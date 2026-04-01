<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\Finance\Enums\TransactionStatus;
use App\Modules\Finance\Models\PaymentTransaction;
use App\Modules\Finance\Services\InvoiceService;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderAddress;
use App\Modules\Shipping\Jobs\SyncSenditDeliveryUpdateJob;
use App\Modules\Shipping\Models\Shipment;
use App\Modules\Shipping\Models\ShippingCarrierDistrict;
use App\Modules\Shipping\Models\ShippingMethod;
use App\Modules\Shipping\Enums\ShipmentStatus;
use App\Modules\Shipping\Services\SenditService;
use App\Modules\Shipping\Services\ShipmentService;
use App\Modules\Support\Models\ActivityTimeline;
use App\Modules\Support\Models\InternalNote;
use App\Modules\Shipping\Models\ShipmentStatusHistory;
use App\Support\InternationalDirectory;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.any:orders.override_status')->only('updateStatus');
        $this->middleware('permission.any:orders.update,shipping.update')->only('updateDeliveryContact');
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::with('customer')->orderByDesc('created_at');
        $summaryBaseQuery = Order::query();

        if ($search = $request->input('search')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('reference_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('email', 'LIKE', "%{$search}%")
                            ->orWhere('name', 'LIKE', "%{$search}%")
                            ->orWhere('first_name', 'LIKE', "%{$search}%")
                            ->orWhere('last_name', 'LIKE', "%{$search}%");
                    });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', Carbon::parse($dateFrom)->toDateString());
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', Carbon::parse($dateTo)->toDateString());
        }

        if ($customerId = $request->input('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        if ($paymentMethod = $request->input('payment_method')) {
            $query->where('payment_method', $paymentMethod);
        }

        if ($shippingMethod = $request->input('shipping_method')) {
            $query->where('shipping_method', $shippingMethod);
        }

        $orders = $query->paginate(25)->withQueryString();

        $summary = [
            'total_orders' => (clone $summaryBaseQuery)->count(),
            'awaiting_payment' => (clone $summaryBaseQuery)->where('status', OrderStatus::AWAITING_PAYMENT)->count(),
            'preparing' => (clone $summaryBaseQuery)->where('status', OrderStatus::PREPARING)->count(),
            'gross_30_days' => (float) ((clone $summaryBaseQuery)
                ->where('created_at', '>=', now()->subDays(30))
                ->sum('grand_total') ?? 0),
        ];

        $statuses = OrderStatus::cases();
        $customers = User::query()
            ->whereIn('id', Order::query()->whereNotNull('customer_id')->select('customer_id'))
            ->orderByRaw("COALESCE(NULLIF(name, ''), CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')))")
            ->get(['id', 'name', 'first_name', 'last_name', 'email']);
        $paymentMethods = $this->distinctOrderFieldValues('payment_method');
        $shippingMethods = $this->distinctOrderFieldValues('shipping_method');

        return view('admin.orders.index', compact(
            'orders',
            'statuses',
            'summary',
            'customers',
            'paymentMethods',
            'shippingMethods',
        ));
    }

    public function show(Order $order, InvoiceService $invoiceService)
    {
        $this->authorize('view', $order);

        $order->load([
            'customer',
            'lineItems',
            'addresses.shippingCarrierDistrict',
            'shipments.statusHistory.changedByUser',
            'shipments.shippingMethod.shippingCarrier.districts',
            'shipments.shippingMethod.districtOverrides',
            'transactions',
            'invoice',
            'shippingMethodRecord.shippingCarrier.districts',
            'shippingMethodRecord.districtOverrides',
        ]);

        $shippingAddress = $order->addresses->where('type', 'shipping')->first();
        $billingAddress = $order->addresses->where('type', 'billing')->first();
        $latestShipment = $order->shipments->sortByDesc('id')->first();
        $notes = InternalNote::query()
            ->where('notable_type', Order::class)
            ->where('notable_id', $order->id)
            ->with('author')
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->get();
        $auditTrail = AuditLog::query()
            ->where('auditable_type', Order::class)
            ->where('auditable_id', $order->id)
            ->latest('id')
            ->limit(10)
            ->get();
        $timeline = $this->buildTimeline($order, $notes);
        $statuses = OrderStatus::cases();
        $invoice = $invoiceService->ensureInvoiceForOrder($order);
        $deliveryEditor = $this->buildDeliveryEditor($order, $shippingAddress, $latestShipment);

        return view('admin.orders.show', compact(
            'order',
            'shippingAddress',
            'billingAddress',
            'latestShipment',
            'notes',
            'auditTrail',
            'timeline',
            'statuses',
            'invoice',
            'deliveryEditor',
        ));
    }

    public function updateStatus(
        Request $request,
        Order $order,
        AuditLogger $audit,
        InvoiceService $invoiceService,
        ShipmentService $shipmentService,
        SenditService $senditService,
    )
    {
        abort_unless($request->user()?->can('orders.override_status'), 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_map(static fn (OrderStatus $status) => $status->value, OrderStatus::cases()))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $newStatus = OrderStatus::from($validated['status']);
        $currentStatus = $order->status instanceof OrderStatus
            ? $order->status
            : OrderStatus::from((string) $order->status);
        $note = trim((string) ($validated['notes'] ?? ''));

        if ($currentStatus === $newStatus) {
            return redirect()->route('admin.orders.show', $order)->with('success', 'Order status is already up to date.');
        }

        if ($newStatus === OrderStatus::CANCELLED) {
            $order->loadMissing('shipments.shippingMethod.shippingCarrier');

            foreach ($order->shipments as $shipment) {
                if ($shipment->status === ShipmentStatus::CANCELLED) {
                    continue;
                }

                if (! $shipment->status->canTransitionTo(ShipmentStatus::CANCELLED)) {
                    return redirect()
                        ->route('admin.orders.show', $order)
                        ->with('error', 'This order already has a shipment that can no longer be cancelled before carrier collection.');
                }

                if ($shipment->usesSendit() && filled($shipment->external_reference)) {
                    try {
                        $senditService->deleteDelivery(
                            $shipment,
                            transitionLocal: true,
                            note: $note !== '' ? $note : 'Order cancelled before Sendit collection.',
                        );
                    } catch (\RuntimeException $exception) {
                        return redirect()
                            ->route('admin.orders.show', $order)
                            ->with('error', $exception->getMessage());
                    }
                } else {
                    $shipmentService->transitionStatus(
                        $shipment,
                        ShipmentStatus::CANCELLED,
                        $note !== '' ? $note : 'Order cancelled from the order detail screen.',
                    );
                }
            }
        }

        $order->update([
            'status' => $newStatus,
        ]);

        $invoiceService->ensureInvoiceForOrder($order->fresh('transactions', 'shipments', 'invoice'));

        $audit->log(
            action: 'orders.status_overridden',
            target: $order->fresh(),
            oldValues: ['status' => $currentStatus->value],
            newValues: ['status' => $newStatus->value],
            notes: $note !== '' ? $note : 'Order status updated from the order detail screen.',
            context: [
                'module' => 'orders',
                'source' => 'order_controller',
                'manual_override' => true,
            ],
        );

        return redirect()->route('admin.orders.show', $order)->with('success', 'Order status updated successfully.');
    }

    public function updateDeliveryContact(
        Request $request,
        Order $order,
        AuditLogger $audit,
        InvoiceService $invoiceService,
    ) {
        abort_unless($request->user()?->canAny(['orders.update', 'shipping.update']), 403);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:40'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:40'],
            'country' => ['required', 'string', 'max:120'],
            'district_id' => ['nullable', 'integer'],
        ]);

        $order->loadMissing([
            'addresses.shippingCarrierDistrict',
            'shipments.shippingMethod.shippingCarrier.districts',
            'shipments.shippingMethod.districtOverrides',
            'shipments.order.customer',
            'shipments.order.addresses.shippingCarrierDistrict',
            'shipments.order.lineItems',
            'transactions',
            'invoice',
            'shippingMethodRecord.shippingCarrier.districts',
            'shippingMethodRecord.districtOverrides',
        ]);

        $shippingAddress = $order->shippingAddress ?: $order->addresses->firstWhere('type', 'shipping');
        $shippingMethod = $this->editableShippingMethod($order, $order->shipments->sortByDesc('id')->first());
        $canReprice = $shippingMethod && ! $this->financialTotalsLocked($order);
        $selectedDistrict = null;

        if ($shippingMethod?->isApiManaged() && strtoupper(trim((string) $validated['country'])) === 'MA') {
            if (blank($validated['district_id'] ?? null)) {
                throw ValidationException::withMessages([
                    'district_id' => 'Choose the exact synced Sendit destination district before saving this delivery.',
                ]);
            }

            $selectedDistrict = $this->resolveShippingMethodDistrict(
                $shippingMethod,
                $validated['district_id'] ?? null,
                strictCandidateOnly: true,
            );

            if (! $selectedDistrict) {
                throw ValidationException::withMessages([
                    'district_id' => 'Choose a valid synced Sendit destination district to update this delivery.',
                ]);
            }

            $validated['city'] = $selectedDistrict->city;
            $validated['state'] = $selectedDistrict->district_name;
        }

        if ($shippingMethod?->isApiManaged() && strtoupper(trim((string) $validated['country'])) !== 'MA') {
            throw ValidationException::withMessages([
                'country' => 'Sendit deliveries on this order must remain in Morocco and use a synced destination district.',
            ]);
        }

        if (! $shippingMethod?->isApiManaged() && blank($validated['city'] ?? null)) {
            throw ValidationException::withMessages([
                'city' => 'City is required for this delivery.',
            ]);
        }

        $oldValues = collect([
            'first_name' => $shippingAddress?->first_name,
            'last_name' => $shippingAddress?->last_name,
            'phone' => $shippingAddress?->phone,
            'address_line_1' => $shippingAddress?->address_line_1,
            'address_line_2' => $shippingAddress?->address_line_2,
            'city' => $shippingAddress?->city,
            'state' => $shippingAddress?->state,
            'postal_code' => $shippingAddress?->postal_code,
            'country' => $shippingAddress?->country,
            'shipping_carrier_district_id' => $shippingAddress?->shipping_carrier_district_id,
            'shipping_total' => (float) $order->shipping_total,
            'grand_total' => (float) $order->grand_total,
        ])->all();

        $senditShipment = $order->shipments
            ->sortByDesc('id')
            ->first(fn ($shipment) => $shipment->usesSendit() && filled($shipment->external_reference) && ! $shipment->status->isFinal());

        $recalculatedTotals = [
            'shipping_total' => (float) $order->shipping_total,
            'grand_total' => (float) $order->grand_total,
        ];

        if ($canReprice && $shippingMethod) {
            $quote = $this->quoteShippingMethod($shippingMethod, (float) $order->subtotal, $selectedDistrict);
            $recalculatedTotals = [
                'shipping_total' => $quote['cost'],
                'grand_total' => round((float) $order->subtotal + (float) $order->tax_total + $quote['cost'] - (float) $order->discount_total, 2),
            ];
        }

        DB::transaction(function () use (
            $order,
            $shippingAddress,
            $validated,
            $selectedDistrict,
            $canReprice,
            $recalculatedTotals,
            $invoiceService,
        ): void {
            $address = $shippingAddress ?: $order->addresses()->create(['type' => 'shipping']);

            $address->fill(array_merge($validated, [
                'type' => 'shipping',
                'shipping_carrier_district_id' => $selectedDistrict?->id,
            ]))->save();

            if ($canReprice) {
                $order->forceFill($recalculatedTotals)->save();
                $this->syncPendingTransactionsForOrder($order->fresh('transactions'));
                $invoiceService->ensureInvoiceForOrder($order->fresh(['transactions', 'shipments', 'invoice']));
            }
        });

        $senditSyncQueued = false;

        if ($senditShipment) {
            Shipment::query()->whereKey($senditShipment->id)->update([
                'provider_error' => null,
            ]);

            SyncSenditDeliveryUpdateJob::dispatch($senditShipment->id);
            $senditSyncQueued = true;
        }

        $updatedOrder = $order->fresh(['addresses.shippingCarrierDistrict', 'shipments.shippingMethod.shippingCarrier', 'transactions', 'invoice']);

        $audit->log(
            action: 'orders.delivery_contact_updated',
            target: $updatedOrder,
            oldValues: $oldValues,
            newValues: array_merge($validated, [
                'shipping_carrier_district_id' => $selectedDistrict?->id,
                'shipping_total' => $recalculatedTotals['shipping_total'],
                'grand_total' => $recalculatedTotals['grand_total'],
            ]),
            notes: match (true) {
                $senditSyncQueued && $canReprice => 'Delivery contact updated, repriced, and queued for Sendit sync.',
                $senditSyncQueued => 'Delivery contact updated locally and queued for Sendit sync.',
                $canReprice => 'Delivery contact updated and shipping totals repriced.',
                default => 'Delivery contact updated locally.',
            },
            context: [
                'module' => 'orders',
                'source' => 'order_controller',
                'sendit_sync_queued' => $senditSyncQueued,
                'shipping_repriced' => $canReprice,
                'district_id' => $selectedDistrict?->id,
            ],
        );

        $redirect = redirect()
            ->route('admin.orders.show', $order)
            ->with('success', match (true) {
                $senditSyncQueued && $canReprice => 'Delivery contact updated, shipping totals recalculated, and Sendit sync queued.',
                $senditSyncQueued => 'Delivery contact updated and Sendit sync queued.',
                $canReprice => 'Delivery contact updated and shipping totals recalculated.',
                default => 'Delivery contact updated successfully.',
            });

        return $redirect;
    }

    /**
     * @return list<array{value:string,label:string}>
     */
    private function buildDeliveryEditor(Order $order, ?OrderAddress $shippingAddress, ?Shipment $latestShipment): array
    {
        $shippingMethod = $this->editableShippingMethod($order, $latestShipment);
        $isSenditDistrictMode = $shippingMethod?->isApiManaged()
            && $shippingMethod->shippingCarrier?->isSendit()
            && strtoupper(trim((string) ($shippingAddress?->country ?? 'MA'))) === 'MA';

        $selectedDistrict = $isSenditDistrictMode
            ? $this->resolveShippingMethodDistrict(
                $shippingMethod,
                old('district_id'),
                old('state', $shippingAddress?->state),
                old('city', $shippingAddress?->city),
                $shippingAddress?->shipping_carrier_district_id,
            )
            : null;

        $districtOptions = $isSenditDistrictMode
            ? $this->shippingDistrictOptions($shippingMethod)
            : [];

        $previewQuote = $shippingMethod
            ? $this->quoteShippingMethod($shippingMethod, (float) $order->subtotal, $selectedDistrict)
            : ['cost' => (float) $order->shipping_total, 'eta' => null, 'source' => 'none', 'has_match' => false];

        return [
            'open' => $this->shouldOpenDeliveryEditor(),
            'can_edit' => $shippingAddress !== null,
            'sendit_mode' => $isSenditDistrictMode,
            'sendit_live_sync' => $latestShipment?->usesSendit() && filled($latestShipment->external_reference) && ! $latestShipment->status->isFinal(),
            'can_reprice' => $shippingMethod && ! $this->financialTotalsLocked($order),
            'totals_locked' => $this->financialTotalsLocked($order),
            'selected_district_id' => (string) ($selectedDistrict?->id ?? ''),
            'district_options' => $districtOptions,
            'preview_quote' => $previewQuote,
            'shipping_method_name' => $shippingMethod?->name,
            'country_flag' => InternationalDirectory::flagEmoji((string) ($shippingAddress?->country ?: 'MA')),
        ];
    }

    private function shouldOpenDeliveryEditor(): bool
    {
        return session()->hasOldInput()
            || session('open_delivery_editor', false)
            || request()->boolean('edit_delivery');
    }

    private function editableShippingMethod(Order $order, ?Shipment $latestShipment): ?ShippingMethod
    {
        return $latestShipment?->shippingMethod ?: $order->shippingMethodRecord;
    }

    private function resolveShippingMethodDistrict(
        ?ShippingMethod $shippingMethod,
        mixed $districtId = null,
        ?string $districtName = null,
        ?string $city = null,
        mixed $fallbackDistrictId = null,
        bool $strictCandidateOnly = false,
    ): ?ShippingCarrierDistrict {
        if (! $shippingMethod?->shippingCarrier) {
            return null;
        }

        $districts = $this->districtCollectionForMethod($shippingMethod);
        $candidateIds = array_filter([
            $districtId,
            $fallbackDistrictId,
        ], static fn ($value) => filled($value));

        foreach ($candidateIds as $candidateId) {
            $directMatch = $districts->first(fn (ShippingCarrierDistrict $district): bool => (string) $district->id === (string) $candidateId);

            if (! $directMatch) {
                $directMatch = $districts->first(fn (ShippingCarrierDistrict $district): bool => (string) $district->external_id === (string) $candidateId);
            }

            if ($directMatch) {
                return $directMatch;
            }
        }

        if ($strictCandidateOnly && ! empty($candidateIds)) {
            return null;
        }

        $districtName = trim((string) $districtName);

        if ($districtName !== '') {
            $normalizedDistrictName = mb_strtolower($districtName);

            $nameMatch = $districts->first(function (ShippingCarrierDistrict $district) use ($normalizedDistrictName): bool {
                return mb_strtolower((string) $district->district_name) === $normalizedDistrictName
                    || str_contains(mb_strtolower((string) $district->district_name), $normalizedDistrictName);
            });

            if ($nameMatch) {
                return $nameMatch;
            }
        }

        $city = trim((string) $city);

        if ($city === '') {
            return null;
        }

        $normalizedCity = mb_strtolower($city);

        return $districts->first(function (ShippingCarrierDistrict $district) use ($normalizedCity): bool {
            return mb_strtolower((string) $district->city) === $normalizedCity
                || mb_strtolower((string) $district->district_name) === $normalizedCity
                || str_contains(mb_strtolower((string) $district->district_name), $normalizedCity);
        });
    }

    /**
     * @return \Illuminate\Support\Collection<int, ShippingCarrierDistrict>
     */
    private function districtCollectionForMethod(ShippingMethod $shippingMethod): Collection
    {
        return $shippingMethod->shippingCarrier?->relationLoaded('districts')
            ? $shippingMethod->shippingCarrier->districts->where('is_active', true)->values()
            : $shippingMethod->shippingCarrier?->districts()->where('is_active', true)->get() ?? collect();
    }

    /**
     * @return array<int, array{value:string,label:string,search:string,city:string,district:string,effective_price:float,base_price:float,eta:?string,source:string}>
     */
    private function shippingDistrictOptions(ShippingMethod $shippingMethod): array
    {
        $overrides = $shippingMethod->relationLoaded('districtOverrides')
            ? $shippingMethod->districtOverrides
            : $shippingMethod->districtOverrides()->get();

        return $this->districtCollectionForMethod($shippingMethod)
            ->map(function (ShippingCarrierDistrict $district) use ($overrides): array {
                $forcedPrice = optional($overrides->firstWhere('shipping_carrier_district_id', $district->id))->forced_price;
                $label = $this->districtDisplayLabel($district);

                return [
                    'value' => (string) $district->id,
                    'label' => $label,
                    'search' => mb_strtolower($label.' '.$district->city.' '.$district->district_name.' '.($district->arabic_name ?? '')),
                    'city' => (string) $district->city,
                    'district' => (string) $district->district_name,
                    'effective_price' => round((float) ($forcedPrice ?? $district->price ?? 0), 2),
                    'base_price' => round((float) ($district->price ?? 0), 2),
                    'eta' => $district->estimated_delivery,
                    'source' => $forcedPrice !== null ? 'forced' : 'api',
                ];
            })
            ->sortBy([
                fn (array $option) => mb_strtolower($option['city']),
                fn (array $option) => mb_strtolower($option['district']),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{cost:float,eta:?string,source:string,has_match:bool}
     */
    private function quoteShippingMethod(ShippingMethod $shippingMethod, float $subtotal, ?ShippingCarrierDistrict $district): array
    {
        if (! $shippingMethod->isApiManaged()) {
            return [
                'cost' => round((float) $shippingMethod->calculateCost($subtotal), 2),
                'eta' => $shippingMethod->estimated_days,
                'source' => 'manual',
                'has_match' => true,
            ];
        }

        if (! $district) {
            return [
                'cost' => round((float) $shippingMethod->base_cost, 2),
                'eta' => $shippingMethod->estimated_days,
                'source' => 'pending',
                'has_match' => false,
            ];
        }

        $override = $shippingMethod->relationLoaded('districtOverrides')
            ? $shippingMethod->districtOverrides->firstWhere('shipping_carrier_district_id', $district->id)
            : $shippingMethod->districtOverrides()->where('shipping_carrier_district_id', $district->id)->first();

        return [
            'cost' => round((float) ($override?->forced_price ?? $district->price ?? 0), 2),
            'eta' => $district->estimated_delivery ?: $shippingMethod->estimated_days,
            'source' => $override?->forced_price !== null ? 'forced' : 'api',
            'has_match' => true,
        ];
    }

    private function districtDisplayLabel(ShippingCarrierDistrict $district): string
    {
        $city = trim((string) $district->city);
        $districtName = trim((string) $district->district_name);

        if ($city !== '' && $districtName !== '' && mb_strtolower($city) !== mb_strtolower($districtName)) {
            return $city.' - '.$districtName;
        }

        return $districtName !== '' ? $districtName : $city;
    }

    private function financialTotalsLocked(Order $order): bool
    {
        return $order->transactions->contains(function (PaymentTransaction $transaction): bool {
            return in_array($transaction->status, [
                TransactionStatus::COMPLETED,
                TransactionStatus::REFUNDED,
            ], true);
        });
    }

    private function syncPendingTransactionsForOrder(Order $order): void
    {
        $order->transactions
            ->filter(fn (PaymentTransaction $transaction) => $transaction->status === TransactionStatus::PENDING)
            ->each(function (PaymentTransaction $transaction) use ($order): void {
                $transaction->forceFill([
                    'amount' => $order->grand_total,
                    'net_amount' => round((float) $order->grand_total - (float) ($transaction->fee_amount ?? 0), 2),
                    'order_shipping' => $order->shipping_total,
                    'order_total' => $order->grand_total,
                ])->save();
            });
    }

    private function distinctOrderFieldValues(string $field): array
    {
        return Order::query()
            ->whereNotNull($field)
            ->where($field, '!=', '')
            ->distinct()
            ->orderBy($field)
            ->pluck($field)
            ->map(fn (string $value): array => [
                'value' => $value,
                'label' => Str::of($value)->replace(['_', '-'], ' ')->title()->value(),
            ])
            ->values()
            ->all();
    }

    private function buildTimeline(Order $order, Collection $notes): Collection
    {
        $timeline = collect([
            [
                'title' => 'Order created',
                'copy' => 'Checkout completed and the operational order record was created.',
                'meta' => $order->created_at->format('M d, Y H:i'),
                'timestamp' => $order->created_at,
                'tone' => 'success',
            ],
        ]);

        $timeline = $timeline
            ->merge($order->transactions->map(function (PaymentTransaction $transaction): array {
                $status = $transaction->status?->label() ?? Str::of((string) $transaction->status)->replace('_', ' ')->title()->value();
                $method = $transaction->resolvedPaymentMethodLabel();

                return [
                    'title' => 'Payment transaction '.$status,
                    'copy' => trim(sprintf(
                        '%s%s%s',
                        $method ?: 'Payment method pending',
                        $transaction->reference ? ' · Ref '.$transaction->reference : '',
                        $transaction->failure_reason ? ' · '.$transaction->failure_reason : '',
                    )),
                    'meta' => $transaction->created_at?->format('M d, Y H:i') ?? 'Unknown time',
                    'timestamp' => $transaction->created_at ?? now()->subCentury(),
                    'tone' => match ($transaction->status?->value) {
                        'completed' => 'success',
                        'failed', 'cancelled', 'refunded' => 'danger',
                        default => 'info',
                    },
                ];
            }))
            ->merge($order->shipments->flatMap(function ($shipment): Collection {
                return $shipment->statusHistory->map(function (ShipmentStatusHistory $entry): array {
                    $title = match ($entry->eventTitle()) {
                        'Shipment created' => 'Shipment created',
                        'Status updated' => 'Shipment status updated',
                        'Tracking updated' => 'Shipment tracking updated',
                        'Delivery issue flagged' => 'Shipment issue flagged',
                        'Delivery issue resolved' => 'Shipment issue resolved',
                        default => 'Shipment activity',
                    };

                    return [
                        'title' => $title,
                        'copy' => $entry->eventCopy(),
                        'meta' => ($entry->changed_by_name ?: 'System').' · '.$entry->created_at->format('M d, Y H:i'),
                        'timestamp' => $entry->created_at,
                        'tone' => ! $entry->isStateChange()
                            ? 'neutral'
                            : match ($entry->status_to) {
                            'delivered' => 'success',
                            'failed_delivery', 'cancelled', 'returned' => 'danger',
                            default => 'info',
                        },
                    ];
                });
            }))
            ->merge($notes->map(function (InternalNote $note): array {
                return [
                    'title' => $note->is_pinned ? 'Pinned internal note' : 'Internal note added',
                    'copy' => $note->content,
                    'meta' => ($note->author?->first_name ?: $note->author?->name ?: 'Unknown').' · '.$note->created_at->format('M d, Y H:i'),
                    'timestamp' => $note->created_at,
                    'tone' => 'neutral',
                ];
            }));

        return $timeline
            ->sortByDesc(fn (array $entry) => $entry['timestamp'])
            ->values();
    }
}
