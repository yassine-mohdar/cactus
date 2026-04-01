<?php

namespace Tests\Feature;

use App\Modules\Payments\Models\GatewaySetting;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\Payments\Services\PaymentMethodAvailabilityService;
use App\Modules\Shipping\Models\ShippingCarrier;
use App\Modules\Shipping\Models\ShippingMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutPaymentMethodsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_method_lookups_do_not_mutate_the_catalog_after_bootstrap(): void
    {
        GatewaySetting::query()->create([
            'gateway_id' => 'offline_transfer',
            'name' => 'Offline Bank Transfer',
            'is_enabled' => false,
            'mode' => 'test',
            'credentials' => [],
            'metadata' => [],
        ]);

        GatewaySetting::query()->create([
            'gateway_id' => 'stripe',
            'name' => 'Stripe',
            'is_enabled' => false,
            'mode' => 'test',
            'credentials' => [],
            'metadata' => [],
        ]);

        GatewaySetting::query()->create([
            'gateway_id' => 'cmi',
            'name' => 'CMI',
            'is_enabled' => false,
            'mode' => 'test',
            'credentials' => [],
            'metadata' => [],
        ]);

        GatewaySetting::query()->create([
            'gateway_id' => 'payzone',
            'name' => 'Payzone',
            'is_enabled' => false,
            'mode' => 'test',
            'credentials' => [],
            'metadata' => [],
        ]);

        $availability = app(PaymentMethodAvailabilityService::class);
        $availability->ensureDefaults();

        $codMethod = PaymentMethod::query()->where('code', 'cod')->firstOrFail();
        $bankTransfer = PaymentMethod::query()->where('code', 'bank_transfer')->firstOrFail();
        $codUpdatedAt = $codMethod->updated_at?->toISOString();
        $bankTransferUpdatedAt = $bankTransfer->updated_at?->toISOString();
        $paymentMethodCount = PaymentMethod::query()->count();
        $gatewaySettingCount = GatewaySetting::query()->count();

        $this->travel(5)->minutes();

        $availability->resolveByCode('cod');
        $availability->eligibleMethods();

        $codMethod->refresh();
        $bankTransfer->refresh();

        $this->assertSame($paymentMethodCount, PaymentMethod::query()->count());
        $this->assertSame($gatewaySettingCount, GatewaySetting::query()->count());
        $this->assertSame($codUpdatedAt, $codMethod->updated_at?->toISOString());
        $this->assertSame($bankTransferUpdatedAt, $bankTransfer->updated_at?->toISOString());
    }

    public function test_checkout_payment_methods_endpoint_filters_methods_by_selected_shipping_carrier(): void
    {
        $availability = app(PaymentMethodAvailabilityService::class);
        $availability->ensureDefaults();

        $amana = ShippingCarrier::query()->firstOrCreate([
            'code' => 'amana',
        ], [
            'name' => 'Amana',
            'provider' => ShippingCarrier::PROVIDER_MANUAL,
            'is_enabled' => true,
        ]);

        $fedex = ShippingCarrier::query()->create([
            'code' => 'fedex-local',
            'name' => 'FedEx',
            'provider' => ShippingCarrier::PROVIDER_MANUAL,
            'is_enabled' => true,
        ]);

        $amanaMethod = ShippingMethod::query()->create([
            'name' => 'Amana Home',
            'slug' => 'amana-home',
            'carrier' => 'Amana',
            'shipping_carrier_id' => $amana->id,
            'base_cost' => 25,
            'is_enabled' => true,
            'sort_order' => 10,
        ]);

        $fedexMethod = ShippingMethod::query()->create([
            'name' => 'FedEx Europe',
            'slug' => 'fedex-eu',
            'carrier' => 'FedEx',
            'shipping_carrier_id' => $fedex->id,
            'base_cost' => 45,
            'is_enabled' => true,
            'sort_order' => 20,
        ]);

        $customCod = PaymentMethod::query()->create([
            'code' => 'cash_on_pickup',
            'name' => 'Cash on Pickup',
            'channel' => PaymentMethod::CHANNEL_OFFLINE,
            'behavior' => PaymentMethod::BEHAVIOR_COD,
            'is_enabled' => true,
            'sort_order' => 90,
            'metadata' => [
                'method_label' => 'Cash on Pickup',
                'checkout_title' => 'Pay when the carrier arrives',
                'instructions' => 'The carrier collects cash on handover.',
            ],
        ]);
        $customCod->shippingCarriers()->sync([$amana->id]);

        GatewaySetting::query()->where('gateway_id', 'stripe')->update([
            'is_enabled' => false,
        ]);
        GatewaySetting::query()->where('gateway_id', 'payzone')->update([
            'is_enabled' => true,
        ]);
        $availability->syncProviderState();

        $amanaResponse = $this->getJson(route('api.checkout.payment-methods', [
            'shipping_method_id' => $amanaMethod->id,
        ]));

        $amanaResponse->assertOk()
            ->assertJsonPath('data.0.value', 'cod')
            ->assertJsonFragment(['value' => 'cash_on_pickup'])
            ->assertJsonFragment(['value' => 'bank_transfer'])
            ->assertJsonFragment(['value' => 'payzone'])
            ->assertJsonMissing(['value' => 'stripe']);

        $fedexResponse = $this->getJson(route('api.checkout.payment-methods', [
            'shipping_method_id' => $fedexMethod->id,
        ]));

        $fedexResponse->assertOk()
            ->assertJsonMissing(['value' => 'cash_on_pickup'])
            ->assertJsonFragment(['value' => 'bank_transfer'])
            ->assertJsonFragment(['value' => 'payzone']);
    }
}
