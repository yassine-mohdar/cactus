<?php

namespace App\Modules\Shipping\Services;

use App\Modules\Shipping\Enums\ShipmentStatus;
use App\Modules\Shipping\Models\Shipment;
use App\Modules\Shipping\Models\ShippingCarrier;
use App\Modules\Shipping\Models\ShippingCarrierDistrict;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SenditService
{
    private const BASE_URL = 'https://app.sendit.ma/api/v1';

    public function __construct(
        private readonly ShipmentService $shipmentService,
    ) {}

    public function authenticate(ShippingCarrier $carrier): string
    {
        $this->ensureSenditCarrier($carrier);

        return Cache::remember($this->tokenCacheKey($carrier), now()->addMinutes(50), function () use ($carrier): string {
            $response = Http::baseUrl(self::BASE_URL)
                ->acceptJson()
                ->post('/login', [
                    'public_key' => $carrier->getCredential('public_key'),
                    'secret_key' => $carrier->getCredential('secret_key'),
                ]);

            if ($response->failed()) {
                throw new RuntimeException('Sendit authentication failed: '.$response->body());
            }

            $token = (string) $response->json('data.token');

            if ($token === '') {
                throw new RuntimeException('Sendit authentication did not return a token.');
            }

            return $token;
        });
    }

    public function createDelivery(Shipment $shipment): array
    {
        $carrier = $this->carrierForShipment($shipment);
        $response = $this->send($carrier, fn (PendingRequest $request) => $request->post('/deliveries', $this->buildDeliveryPayload($shipment, $carrier)));

        return $this->storeDeliveryResponse($shipment, $response, $carrier);
    }

    public function updateDelivery(Shipment $shipment): array
    {
        if (! filled($shipment->external_reference)) {
            throw new RuntimeException('The shipment does not have a Sendit delivery code yet.');
        }

        $carrier = $this->carrierForShipment($shipment);
        $response = $this->send($carrier, fn (PendingRequest $request) => $request->put('/deliveries/'.$shipment->external_reference, $this->buildDeliveryPayload($shipment, $carrier)));

        return $this->storeDeliveryResponse($shipment, $response, $carrier);
    }

    public function deleteDelivery(Shipment $shipment, bool $transitionLocal = false, ?string $note = null): array
    {
        if (! filled($shipment->external_reference)) {
            if ($transitionLocal && $shipment->status !== ShipmentStatus::CANCELLED) {
                $this->shipmentService->transitionStatus(
                    $shipment,
                    ShipmentStatus::CANCELLED,
                    $note ?: 'Shipment cancelled before Sendit parcel creation.',
                );
            }

            return [
                'success' => true,
                'message' => 'No remote Sendit parcel was linked to this shipment.',
            ];
        }

        if (! $this->canDeleteDelivery($shipment)) {
            throw new RuntimeException('The Sendit parcel can no longer be deleted because it has already been collected or reached a final carrier state.');
        }

        $carrier = $this->carrierForShipment($shipment);
        $response = $this->send($carrier, fn (PendingRequest $request) => $request->delete('/deliveries/'.$shipment->external_reference));

        if ($response->failed()) {
            $this->markProviderError($shipment, 'Sendit parcel deletion failed: '.$response->body());
            throw new RuntimeException('Sendit parcel deletion failed: '.$response->body());
        }

        $payload = $response->json();

        $shipment->forceFill([
            'external_status' => 'DELETED',
            'label_url' => null,
            'external_payload' => $payload,
            'last_provider_sync_at' => now(),
            'provider_error' => null,
        ])->save();

        if ($transitionLocal && $shipment->fresh()->status !== ShipmentStatus::CANCELLED) {
            $shipment = $this->shipmentService->transitionStatus(
                $shipment->fresh(),
                ShipmentStatus::CANCELLED,
                $note ?: 'Shipment cancelled and Sendit parcel deleted before collection.',
            );
        }

        return $payload;
    }

    public function fetchDelivery(Shipment $shipment): array
    {
        if (! filled($shipment->external_reference)) {
            throw new RuntimeException('The shipment does not have a Sendit delivery code yet.');
        }

        $carrier = $this->carrierForShipment($shipment);
        $response = $this->send($carrier, fn (PendingRequest $request) => $request->get('/deliveries/'.$shipment->external_reference));

        if ($response->failed()) {
            $this->markProviderError($shipment, 'Sendit sync failed: '.$response->body());
            throw new RuntimeException('Sendit fetch failed: '.$response->body());
        }

        $payload = $response->json();
        $data = $response->json('data', []);

        $labelUrl = (string) data_get($data, 'labelUrl', '');

        if ($labelUrl === '' && filled($shipment->external_reference)) {
            $labelUrl = $this->resolveLabelUrl($carrier, [$shipment->external_reference]) ?? '';
        }

        $shipment->forceFill([
            'external_status' => data_get($data, 'status'),
            'label_url' => $labelUrl !== '' ? $labelUrl : $shipment->label_url,
            'external_payload' => $payload,
            'last_provider_sync_at' => now(),
            'provider_error' => null,
            'tracking_number' => $shipment->tracking_number ?: $shipment->external_reference,
        ])->save();

        $this->applyMappedStatus($shipment->fresh(['shippingMethod.shippingCarrier']), $payload);

        return $payload;
    }

    public function printLabels(array $codes, int $format, ?ShippingCarrier $carrier = null): array
    {
        $carrier ??= ShippingCarrier::query()->where('provider', ShippingCarrier::PROVIDER_SENDIT)->firstOrFail();

        $response = $this->send($carrier, fn (PendingRequest $request) => $request->post('/deliveries/getlabels', [
            'codesToPrint' => implode(',', $codes),
            'printFormat' => $format,
        ]));

        if ($response->failed()) {
            throw new RuntimeException('Sendit label printing failed: '.$response->body());
        }

        return $response->json();
    }

    public function syncDistricts(?ShippingCarrier $carrier = null): int
    {
        $carrier ??= ShippingCarrier::query()->where('provider', ShippingCarrier::PROVIDER_SENDIT)->firstOrFail();
        $this->ensureSenditCarrier($carrier);

        $page = 1;
        $count = 0;

        do {
            $response = $this->send($carrier, fn (PendingRequest $request) => $request->get('/districts', ['page' => $page]));

            if ($response->failed()) {
                throw new RuntimeException('Sendit district sync failed: '.$response->body());
            }

            $payload = $response->json();

            foreach ($response->json('data', []) as $district) {
                ShippingCarrierDistrict::updateOrCreate(
                    [
                        'shipping_carrier_id' => $carrier->id,
                        'external_id' => (string) data_get($district, 'id'),
                    ],
                    [
                        'city' => trim((string) data_get($district, 'ville')),
                        'district_name' => trim((string) (data_get($district, 'name') ?: data_get($district, 'ville'))),
                        'arabic_name' => trim((string) data_get($district, 'arabic_name')) ?: null,
                        'price' => data_get($district, 'price'),
                        'estimated_delivery' => trim((string) data_get($district, 'delais')) ?: null,
                        'is_pickup' => (bool) data_get($district, 'pickup_district', false),
                        'is_active' => true,
                        'metadata' => $district,
                    ]
                );

                $count++;
            }

            $page++;
        } while (filled(data_get($payload, 'next_page_url')));

        $carrier->update([
            'metadata' => array_merge($carrier->metadata ?? [], [
                'districts_synced_at' => now()->toIso8601String(),
            ]),
        ]);

        return $count;
    }

    public function syncWebhookDelivery(ShippingCarrier $carrier, array $payload): ?Shipment
    {
        $this->ensureSenditCarrier($carrier);

        $shipment = $this->findShipmentFromWebhookPayload($carrier, $payload);

        if (! $shipment) {
            return null;
        }

        try {
            $this->fetchDelivery($shipment);
        } catch (Throwable $exception) {
            Log::warning('[Shipping] Sendit webhook fell back to raw payload sync', [
                'carrier_id' => $carrier->id,
                'shipment_id' => $shipment->id,
                'error' => $exception->getMessage(),
            ]);

            $shipment = $this->applyIncomingPayload($shipment, $payload);
        }

        return $shipment->fresh(['shippingMethod.shippingCarrier', 'order.customer', 'order.addresses', 'order.lineItems']);
    }

    public function canDeleteDelivery(Shipment $shipment): bool
    {
        $externalStatus = strtoupper(trim((string) $shipment->external_status));

        if (in_array($shipment->status, [
            ShipmentStatus::DISPATCHED,
            ShipmentStatus::IN_TRANSIT,
            ShipmentStatus::DELIVERED,
            ShipmentStatus::FAILED_DELIVERY,
            ShipmentStatus::RETURNED,
        ], true)) {
            return false;
        }

        return ! in_array($externalStatus, [
            'PICKEDUP',
            'TRANSIT',
            'DISTRIBUTED',
            'DELIVERING',
            'DELIVERED',
            'REJECTED',
            'RETURNED',
            'CANCELED',
        ], true);
    }

    public function syncActiveShipments(): int
    {
        $count = 0;

        Shipment::query()
            ->with(['shippingMethod.shippingCarrier', 'order.customer', 'order.addresses', 'order.lineItems'])
            ->whereNotNull('external_reference')
            ->whereNotIn('status', [
                ShipmentStatus::DELIVERED->value,
                ShipmentStatus::CANCELLED->value,
                ShipmentStatus::RETURNED->value,
            ])
            ->whereHas('shippingMethod.shippingCarrier', function ($query): void {
                $query->where('provider', ShippingCarrier::PROVIDER_SENDIT);
            })
            ->chunkById(50, function ($shipments) use (&$count): void {
                foreach ($shipments as $shipment) {
                    try {
                        $this->fetchDelivery($shipment);
                        $count++;
                    } catch (\Throwable $e) {
                        Log::warning('[Shipping] Sendit shipment sync failed', [
                            'shipment_id' => $shipment->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        return $count;
    }

    public function matchDistrictForCity(ShippingCarrier $carrier, ?string $city): ?ShippingCarrierDistrict
    {
        $city = trim((string) $city);

        if ($city === '') {
            return null;
        }

        $normalized = mb_strtolower($city);

        return ShippingCarrierDistrict::query()
            ->where('shipping_carrier_id', $carrier->id)
            ->where('is_active', true)
            ->get()
            ->first(function (ShippingCarrierDistrict $district) use ($normalized): bool {
                $districtCity = mb_strtolower((string) $district->city);
                $districtName = mb_strtolower((string) $district->district_name);

                return $districtCity === $normalized
                    || $districtName === $normalized
                    || str_contains($districtName, $normalized)
                    || str_contains($districtCity, $normalized);
            });
    }

    public function resolveDistrictForAddress(
        ShippingCarrier $carrier,
        mixed $districtId = null,
        ?string $districtName = null,
        ?string $city = null,
    ): ?ShippingCarrierDistrict {
        $districts = ShippingCarrierDistrict::query()
            ->where('shipping_carrier_id', $carrier->id)
            ->where('is_active', true)
            ->get();

        if (filled($districtId)) {
            $directMatch = $districts->first(fn (ShippingCarrierDistrict $district): bool => (string) $district->id === (string) $districtId);

            if (! $directMatch) {
                $directMatch = $districts->first(fn (ShippingCarrierDistrict $district): bool => (string) $district->external_id === (string) $districtId);
            }

            if ($directMatch) {
                return $directMatch;
            }

            return null;
        }

        $districtName = trim((string) $districtName);

        if ($districtName !== '') {
            $normalizedDistrictName = mb_strtolower($districtName);

            $exactNameMatch = $districts->first(function (ShippingCarrierDistrict $district) use ($normalizedDistrictName): bool {
                return mb_strtolower((string) $district->district_name) === $normalizedDistrictName;
            });

            if ($exactNameMatch) {
                return $exactNameMatch;
            }

            $containsNameMatch = $districts->first(function (ShippingCarrierDistrict $district) use ($normalizedDistrictName): bool {
                return str_contains(mb_strtolower((string) $district->district_name), $normalizedDistrictName);
            });

            if ($containsNameMatch) {
                return $containsNameMatch;
            }
        }

        return $this->matchDistrictForCity($carrier, $city);
    }

    private function buildDeliveryPayload(Shipment $shipment, ShippingCarrier $carrier): array
    {
        $shipment->loadMissing(['order.customer', 'order.addresses', 'order.lineItems', 'shippingMethod.shippingCarrier']);
        $order = $shipment->order;
        $address = $order?->shippingAddress ?: $order?->addresses?->firstWhere('type', 'shipping');
        $district = $this->resolveDistrictForAddress(
            $carrier,
            $address?->shipping_carrier_district_id,
            $address?->state,
            $address?->city,
        );

        if (! $district) {
            throw new RuntimeException('No synced Sendit district matches the shipment destination city.');
        }

        $customerName = trim(implode(' ', array_filter([
            $address?->first_name,
            $address?->last_name,
        ]))) ?: ($order?->customer?->name ?? 'Customer');

        $addressParts = array_filter([
            $address?->address_line_1,
            $address?->address_line_2,
            $address?->city,
            $address?->postal_code,
            $address?->country,
        ]);

        $productSummary = collect($order?->lineItems ?? [])
            ->map(function ($item): string {
                $reference = trim((string) ($item->sku ?: $item->product_name ?: 'item'));

                return $reference.':'.$item->quantity;
            })
            ->implode(';');

        return [
            'pickup_district_id' => (int) $carrier->setting('pickup_district_id'),
            'district_id' => (int) $district->external_id,
            'name' => $customerName,
            'amount' => (float) ($order?->grand_total ?? 0),
            'address' => implode(', ', $addressParts),
            'phone' => $this->normalizePhoneForSendit($address?->phone ?: $order?->customer?->phone ?: ''),
            'comment' => trim((string) ($shipment->internal_notes ?: $order?->admin_notes ?: '')) ?: null,
            'reference' => $order?->reference_number,
            'allow_open' => 0,
            'allow_try' => 0,
            'products_from_stock' => 0,
            'products' => $productSummary,
            'option_exchange' => 0,
        ];
    }

    private function storeDeliveryResponse(Shipment $shipment, Response $response, ShippingCarrier $carrier): array
    {
        if ($response->failed()) {
            $this->markProviderError($shipment, 'Sendit delivery sync failed: '.$response->body());
            throw new RuntimeException('Sendit delivery sync failed: '.$response->body());
        }

        $payload = $response->json();
        $data = $response->json('data', []);
        $code = (string) data_get($data, 'code');
        $labelUrl = (string) data_get($data, 'labelUrl', '');

        if ($labelUrl === '' && $code !== '') {
            $labelUrl = $this->resolveLabelUrl($carrier, [$code]) ?? '';
        }

        $shipment->update([
            'external_reference' => $code !== '' ? $code : $shipment->external_reference,
            'external_status' => data_get($data, 'status'),
            'tracking_number' => $shipment->tracking_number ?: ($code !== '' ? $code : null),
            'label_url' => $labelUrl !== '' ? $labelUrl : $shipment->label_url,
            'external_payload' => $payload,
            'last_provider_sync_at' => now(),
            'provider_error' => null,
        ]);

        return $payload;
    }

    private function resolveLabelUrl(ShippingCarrier $carrier, array $codes, int $format = 0): ?string
    {
        try {
            return trim((string) data_get($this->printLabels($codes, $format, $carrier), 'data.fileUrl')) ?: null;
        } catch (\Throwable $exception) {
            Log::warning('[Shipping] Sendit label URL sync failed', [
                'carrier_id' => $carrier->id,
                'codes' => $codes,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function applyMappedStatus(Shipment $shipment, array $payload): void
    {
        $externalStatus = strtoupper((string) data_get($payload, 'data.status'));

        if ($externalStatus === '') {
            return;
        }

        $note = 'Sendit status sync: '.$externalStatus;

        match ($externalStatus) {
            'TRANSIT', 'DISTRIBUTED', 'DELIVERING' => $this->syncShipmentStatus($shipment, ShipmentStatus::IN_TRANSIT, $note),
            'DELIVERED' => $this->syncShipmentStatus($shipment, ShipmentStatus::DELIVERED, $note),
            'CANCELED' => $this->syncShipmentStatus($shipment, ShipmentStatus::CANCELLED, $note),
            'REJECTED' => $this->syncShipmentStatus($shipment, ShipmentStatus::FAILED_DELIVERY, $note),
            'UNREACHABLE', 'POSTPONED' => $this->shipmentService->flagDeliveryIssue($shipment, 'Sendit reported status '.$externalStatus, $externalStatus),
            default => null,
        };
    }

    private function syncShipmentStatus(Shipment $shipment, ShipmentStatus $newStatus, string $note): void
    {
        if ($shipment->status === $newStatus) {
            return;
        }

        $this->shipmentService->applyExternalStatus($shipment, $newStatus, $note);
    }

    private function request(ShippingCarrier $carrier, bool $freshToken = false): PendingRequest
    {
        return Http::baseUrl(self::BASE_URL)
            ->acceptJson()
            ->withToken($freshToken ? $this->refreshToken($carrier) : $this->authenticate($carrier));
    }

    private function send(ShippingCarrier $carrier, callable $callback): Response
    {
        $response = $callback($this->request($carrier));

        if ($response->status() !== 401) {
            return $response;
        }

        Log::info('[Shipping] Sendit token expired, retrying with a fresh token', [
            'carrier_id' => $carrier->id,
        ]);

        return $callback($this->request($carrier, true));
    }

    private function carrierForShipment(Shipment $shipment): ShippingCarrier
    {
        $carrier = $shipment->shippingMethod?->shippingCarrier;

        if (! $carrier || ! $carrier->isSendit()) {
            throw new RuntimeException('This shipment is not linked to a Sendit carrier.');
        }

        if (! $carrier->isConfigured()) {
            throw new RuntimeException('The Sendit carrier is not fully configured.');
        }

        return $carrier;
    }

    private function ensureSenditCarrier(ShippingCarrier $carrier): void
    {
        if (! $carrier->isSendit()) {
            throw new RuntimeException('This carrier does not use the Sendit provider.');
        }
    }

    private function normalizePhoneForSendit(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?: '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '00212')) {
            $digits = substr($digits, 5);
        } elseif (str_starts_with($digits, '212')) {
            $digits = substr($digits, 3);
        }

        if (preg_match('/^0\d{9}$/', $digits)) {
            return $digits;
        }

        if (preg_match('/^[5-7]\d{8}$/', $digits)) {
            return '0'.$digits;
        }

        return $digits;
    }

    private function tokenCacheKey(ShippingCarrier $carrier): string
    {
        return 'shipping:sendit:token:'.$carrier->id;
    }

    private function refreshToken(ShippingCarrier $carrier): string
    {
        Cache::forget($this->tokenCacheKey($carrier));

        return $this->authenticate($carrier);
    }

    private function markProviderError(Shipment $shipment, string $message): void
    {
        $shipment->update([
            'provider_error' => $message,
            'last_provider_sync_at' => now(),
        ]);
    }

    private function applyIncomingPayload(Shipment $shipment, array $payload): Shipment
    {
        $data = data_get($payload, 'data');
        $data = is_array($data) ? $data : [];
        $code = (string) (data_get($data, 'code') ?: data_get($payload, 'code') ?: $shipment->external_reference);
        $status = data_get($data, 'status') ?: data_get($payload, 'status');

        $shipment->forceFill([
            'external_reference' => $code !== '' ? $code : $shipment->external_reference,
            'external_status' => $status ?: $shipment->external_status,
            'tracking_number' => $shipment->tracking_number ?: ($code !== '' ? $code : null),
            'external_payload' => $payload,
            'last_provider_sync_at' => now(),
            'provider_error' => null,
        ])->save();

        if (filled($status)) {
            $this->applyMappedStatus($shipment->fresh(['shippingMethod.shippingCarrier']), [
                'data' => ['status' => $status],
            ]);
        }

        return $shipment->fresh(['shippingMethod.shippingCarrier']);
    }

    private function findShipmentFromWebhookPayload(ShippingCarrier $carrier, array $payload): ?Shipment
    {
        $code = $this->extractWebhookValue($payload, [
            'data.code',
            'code',
            'delivery.code',
            'parcel.code',
            'colis.code',
        ]);

        if (filled($code)) {
            $shipment = Shipment::query()
                ->with(['shippingMethod.shippingCarrier', 'order.customer', 'order.addresses', 'order.lineItems'])
                ->whereHas('shippingMethod.shippingCarrier', function ($query) use ($carrier): void {
                    $query->whereKey($carrier->id);
                })
                ->where(function ($query) use ($code): void {
                    $query->where('external_reference', $code)
                        ->orWhere('tracking_number', $code);
                })
                ->latest('id')
                ->first();

            if ($shipment) {
                return $shipment;
            }
        }

        $reference = $this->extractWebhookValue($payload, [
            'data.reference',
            'reference',
            'delivery.reference',
            'parcel.reference',
            'colis.reference',
        ]);

        if (! filled($reference)) {
            return null;
        }

        return Shipment::query()
            ->with(['shippingMethod.shippingCarrier', 'order.customer', 'order.addresses', 'order.lineItems'])
            ->whereHas('shippingMethod.shippingCarrier', function ($query) use ($carrier): void {
                $query->whereKey($carrier->id);
            })
            ->whereHas('order', function ($query) use ($reference): void {
                $query->where('reference_number', $reference);
            })
            ->latest('id')
            ->first();
    }

    private function extractWebhookValue(array $payload, array $paths): ?string
    {
        foreach ($paths as $path) {
            $value = data_get($payload, $path);

            if (filled($value)) {
                return trim((string) $value);
            }
        }

        foreach (Arr::flatten($payload) as $value) {
            if (is_string($value) && filled($value) && preg_match('/^DH[A-Z0-9]+$/i', $value)) {
                return trim($value);
            }
        }

        return null;
    }
}
