<?php

namespace App\Modules\Payments\Services;

use App\Modules\Payments\Models\GatewaySetting;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\Shipping\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class PaymentMethodAvailabilityService
{
    public function ensureDefaults(): void
    {
        $gatewayDefaults = [
            'offline_transfer' => [
                'name' => 'Offline Bank Transfer',
                'metadata' => [
                    'method_label' => 'Bank Transfer',
                    'checkout_title' => 'Bank transfer instructions',
                    'checkout_description' => 'Show the account details after checkout so customers can complete the transfer manually.',
                    'instructions' => 'Please complete the transfer using the bank details below and share your receipt with the finance team.',
                    'admin_instructions' => 'Finance should validate the transfer reference and receipt before marking the transaction as completed.',
                    'payment_window_hours' => 48,
                    'reference_prefix' => 'NINO',
                    'require_receipt' => true,
                ],
            ],
            'cmi' => [
                'name' => 'CMI (Centre Monétique Interbancaire)',
                'metadata' => [
                    'currency_code' => '504',
                    'language' => 'fr',
                ],
            ],
            'payzone' => [
                'name' => 'Payzone Morocco',
                'metadata' => [
                    'currency' => 'MAD',
                ],
            ],
            'stripe' => [
                'name' => 'Stripe',
                'metadata' => [
                    'currency' => 'MAD',
                ],
            ],
        ];

        foreach ($gatewayDefaults as $gatewayId => $gateway) {
            GatewaySetting::query()->firstOrCreate(
                ['gateway_id' => $gatewayId],
                [
                    'name' => $gateway['name'],
                    'is_enabled' => false,
                    'mode' => 'test',
                    'credentials' => [],
                    'metadata' => $gateway['metadata'],
                ],
            );
        }

        $gatewaySettings = GatewaySetting::query()
            ->whereIn('gateway_id', ['offline_transfer', 'cmi', 'payzone', 'stripe'])
            ->get()
            ->keyBy('gateway_id');

        $defaultMethods = [
            'cod' => [
                'name' => 'Cash on Delivery',
                'channel' => PaymentMethod::CHANNEL_OFFLINE,
                'behavior' => PaymentMethod::BEHAVIOR_COD,
                'gateway_setting_id' => null,
                'is_enabled' => true,
                'sort_order' => 10,
                'metadata' => [
                    'method_label' => 'Cash on Delivery',
                    'checkout_title' => 'Cash on delivery',
                    'checkout_description' => 'Collect payment from the customer when the shipment is delivered.',
                    'instructions' => 'The courier will collect the payment when the order is delivered.',
                    'admin_instructions' => 'Track collection and reconciliation through the COD finance workflow.',
                ],
            ],
            'bank_transfer' => [
                'name' => 'Bank Transfer',
                'channel' => PaymentMethod::CHANNEL_OFFLINE,
                'behavior' => PaymentMethod::BEHAVIOR_OFFLINE_MANUAL,
                'gateway_setting_id' => $gatewaySettings['offline_transfer']->id ?? null,
                'is_enabled' => true,
                'sort_order' => 20,
                'metadata' => array_merge([
                    'method_label' => 'Bank Transfer',
                    'checkout_title' => 'Bank transfer instructions',
                    'checkout_description' => 'Complete the transfer manually and wait for finance verification.',
                    'instructions' => 'Please complete the transfer using the company account below and keep your receipt ready.',
                    'admin_instructions' => 'Finance should verify the credited amount before marking this method as completed.',
                    'payment_window_hours' => 48,
                    'reference_prefix' => 'NINO',
                    'require_receipt' => true,
                    'sync_from_gateway_defaults' => true,
                ], $gatewaySettings['offline_transfer']->metadata ?? []),
            ],
            'cmi' => [
                'name' => 'CMI',
                'channel' => PaymentMethod::CHANNEL_ONLINE,
                'behavior' => PaymentMethod::BEHAVIOR_GATEWAY,
                'gateway_setting_id' => $gatewaySettings['cmi']->id ?? null,
                'is_enabled' => (bool) ($gatewaySettings['cmi']->is_enabled ?? false),
                'sort_order' => 30,
                'metadata' => [
                    'provider_gateway_id' => 'cmi',
                    'method_label' => 'CMI',
                    'checkout_title' => 'CMI card payment',
                    'checkout_description' => 'Hosted card checkout through CMI.',
                ],
            ],
            'payzone' => [
                'name' => 'Payzone',
                'channel' => PaymentMethod::CHANNEL_ONLINE,
                'behavior' => PaymentMethod::BEHAVIOR_GATEWAY,
                'gateway_setting_id' => $gatewaySettings['payzone']->id ?? null,
                'is_enabled' => (bool) ($gatewaySettings['payzone']->is_enabled ?? false),
                'sort_order' => 40,
                'metadata' => [
                    'provider_gateway_id' => 'payzone',
                    'method_label' => 'Payzone',
                    'checkout_title' => 'Payzone checkout',
                    'checkout_description' => 'Hosted online payment through Payzone.',
                ],
            ],
            'stripe' => [
                'name' => 'Stripe',
                'channel' => PaymentMethod::CHANNEL_ONLINE,
                'behavior' => PaymentMethod::BEHAVIOR_GATEWAY,
                'gateway_setting_id' => $gatewaySettings['stripe']->id ?? null,
                'is_enabled' => (bool) ($gatewaySettings['stripe']->is_enabled ?? false),
                'sort_order' => 50,
                'metadata' => [
                    'provider_gateway_id' => 'stripe',
                    'method_label' => 'Stripe',
                    'checkout_title' => 'Stripe checkout',
                    'checkout_description' => 'Hosted online payment through Stripe.',
                ],
            ],
        ];

        foreach ($defaultMethods as $code => $defaults) {
            $method = PaymentMethod::query()->firstOrNew(['code' => $code]);

            if (! $method->exists) {
                $method->fill($defaults);
                $method->save();

                continue;
            }

            $existingMetadata = $method->metadata ?? [];
            $metadata = array_merge($defaults['metadata'] ?? [], $existingMetadata);

            if ($code === 'bank_transfer') {
                $syncFromGatewayDefaults = $this->metadataBoolean($existingMetadata, 'sync_from_gateway_defaults', true);

                if ($syncFromGatewayDefaults) {
                    $metadata = array_merge($metadata, $gatewaySettings['offline_transfer']->metadata ?? []);
                    $metadata['sync_from_gateway_defaults'] = true;
                } else {
                    $metadata['sync_from_gateway_defaults'] = false;
                }
            }

            if ($method->isOnline() || in_array($code, ['cmi', 'payzone', 'stripe'], true)) {
                $method->forceFill([
                    'channel' => $defaults['channel'],
                    'behavior' => $defaults['behavior'],
                    'gateway_setting_id' => $defaults['gateway_setting_id'],
                    'sort_order' => $method->sort_order ?: $defaults['sort_order'],
                    'metadata' => $metadata,
                ])->save();

                continue;
            }

            $method->forceFill([
                'channel' => $method->channel ?: $defaults['channel'],
                'behavior' => $method->behavior ?: $defaults['behavior'],
                'sort_order' => $method->sort_order ?: $defaults['sort_order'],
                'metadata' => $metadata,
            ])->save();
        }

        $this->syncProviderState();
    }

    public function syncProviderState(): void
    {
        $providerMethods = PaymentMethod::query()
            ->with('gatewaySetting')
            ->where('behavior', PaymentMethod::BEHAVIOR_GATEWAY)
            ->get();

        foreach ($providerMethods as $method) {
            if (! $method->gatewaySetting) {
                continue;
            }

            $method->forceFill([
                'gateway_setting_id' => $method->gatewaySetting->id,
                'is_enabled' => (bool) $method->gatewaySetting->is_enabled,
                'metadata' => array_merge($method->metadata ?? [], [
                    'provider_gateway_id' => $method->gatewaySetting->gateway_id,
                ]),
            ])->save();
        }
    }

    public function resolveByCode(?string $code): ?PaymentMethod
    {
        $normalized = $this->normalizeCode($code);

        if (! $normalized) {
            return null;
        }

        return PaymentMethod::query()
            ->with(['gatewaySetting', 'shippingCarriers'])
            ->where('code', $normalized)
            ->first();
    }

    public function normalizeCode(?string $code): ?string
    {
        $code = strtolower(trim((string) $code));

        return match ($code) {
            '', 'other' => null,
            'cash_on_delivery' => 'cod',
            'offline_transfer' => 'bank_transfer',
            default => $code,
        };
    }

    public function eligibleMethods(?ShippingMethod $shippingMethod = null): EloquentCollection
    {
        $carrierId = $shippingMethod?->shipping_carrier_id;

        $methods = PaymentMethod::query()
            ->with(['gatewaySetting', 'shippingCarriers'])
            ->enabled()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(function (PaymentMethod $method) use ($carrierId): bool {
                if ($method->isGatewayBehavior()) {
                    return $method->gatewaySetting?->is_enabled ?? false;
                }

                $linkedCarriers = $method->relationLoaded('shippingCarriers')
                    ? $method->shippingCarriers
                    : collect();

                if ($linkedCarriers->isEmpty()) {
                    return true;
                }

                if (! $carrierId) {
                    return false;
                }

                return $linkedCarriers->contains('id', $carrierId);
            })
            ->values();

        return new EloquentCollection($methods->all());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function eligibleMethodOptions(?ShippingMethod $shippingMethod = null): array
    {
        return $this->eligibleMethods($shippingMethod)
            ->map(fn (PaymentMethod $method): array => $this->optionPayload($method))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function optionPayload(PaymentMethod $method): array
    {
        return [
            'id' => $method->id,
            'value' => $method->code,
            'label' => $method->checkoutLabel(),
            'title' => $method->checkoutTitle(),
            'meta' => $method->optionMeta(),
            'description' => $method->checkoutDescription(),
            'behavior' => $method->behavior,
            'channel' => $method->channel,
            'carrier_linked' => $method->hasLinkedCarriers(),
            'instructions' => $method->instructions(),
            'requires_receipt' => $method->requiresReceipt(),
            'payment_window_hours' => $method->paymentWindowHours(),
        ];
    }

    /**
     * @return array{id:int|null,code:string|null,label:string|null,behavior:string|null}
     */
    public function snapshot(?string $code): array
    {
        $method = $this->resolveByCode($code);
        $normalizedCode = $this->normalizeCode($code);

        if ($method) {
            return [
                'id' => $method->id,
                'code' => $method->code,
                'label' => $method->checkoutLabel(),
                'behavior' => $method->behavior,
            ];
        }

        return [
            'id' => null,
            'code' => $normalizedCode,
            'label' => $this->legacyLabel($normalizedCode ?? $code),
            'behavior' => $normalizedCode === 'cod' ? PaymentMethod::BEHAVIOR_COD : null,
        ];
    }

    public function legacyLabel(?string $code): ?string
    {
        $normalizedCode = $this->normalizeCode($code);

        return match ($normalizedCode) {
            'cod' => 'Cash on Delivery',
            'bank_transfer' => 'Bank Transfer',
            'cmi' => 'CMI',
            'payzone' => 'Payzone',
            'stripe' => 'Stripe',
            default => $code ? str($code)->replace(['_', '-'], ' ')->title()->value() : null,
        };
    }

    public function legacyIcon(?string $code): string
    {
        $normalizedCode = $this->normalizeCode($code);

        return match ($normalizedCode) {
            'cod' => '💵',
            'bank_transfer' => '🏦',
            'cmi', 'payzone', 'stripe' => '💳',
            default => '💰',
        };
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function metadataBoolean(array $metadata, string $key, bool $default): bool
    {
        if (! array_key_exists($key, $metadata)) {
            return $default;
        }

        $value = filter_var($metadata[$key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $value ?? $default;
    }
}
