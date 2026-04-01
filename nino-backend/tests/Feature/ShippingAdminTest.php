<?php

namespace Tests\Feature;

use App\Models\User;
use App\Livewire\Admin\Orders\OrderCreate as OrderCreateComponent;
use App\Modules\Catalog\Models\Product;
use App\Modules\Finance\Enums\RefundStatus;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\IAM\Notifications\CustomerPasswordSetupNotification;
use App\Modules\Notifications\Enums\NotificationChannel;
use App\Modules\Notifications\Enums\NotificationEvent;
use App\Modules\Notifications\Models\NotificationTemplate;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Models\GatewaySetting;
use App\Modules\Promotions\Enums\CouponType;
use App\Modules\Promotions\Models\Coupon;
use App\Modules\Promotions\Models\CouponUsage;
use App\Modules\Shipping\Enums\ShipmentStatus;
use App\Modules\Shipping\Models\Shipment;
use App\Modules\Shipping\Models\ShipmentStatusHistory;
use App\Modules\Shipping\Models\ShippingCarrier;
use App\Modules\Shipping\Models\ShippingCarrierDistrict;
use App\Modules\Shipping\Models\ShippingMethod;
use App\Modules\Shipping\Models\ShippingMethodDistrictOverride;
use App\Modules\Settings\Services\SettingsService;
use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Livewire\Livewire;

class ShippingAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_shipments_index_renders_without_group_by_failures_for_named_and_blank_carriers(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = $this->makeShippingUser();

        $customer = User::factory()->create([
            'type' => 'customer',
            'status' => 'active',
        ]);

        $shippingMethod = ShippingMethod::create([
            'name' => 'Standard Delivery',
            'slug' => 'standard-delivery',
            'carrier' => 'Amana',
            'base_cost' => 25,
            'estimated_days' => '2-4',
            'is_enabled' => true,
        ]);

        Shipment::create([
            'order_id' => $this->makeOrder($customer, 'ORD-QA-001')->id,
            'shipping_method_id' => $shippingMethod->id,
            'status' => ShipmentStatus::READY_TO_SHIP->value,
            'carrier_name' => 'Amana',
            'tracking_number' => 'TRK-001',
        ]);

        Shipment::create([
            'order_id' => $this->makeOrder($customer, 'ORD-QA-002')->id,
            'shipping_method_id' => $shippingMethod->id,
            'status' => ShipmentStatus::PACKED->value,
            'carrier_name' => '',
            'tracking_number' => 'TRK-002',
        ]);

        Shipment::create([
            'order_id' => $this->makeOrder($customer, 'ORD-QA-003')->id,
            'shipping_method_id' => $shippingMethod->id,
            'status' => ShipmentStatus::DISPATCHED->value,
            'carrier_name' => null,
            'tracking_number' => 'TRK-003',
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('admin.shipping.shipments.index'));

        $response->assertOk();
        $response->assertSee('Carrier Load');
        $response->assertSee('Amana');
        $response->assertSee('Unassigned');
    }

    public function test_shipping_method_creation_uses_configured_carrier_and_sla_defaults_when_fields_are_blank(): void
    {
        $this->activateAdminTheme('nino-v2');

        app(SettingsService::class)->setMany('shipping', [
            ['key' => 'default_carrier_name', 'value' => 'DHL', 'type' => 'string'],
            ['key' => 'default_estimated_days', 'value' => '1-2 business days', 'type' => 'string'],
        ]);

        $staffUser = $this->makeShippingUser(canManageCarriers: true);

        $this->actingAs($staffUser)
            ->post(route('admin.shipping.methods.store'), [
                'name' => 'Express Delivery',
                'carrier' => '',
                'description' => 'Priority delivery',
                'base_cost' => 65,
                'free_shipping_threshold' => null,
                'estimated_days' => '',
                'sort_order' => 10,
            ])
            ->assertRedirect(route('admin.shipping.methods.index'));

        $this->assertDatabaseHas('shipping_methods', [
            'slug' => 'express-delivery',
            'carrier' => 'DHL',
            'estimated_days' => '1-2 business days',
        ]);
    }

    public function test_shipping_method_can_store_allowed_country_coverage(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = $this->makeShippingUser(canManageCarriers: true);

        $this->actingAs($staffUser)
            ->post(route('admin.shipping.methods.store'), [
                'name' => 'Europe Road',
                'carrier' => 'DHL',
                'description' => 'Europe only',
                'base_cost' => 65,
                'estimated_days' => '4-7 business days',
                'metadata' => [
                    'allowed_countries' => ['FR', 'BE', 'DE'],
                ],
            ])
            ->assertRedirect(route('admin.shipping.methods.index'));

        $method = ShippingMethod::query()->where('slug', 'europe-road')->firstOrFail();

        $this->assertSame(['BE', 'DE', 'FR'], $method->allowedCountryCodes());
    }

    public function test_carrier_registry_can_create_sendit_carrier_and_sync_districts(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = $this->makeShippingUser(canManageCarriers: true);

        $this->actingAs($staffUser)
            ->post(route('admin.shipping.carriers.store'), [
                'name' => 'Sendit',
                'code' => 'sendit',
                'provider' => 'sendit',
                'tracking_url_template' => 'https://app.sendit.ma/track/{tracking_number}',
                'is_enabled' => 1,
                'credentials' => [
                    'public_key' => 'public-demo',
                    'secret_key' => 'secret-demo',
                ],
                'settings' => [
                    'default_label_format' => 1,
                ],
                'metadata' => [
                    'notes' => 'Primary API carrier',
                ],
            ])
            ->assertRedirect();

        $carrier = ShippingCarrier::query()->where('code', 'sendit')->firstOrFail();

        $this->assertNull(data_get($carrier->settings ?? [], 'pickup_district_id'));

        Http::fake([
            'https://app.sendit.ma/api/v1/login' => Http::response([
                'data' => ['token' => 'sendit-token'],
            ]),
            'https://app.sendit.ma/api/v1/districts*' => Http::response([
                'data' => [
                    [
                        'id' => 501,
                        'ville' => 'Casablanca',
                        'name' => 'Maarif',
                        'arabic_name' => 'المعاريف',
                        'price' => 25,
                        'delais' => '24h',
                        'pickup_district' => true,
                    ],
                ],
                'next_page_url' => null,
            ]),
        ]);

        $this->actingAs($staffUser)
            ->post(route('admin.shipping.carriers.sync-districts', $carrier))
            ->assertRedirect(route('admin.shipping.carriers.edit', $carrier));

        $this->assertDatabaseHas('shipping_carrier_districts', [
            'shipping_carrier_id' => $carrier->id,
            'external_id' => '501',
            'city' => 'Casablanca',
            'district_name' => 'Maarif',
        ]);
    }

    public function test_carrier_management_requires_manage_carriers_permission(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = $this->makeShippingUser();

        $this->actingAs($staffUser)
            ->get(route('admin.shipping.carriers.create'))
            ->assertForbidden();
    }

    public function test_shipping_method_form_lists_api_district_pricing_metadata_for_api_carriers(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = $this->makeShippingUser(canManageCarriers: true);
        $carrier = ShippingCarrier::query()->where('code', 'sendit')->firstOrFail();
        $carrier->update([
            'name' => 'Sendit',
            'provider' => ShippingCarrier::PROVIDER_SENDIT,
            'is_enabled' => true,
            'metadata' => ['districts_synced_at' => 'Mar 29, 2026 17:40'],
        ]);

        ShippingCarrierDistrict::create([
            'shipping_carrier_id' => $carrier->id,
            'external_id' => '1',
            'city' => 'Casablanca',
            'district_name' => 'Casablanca - Al fida',
            'price' => 19,
            'estimated_delivery' => '24h',
            'is_pickup' => false,
            'is_active' => true,
        ]);

        $this->actingAs($staffUser)
            ->get(route('admin.shipping.methods.create'))
            ->assertOk()
            ->assertSee('API Pricing and SLA')
            ->assertSee('Forced Price')
            ->assertSee('Last Synced')
            ->assertSee('Casablanca - Al fida');
    }

    public function test_sendit_carrier_edit_uses_full_synced_district_catalog_for_pickup_selection(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = $this->makeShippingUser(canManageCarriers: true);
        $carrier = ShippingCarrier::query()->where('code', 'sendit')->firstOrFail();

        ShippingCarrierDistrict::create([
            'shipping_carrier_id' => $carrier->id,
            'external_id' => '1001',
            'city' => 'Casablanca',
            'district_name' => 'Maarif',
            'price' => 25,
            'estimated_delivery' => '24h',
            'is_pickup' => false,
            'is_active' => true,
        ]);

        ShippingCarrierDistrict::create([
            'shipping_carrier_id' => $carrier->id,
            'external_id' => '1002',
            'city' => 'Casablanca',
            'district_name' => 'Casablanca - Al fida',
            'price' => 25,
            'estimated_delivery' => '24h',
            'is_pickup' => false,
            'is_active' => true,
        ]);

        $this->actingAs($staffUser)
            ->get(route('admin.shipping.carriers.edit', $carrier))
            ->assertOk()
            ->assertSee('Maarif')
            ->assertSee('Casablanca - Al fida');
    }

    public function test_api_shipping_method_stores_forced_district_prices_and_ignores_manual_pricing_fields(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = $this->makeShippingUser(canManageCarriers: true);
        $carrier = ShippingCarrier::query()->where('code', 'sendit')->firstOrFail();
        $carrier->update([
            'name' => 'Sendit',
            'provider' => ShippingCarrier::PROVIDER_SENDIT,
            'is_enabled' => true,
        ]);

        $district = ShippingCarrierDistrict::create([
            'shipping_carrier_id' => $carrier->id,
            'external_id' => '1',
            'city' => 'Casablanca',
            'district_name' => 'Casablanca - Al fida',
            'price' => 19,
            'estimated_delivery' => '24h',
            'is_pickup' => false,
            'is_active' => true,
        ]);

        $this->actingAs($staffUser)
            ->post(route('admin.shipping.methods.store'), [
                'name' => 'Sendit City Delivery',
                'shipping_carrier_id' => $carrier->id,
                'description' => 'API-managed delivery method',
                'base_cost' => 999,
                'free_shipping_threshold' => 500,
                'estimated_days' => 'never use this',
                'sort_order' => 3,
                'district_overrides' => [
                    '1' => 25,
                ],
            ])
            ->assertRedirect(route('admin.shipping.methods.index'));

        $method = ShippingMethod::query()->where('slug', 'sendit-city-delivery')->firstOrFail();

        $this->assertSame($carrier->id, $method->shipping_carrier_id);
        $this->assertSame('0.00', number_format((float) $method->base_cost, 2, '.', ''));
        $this->assertNull($method->free_shipping_threshold);
        $this->assertNull($method->estimated_days);

        $override = ShippingMethodDistrictOverride::query()
            ->where('shipping_method_id', $method->id)
            ->where('shipping_carrier_district_id', $district->id)
            ->first();

        $this->assertNotNull($override);
        $this->assertSame('25.00', number_format((float) $override->forced_price, 2, '.', ''));
    }

    public function test_manual_order_livewire_uses_enabled_shipping_method_and_persists_selection(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = User::factory()->staff()->create();
        $customer = User::factory()->customer()->create([
            'status' => 'active',
        ]);

        $carrier = ShippingCarrier::query()->where('code', 'amana')->firstOrFail();
        $carrier->update([
            'name' => 'Amana',
            'provider' => ShippingCarrier::PROVIDER_MANUAL,
            'is_enabled' => true,
        ]);

        $standard = ShippingMethod::create([
            'name' => 'Standard Delivery',
            'slug' => 'standard-delivery',
            'carrier' => 'Amana',
            'shipping_carrier_id' => $carrier->id,
            'base_cost' => 30,
            'estimated_days' => '2-4 days',
            'is_enabled' => true,
            'sort_order' => 10,
        ]);

        $express = ShippingMethod::create([
            'name' => 'Express Delivery',
            'slug' => 'express-delivery',
            'carrier' => 'Amana',
            'shipping_carrier_id' => $carrier->id,
            'base_cost' => 55,
            'free_shipping_threshold' => 250,
            'estimated_days' => '24h',
            'is_enabled' => true,
            'sort_order' => 1,
        ]);

        $product = Product::factory()->published()->simple()->create([
            'name' => 'Nino Bear',
            'price' => 100,
            'sku' => 'NINO-BEAR-01',
        ]);

        $this->actingAs($staffUser);

        Livewire::test(OrderCreateComponent::class)
            ->set('customer_id', (string) $customer->id)
            ->set('items', [[
                'product_id' => $product->id,
                'variant_id' => null,
                'quantity' => 3,
                'price' => 100,
                'name' => 'Nino Bear',
            ]])
            ->set('shipping_method_id', (string) $express->id)
            ->set('shipping_address.first_name', 'Nadia')
            ->set('shipping_address.address_line_1', '12 Atlas Street')
            ->set('shipping_address.city', 'Casablanca')
            ->assertSet('shipping_method_id', (string) $express->id)
            ->assertSee('Express Delivery')
            ->assertSee('Standard Delivery')
            ->call('save')
            ->assertHasNoErrors();

        $order = Order::query()->latest('id')->firstOrFail();

        $this->assertSame($express->id, $order->shipping_method_id);
        $this->assertSame('Express Delivery', $order->shipping_method);
        $this->assertSame('0.00', number_format((float) $order->shipping_total, 2, '.', ''));
        $this->assertSame('300.00', number_format((float) $order->grand_total, 2, '.', ''));
    }

    public function test_manual_order_uses_enabled_payment_methods_instead_of_hardcoded_options(): void
    {
        $this->activateAdminTheme('nino-v2');

        GatewaySetting::query()->delete();

        GatewaySetting::create([
            'gateway_id' => 'offline_transfer',
            'name' => 'Offline Bank Transfer',
            'is_enabled' => true,
            'mode' => 'live',
            'credentials' => [],
            'metadata' => [
                'method_label' => 'Bank Transfer',
            ],
        ]);

        GatewaySetting::create([
            'gateway_id' => 'stripe',
            'name' => 'Stripe',
            'is_enabled' => true,
            'mode' => 'test',
            'credentials' => [],
            'metadata' => [],
        ]);

        GatewaySetting::create([
            'gateway_id' => 'cmi',
            'name' => 'CMI',
            'is_enabled' => false,
            'mode' => 'live',
            'credentials' => [],
            'metadata' => [],
        ]);

        $staffUser = User::factory()->staff()->create();

        $this->actingAs($staffUser);

        Livewire::test(OrderCreateComponent::class)
            ->assertSee('Cash on Delivery')
            ->assertSee('Bank Transfer')
            ->assertSee('Stripe')
            ->assertDontSee('CMI')
            ->set('payment_method', 'stripe')
            ->assertSet('payment_method', 'stripe');
    }

    public function test_manual_order_checkout_summary_marks_the_rendered_selected_payment_method(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = User::factory()->staff()->create();

        $this->actingAs($staffUser);

        $component = Livewire::test(OrderCreateComponent::class);

        $component
            ->set('payment_method', 'cod')
            ->assertSee('Carrier-collected payment')
            ->assertDontSee('Complete the transfer manually and wait for finance verification.');

        $component->set('payment_method', 'bank_transfer');

        $component->assertSet('payment_method', 'bank_transfer');

        $this->assertStringContainsString(
            'option value="bank_transfer" selected',
            $component->html(),
        );
    }

    public function test_manual_order_can_inline_create_and_link_a_new_customer(): void
    {
        $this->activateAdminTheme('nino-v2');

        Notification::fake();

        $staffUser = User::factory()->staff()->create();

        $this->actingAs($staffUser);

        Livewire::test(OrderCreateComponent::class)
            ->set('shipping_address.country', 'MA')
            ->set('shipping_address.first_name', 'Sara')
            ->set('shipping_address.last_name', 'El Idrissi')
            ->set('shipping_phone_local', '0612345678')
            ->call('startCreatingCustomer')
            ->set('new_customer.first_name', 'Sara')
            ->set('new_customer.last_name', 'El Idrissi')
            ->set('new_customer.email', 'sara.inline@example.test')
            ->set('new_customer.phone', '0612345678')
            ->call('createCustomer')
            ->assertSet('show_create_customer_form', false)
            ->assertSet('search_customer', 'Sara El Idrissi (sara.inline@example.test)');

        $customer = User::query()->where('email', 'sara.inline@example.test')->firstOrFail();

        $this->assertTrue($customer->isCustomer());
        $this->assertSame('+212612345678', $customer->phone);

        Notification::assertSentTo($customer, CustomerPasswordSetupNotification::class);
    }

    public function test_manual_order_inline_customer_creation_links_existing_customer_when_email_already_exists(): void
    {
        $this->activateAdminTheme('nino-v2');

        Notification::fake();

        $staffUser = User::factory()->staff()->create();
        $customer = User::factory()->customer()->create([
            'first_name' => 'Mina',
            'last_name' => 'Alaoui',
            'email' => 'mina.existing@example.test',
        ]);

        $this->actingAs($staffUser);

        Livewire::test(OrderCreateComponent::class)
            ->call('startCreatingCustomer')
            ->set('new_customer.first_name', 'Mina')
            ->set('new_customer.last_name', 'Alaoui')
            ->set('new_customer.email', 'mina.existing@example.test')
            ->call('createCustomer')
            ->assertSet('customer_id', $customer->id)
            ->assertSet('show_create_customer_form', false);

        $this->assertSame(1, User::query()->where('email', 'mina.existing@example.test')->count());
    }

    public function test_manual_order_customer_search_matches_phone_and_renders_result(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = User::factory()->staff()->create();
        $customer = User::factory()->customer()->create([
            'first_name' => 'Nadia',
            'last_name' => 'Phone',
            'phone' => '+212612000123',
            'email' => 'nadia.phone@example.test',
        ]);

        $this->actingAs($staffUser);

        Livewire::test(OrderCreateComponent::class)
            ->set('search_customer', '612000123')
            ->assertSee($customer->full_name)
            ->assertSee($customer->email);
    }

    public function test_manual_order_livewire_filters_shipping_methods_by_shipping_country(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = User::factory()->staff()->create();
        $customer = User::factory()->customer()->create([
            'status' => 'active',
        ]);

        $carrier = ShippingCarrier::query()->where('code', 'amana')->firstOrFail();
        $carrier->update([
            'name' => 'Amana',
            'provider' => ShippingCarrier::PROVIDER_MANUAL,
            'is_enabled' => true,
        ]);

        $moroccoMethod = ShippingMethod::create([
            'name' => 'Standard Morocco',
            'slug' => 'standard-morocco',
            'carrier' => 'Amana',
            'shipping_carrier_id' => $carrier->id,
            'base_cost' => 30,
            'estimated_days' => '2-4 days',
            'is_enabled' => true,
            'metadata' => ['allowed_countries' => ['MA']],
        ]);

        $europeMethod = ShippingMethod::create([
            'name' => 'Europe Parcel',
            'slug' => 'europe-parcel',
            'carrier' => 'Amana',
            'shipping_carrier_id' => $carrier->id,
            'base_cost' => 90,
            'estimated_days' => '5-8 days',
            'is_enabled' => true,
            'metadata' => ['allowed_countries' => ['FR', 'BE']],
        ]);

        $globalMethod = ShippingMethod::create([
            'name' => 'Global Courier',
            'slug' => 'global-courier',
            'carrier' => 'Amana',
            'shipping_carrier_id' => $carrier->id,
            'base_cost' => 120,
            'estimated_days' => '5-10 days',
            'is_enabled' => true,
            'metadata' => [],
        ]);

        $product = Product::factory()->published()->simple()->create([
            'name' => 'Nino Lamp',
            'price' => 100,
            'sku' => 'NINO-LAMP-01',
        ]);

        $this->actingAs($staffUser);

        Livewire::test(OrderCreateComponent::class)
            ->set('customer_id', (string) $customer->id)
            ->set('items', [[
                'product_id' => $product->id,
                'variant_id' => null,
                'quantity' => 1,
                'price' => 100,
                'name' => 'Nino Lamp',
            ]])
            ->set('shipping_address.country', 'FR')
            ->assertSee('Europe Parcel')
            ->assertSee('Global Courier')
            ->assertDontSee('Standard Morocco')
            ->set('shipping_address.country', 'MA')
            ->assertSee('Standard Morocco')
            ->assertSee('Global Courier')
            ->assertDontSee('Europe Parcel');

        $this->assertTrue($moroccoMethod->supportsCountry('MA'));
        $this->assertFalse($moroccoMethod->supportsCountry('FR'));
        $this->assertTrue($globalMethod->supportsCountry('FR'));
    }

    public function test_manual_order_livewire_uses_api_carrier_district_rate_and_forced_price_override(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = User::factory()->staff()->create();
        $customer = User::factory()->customer()->create([
            'status' => 'active',
        ]);

        $carrier = ShippingCarrier::query()->where('code', 'sendit')->firstOrFail();
        $carrier->update([
            'name' => 'Sendit',
            'provider' => ShippingCarrier::PROVIDER_SENDIT,
            'is_enabled' => true,
        ]);

        $district = ShippingCarrierDistrict::create([
            'shipping_carrier_id' => $carrier->id,
            'external_id' => '777',
            'city' => 'Casablanca',
            'district_name' => 'Casablanca - Al fida',
            'price' => 19,
            'estimated_delivery' => '24h',
            'is_pickup' => false,
            'is_active' => true,
        ]);

        ShippingCarrierDistrict::create([
            'shipping_carrier_id' => $carrier->id,
            'external_id' => '778',
            'city' => 'Casablanca',
            'district_name' => 'Ben msik',
            'price' => 22,
            'estimated_delivery' => '24h',
            'is_pickup' => false,
            'is_active' => true,
        ]);

        $method = ShippingMethod::create([
            'name' => 'Standard Maroc',
            'slug' => 'standard-maroc',
            'carrier' => 'Sendit',
            'shipping_carrier_id' => $carrier->id,
            'base_cost' => 0,
            'estimated_days' => null,
            'is_enabled' => true,
            'sort_order' => 1,
        ]);

        ShippingMethodDistrictOverride::create([
            'shipping_method_id' => $method->id,
            'shipping_carrier_district_id' => $district->id,
            'forced_price' => 25,
        ]);

        $product = Product::factory()->published()->simple()->create([
            'name' => 'Nino Car',
            'price' => 100,
            'sku' => 'NINO-CAR-01',
        ]);

        $this->actingAs($staffUser);

        Livewire::test(OrderCreateComponent::class)
            ->set('customer_id', (string) $customer->id)
            ->set('items', [[
                'product_id' => $product->id,
                'variant_id' => null,
                'quantity' => 1,
                'price' => 100,
                'name' => 'Nino Car',
            ]])
            ->set('shipping_method_id', (string) $method->id)
            ->set('shipping_address.first_name', 'Nadia')
            ->set('shipping_address.address_line_1', '12 Atlas Street')
            ->assertSee('Casablanca - Al fida')
            ->assertSee('Casablanca - Ben msik')
            ->set('shipping_destination_id', (string) $district->id)
            ->assertSet('shipping_address.city', 'Casablanca')
            ->assertSet('shipping_address.state', 'Casablanca - Al fida')
            ->call('save')
            ->assertHasNoErrors();

        $order = Order::query()->latest('id')->firstOrFail();
        $shippingAddress = $order->addresses()->where('type', 'shipping')->firstOrFail();

        $this->assertSame($method->id, $order->shipping_method_id);
        $this->assertSame('25.00', number_format((float) $order->shipping_total, 2, '.', ''));
        $this->assertSame('125.00', number_format((float) $order->grand_total, 2, '.', ''));
        $this->assertSame('Casablanca', $shippingAddress->city);
        $this->assertSame('Casablanca - Al fida', $shippingAddress->state);
    }

    public function test_manual_order_preserves_or_derives_destination_when_switching_to_api_shipping_method(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = User::factory()->staff()->create();

        $manualCarrier = ShippingCarrier::query()->where('code', 'amana')->firstOrFail();
        $manualCarrier->update([
            'name' => 'Amana',
            'provider' => ShippingCarrier::PROVIDER_MANUAL,
            'is_enabled' => true,
        ]);

        $apiCarrier = ShippingCarrier::query()->where('code', 'sendit')->firstOrFail();
        $apiCarrier->update([
            'name' => 'Sendit',
            'provider' => ShippingCarrier::PROVIDER_SENDIT,
            'is_enabled' => true,
        ]);

        $district = ShippingCarrierDistrict::create([
            'shipping_carrier_id' => $apiCarrier->id,
            'external_id' => '777',
            'city' => 'Casablanca',
            'district_name' => 'Casablanca - Ben msik',
            'price' => 19,
            'estimated_delivery' => '24h',
            'is_pickup' => false,
            'is_active' => true,
        ]);

        $manualMethod = ShippingMethod::create([
            'name' => 'Manual Morocco',
            'slug' => 'manual-morocco',
            'carrier' => 'Amana',
            'shipping_carrier_id' => $manualCarrier->id,
            'base_cost' => 30,
            'estimated_days' => '2-4 days',
            'is_enabled' => true,
            'sort_order' => 1,
        ]);

        $apiMethod = ShippingMethod::create([
            'name' => 'Sendit Standard',
            'slug' => 'sendit-standard',
            'carrier' => 'Sendit',
            'shipping_carrier_id' => $apiCarrier->id,
            'base_cost' => 0,
            'estimated_days' => null,
            'is_enabled' => true,
            'sort_order' => 10,
        ]);

        $this->actingAs($staffUser);

        Livewire::test(OrderCreateComponent::class)
            ->assertSet('shipping_method_id', (string) $manualMethod->id)
            ->set('shipping_address.city', 'Casablanca')
            ->set('shipping_address.state', 'Casablanca - Ben msik')
            ->set('shipping_method_id', (string) $apiMethod->id)
            ->assertSet('shipping_destination_id', (string) $district->id)
            ->assertSet('shipping_address.city', 'Casablanca')
            ->assertSet('shipping_address.state', 'Casablanca - Ben msik')
            ->assertSee('Sendit Standard');
    }

    public function test_manual_order_uses_country_driven_global_address_selectors_outside_morocco(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = User::factory()->staff()->create();

        $manualCarrier = ShippingCarrier::query()->where('code', 'amana')->firstOrFail();
        $manualCarrier->update([
            'name' => 'Amana',
            'provider' => ShippingCarrier::PROVIDER_MANUAL,
            'is_enabled' => true,
        ]);

        $apiCarrier = ShippingCarrier::query()->where('code', 'sendit')->firstOrFail();
        $apiCarrier->update([
            'name' => 'Sendit',
            'provider' => ShippingCarrier::PROVIDER_SENDIT,
            'is_enabled' => true,
        ]);

        ShippingCarrierDistrict::create([
            'shipping_carrier_id' => $apiCarrier->id,
            'external_id' => '777',
            'city' => 'Casablanca',
            'district_name' => 'Casablanca - Al fida',
            'price' => 19,
            'estimated_delivery' => '24h',
            'is_pickup' => false,
            'is_active' => true,
        ]);

        ShippingMethod::create([
            'name' => 'US Ground',
            'slug' => 'us-ground',
            'carrier' => 'Amana',
            'shipping_carrier_id' => $manualCarrier->id,
            'base_cost' => 40,
            'estimated_days' => '5-7 business days',
            'is_enabled' => true,
            'metadata' => ['allowed_countries' => ['US']],
        ]);

        $this->actingAs($staffUser);

        Livewire::test(OrderCreateComponent::class)
            ->set('shipping_address.country', 'US')
            ->assertSee('State / Province')
            ->assertSee('California')
            ->assertDontSee('Destination District')
            ->assertDontSee('Casablanca - Al fida')
            ->set('shipping_address.state', 'California')
            ->assertSee('Los Angeles');
    }

    public function test_manual_order_supports_morocco_shipping_and_global_billing_modes_together(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = User::factory()->staff()->create();

        $apiCarrier = ShippingCarrier::query()->where('code', 'sendit')->firstOrFail();
        $apiCarrier->update([
            'name' => 'Sendit',
            'provider' => ShippingCarrier::PROVIDER_SENDIT,
            'is_enabled' => true,
        ]);

        ShippingCarrierDistrict::create([
            'shipping_carrier_id' => $apiCarrier->id,
            'external_id' => '778',
            'city' => 'Casablanca',
            'district_name' => 'Casablanca - Ben msik',
            'price' => 25,
            'estimated_delivery' => '24h',
            'is_pickup' => false,
            'is_active' => true,
        ]);

        ShippingMethod::create([
            'name' => 'Sendit Standard',
            'slug' => 'sendit-standard-billing-mixed',
            'carrier' => 'Sendit',
            'shipping_carrier_id' => $apiCarrier->id,
            'base_cost' => 0,
            'estimated_days' => null,
            'is_enabled' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($staffUser);

        Livewire::test(OrderCreateComponent::class)
            ->set('same_as_shipping', false)
            ->set('billing_address.country', 'US')
            ->assertSee('Destination District')
            ->assertSee('California')
            ->set('billing_address.state', 'California')
            ->assertSee('Los Angeles');
    }

    public function test_manual_order_checkout_status_guides_the_operator_through_required_steps(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = User::factory()->staff()->create();
        $customer = User::factory()->customer()->create([
            'status' => 'active',
        ]);

        $carrier = ShippingCarrier::query()->where('code', 'amana')->firstOrFail();
        $carrier->update([
            'name' => 'Amana',
            'provider' => ShippingCarrier::PROVIDER_MANUAL,
            'is_enabled' => true,
        ]);

        ShippingMethod::create([
            'name' => 'Global Courier',
            'slug' => 'global-courier-status',
            'carrier' => 'Amana',
            'shipping_carrier_id' => $carrier->id,
            'base_cost' => 50,
            'estimated_days' => '5-8 business days',
            'is_enabled' => true,
            'sort_order' => 1,
        ]);

        $product = Product::factory()->published()->simple()->create([
            'name' => 'Nino Plane',
            'price' => 100,
            'sku' => 'NINO-PLANE-01',
        ]);

        $this->actingAs($staffUser);

        Livewire::test(OrderCreateComponent::class)
            ->assertSee('Link or create customer')
            ->set('customer_id', (string) $customer->id)
            ->assertSee('Add basket items')
            ->set('items', [[
                'product_id' => $product->id,
                'variant_id' => null,
                'quantity' => 1,
                'price' => 100,
                'name' => 'Nino Plane',
            ]])
            ->assertSee('Add recipient name')
            ->set('shipping_address.first_name', 'Nadia')
            ->assertSee('Add street address')
            ->set('shipping_address.address_line_1', '12 Atlas Street')
            ->assertSee('District required')
            ->set('shipping_address.country', 'US')
            ->assertSee('State required')
            ->set('shipping_address.state', 'California')
            ->assertSee('City required')
            ->set('shipping_address.city', 'Los Angeles')
            ->assertSee('Ready to place order');
    }

    public function test_manual_order_checkout_summary_reports_pending_api_district_rates(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = User::factory()->staff()->create();
        $customer = User::factory()->customer()->create([
            'status' => 'active',
        ]);

        $carrier = ShippingCarrier::query()->where('code', 'sendit')->firstOrFail();
        $carrier->update([
            'name' => 'Sendit',
            'provider' => ShippingCarrier::PROVIDER_SENDIT,
            'is_enabled' => true,
        ]);

        ShippingMethod::create([
            'name' => 'Standard Maroc',
            'slug' => 'standard-maroc-pending-status',
            'carrier' => 'Sendit',
            'shipping_carrier_id' => $carrier->id,
            'base_cost' => 0,
            'estimated_days' => null,
            'is_enabled' => true,
            'sort_order' => 1,
        ]);

        $product = Product::factory()->published()->simple()->create([
            'name' => 'Nino Rocket',
            'price' => 120,
            'sku' => 'NINO-ROCKET-01',
        ]);

        $this->actingAs($staffUser);

        Livewire::test(OrderCreateComponent::class)
            ->set('customer_id', (string) $customer->id)
            ->set('items', [[
                'product_id' => $product->id,
                'variant_id' => null,
                'quantity' => 1,
                'price' => 120,
                'name' => 'Nino Rocket',
            ]])
            ->set('shipping_address.first_name', 'Nadia')
            ->set('shipping_address.address_line_1', '12 Atlas Street')
            ->assertSee('Pending district')
            ->assertSee('Choose the delivery district to unlock API pricing where needed.');
    }

    public function test_manual_order_summary_and_method_cards_show_free_when_threshold_is_reached(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = User::factory()->staff()->create();
        $customer = User::factory()->customer()->create([
            'status' => 'active',
        ]);

        $carrier = ShippingCarrier::query()->where('code', 'amana')->firstOrFail();
        $carrier->update([
            'name' => 'Amana',
            'provider' => ShippingCarrier::PROVIDER_MANUAL,
            'is_enabled' => true,
        ]);

        ShippingMethod::create([
            'name' => 'Threshold Courier',
            'slug' => 'threshold-courier-free-label',
            'carrier' => 'Amana',
            'shipping_carrier_id' => $carrier->id,
            'base_cost' => 30,
            'free_shipping_threshold' => 50,
            'estimated_days' => '8',
            'is_enabled' => true,
            'sort_order' => 1,
        ]);

        $product = Product::factory()->published()->simple()->create([
            'name' => 'Nino Scooter',
            'price' => 100,
            'sku' => 'NINO-SCOOTER-01',
        ]);

        $this->actingAs($staffUser);

        Livewire::test(OrderCreateComponent::class)
            ->set('customer_id', (string) $customer->id)
            ->set('items', [[
                'product_id' => $product->id,
                'variant_id' => null,
                'quantity' => 1,
                'price' => 100,
                'name' => 'Nino Scooter',
            ]])
            ->set('shipping_address.first_name', 'Nadia')
            ->set('shipping_address.address_line_1', '12 Atlas Street')
            ->set('shipping_address.country', 'US')
            ->set('shipping_address.state', 'California')
            ->set('shipping_address.city', 'Los Angeles')
            ->assertSee('Free')
            ->assertSee('8 business days')
            ->assertSee('Ready to place order');
    }

    public function test_manual_order_can_apply_coupon_and_persist_discount_usage(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = User::factory()->staff()->create();
        $customer = User::factory()->customer()->create([
            'status' => 'active',
        ]);

        $carrier = ShippingCarrier::query()->where('code', 'amana')->firstOrFail();
        $carrier->update([
            'name' => 'Amana',
            'provider' => ShippingCarrier::PROVIDER_MANUAL,
            'is_enabled' => true,
        ]);

        $method = ShippingMethod::create([
            'name' => 'Standard Delivery',
            'slug' => 'standard-delivery-coupon',
            'carrier' => 'Amana',
            'shipping_carrier_id' => $carrier->id,
            'base_cost' => 30,
            'estimated_days' => '2-4 business days',
            'is_enabled' => true,
            'sort_order' => 1,
            'metadata' => ['allowed_countries' => ['US']],
        ]);

        $product = Product::factory()->published()->simple()->create([
            'name' => 'Nino Bear',
            'price' => 100,
            'sku' => 'NINO-BEAR-01',
        ]);

        $coupon = Coupon::create([
            'code' => 'SAVE15',
            'name' => 'Save 15',
            'type' => CouponType::FIXED,
            'value' => 15,
            'is_active' => true,
            'usage_count' => 0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        $this->actingAs($staffUser);

        Livewire::test(OrderCreateComponent::class)
            ->set('customer_id', (string) $customer->id)
            ->set('items', [[
                'product_id' => $product->id,
                'variant_id' => null,
                'quantity' => 1,
                'price' => 100,
                'name' => 'Nino Bear',
                'sku' => 'NINO-BEAR-01',
            ]])
            ->set('shipping_method_id', (string) $method->id)
            ->set('shipping_address.first_name', 'Nadia')
            ->set('shipping_address.address_line_1', '12 Atlas Street')
            ->set('shipping_address.country', 'US')
            ->set('shipping_address.state', 'California')
            ->set('shipping_address.city', 'Los Angeles')
            ->set('coupon_code', 'SAVE15')
            ->call('applyCoupon')
            ->assertSet('applied_coupon_code', 'SAVE15')
            ->call('save')
            ->assertHasNoErrors();

        $order = Order::query()->latest('id')->firstOrFail();

        $this->assertSame($coupon->id, CouponUsage::query()->latest('id')->firstOrFail()->coupon_id);
        $this->assertSame('15.00', number_format((float) $order->discount_total, 2, '.', ''));
        $this->assertSame('115.00', number_format((float) $order->grand_total, 2, '.', ''));
    }

    public function test_sendit_delivery_creation_and_status_sync_store_external_data_and_map_status(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = $this->makeShippingUser();
        $customer = User::factory()->customer()->create([
            'status' => 'active',
            'phone' => '+212600000001',
        ]);

        $carrier = ShippingCarrier::query()->where('code', 'sendit')->firstOrFail();
        $carrier->update([
            'name' => 'Sendit',
            'provider' => ShippingCarrier::PROVIDER_SENDIT,
            'is_enabled' => true,
            'credentials' => [
                'public_key' => 'public-demo',
                'secret_key' => 'secret-demo',
            ],
            'settings' => [
                'pickup_district_id' => 901,
                'default_label_format' => 0,
            ],
        ]);

        ShippingCarrierDistrict::create([
            'shipping_carrier_id' => $carrier->id,
            'external_id' => '777',
            'city' => 'Casablanca',
            'district_name' => 'Maarif',
            'price' => 25,
            'estimated_delivery' => '24h',
            'is_pickup' => false,
            'is_active' => true,
        ]);

        $shippingMethod = ShippingMethod::create([
            'name' => 'Sendit Express',
            'slug' => 'sendit-express',
            'carrier' => 'Sendit',
            'shipping_carrier_id' => $carrier->id,
            'base_cost' => 25,
            'estimated_days' => '24h',
            'is_enabled' => true,
        ]);

        $order = $this->makeOrder($customer, 'ORD-SENDIT-001');
        $order->lineItems()->create([
            'product_name' => 'Nino Plush',
            'sku' => 'PLUSH-01',
            'unit_price' => 100,
            'quantity' => 1,
            'line_total' => 100,
        ]);
        $order->addresses()->create($this->addressPayload('shipping'));

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'shipping_method_id' => $shippingMethod->id,
            'status' => ShipmentStatus::DISPATCHED->value,
            'carrier_name' => 'Sendit',
            'carrier_service' => 'Express',
        ]);

        Http::fake([
            'https://app.sendit.ma/api/v1/login' => Http::sequence()
                ->push(['data' => ['token' => 'expired-token']], 200)
                ->push(['data' => ['token' => 'fresh-token']], 200),
            'https://app.sendit.ma/api/v1/deliveries' => Http::sequence()
                ->push([], 401)
                ->push([
                    'data' => [
                        'code' => 'SDT-1001',
                        'status' => 'PENDING',
                        'labelUrl' => 'https://cdn.sendit.test/SDT-1001.pdf',
                    ],
                ], 200),
            'https://app.sendit.ma/api/v1/deliveries/SDT-1001' => Http::response([
                'data' => [
                    'code' => 'SDT-1001',
                    'status' => 'DELIVERED',
                    'labelUrl' => 'https://cdn.sendit.test/SDT-1001.pdf',
                ],
            ], 200),
        ]);

        $this->actingAs($staffUser)
            ->post(route('admin.shipping.shipments.sendit.create', $shipment))
            ->assertRedirect();

        $shipment->refresh();

        $this->assertSame('SDT-1001', $shipment->external_reference);
        $this->assertSame('PENDING', $shipment->external_status);

        $this->actingAs($staffUser)
            ->post(route('admin.shipping.shipments.sendit.sync', $shipment))
            ->assertRedirect();

        $shipment->refresh();

        $this->assertSame('DELIVERED', $shipment->external_status);
        $this->assertSame(ShipmentStatus::DELIVERED, $shipment->status);
    }

    public function test_sendit_label_download_is_proxied_through_the_backend_as_a_pdf_attachment(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = $this->makeShippingUser();
        $customer = User::factory()->customer()->create([
            'status' => 'active',
        ]);

        $carrier = ShippingCarrier::create([
            'name' => 'Sendit',
            'code' => 'sendit-label-download',
            'provider' => ShippingCarrier::PROVIDER_SENDIT,
            'is_enabled' => true,
            'credentials' => [
                'public_key' => 'public-label',
                'secret_key' => 'secret-label',
            ],
            'settings' => [
                'pickup_district_id' => 44,
            ],
        ]);

        $shippingMethod = ShippingMethod::create([
            'name' => 'Sendit Label',
            'slug' => 'sendit-label-download',
            'carrier' => 'Sendit',
            'shipping_carrier_id' => $carrier->id,
            'base_cost' => 45,
            'estimated_days' => '24h - 48h',
            'is_enabled' => true,
        ]);

        $order = $this->makeOrder($customer, 'ORD-SENDIT-LABEL-001');

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'shipping_method_id' => $shippingMethod->id,
            'status' => ShipmentStatus::READY_TO_SHIP->value,
            'carrier_name' => 'Sendit',
            'tracking_number' => 'TRACK-PDF-1001',
            'external_reference' => 'SDT-PDF-1001',
        ]);

        Http::fake([
            'https://app.sendit.ma/api/v1/login' => Http::response([
                'data' => ['token' => 'sendit-label-token'],
            ], 200),
            'https://app.sendit.ma/api/v1/deliveries/getlabels' => Http::response([
                'data' => [
                    'fileUrl' => 'https://cdn.sendit.test/labels/SDT-PDF-1001.pdf',
                ],
            ], 200),
            'https://cdn.sendit.test/labels/SDT-PDF-1001.pdf' => Http::response('%PDF-1.4 test-label', 200, [
                'Content-Type' => 'application/pdf',
            ]),
        ]);

        $response = $this->actingAs($staffUser)->get(route('admin.shipping.shipments.sendit.label', [
            $shipment,
            'format' => 'a4',
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename="sendit-a4-SDT-PDF-1001.pdf"');
        $this->assertStringStartsWith('%PDF-', $response->content());
    }

    public function test_sendit_label_download_uses_stored_label_url_before_refreshing_provider_state(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = $this->makeShippingUser();
        $customer = User::factory()->customer()->create([
            'status' => 'active',
        ]);

        $carrier = ShippingCarrier::create([
            'name' => 'Sendit',
            'code' => 'sendit-stored-label',
            'provider' => ShippingCarrier::PROVIDER_SENDIT,
            'is_enabled' => true,
            'credentials' => [
                'public_key' => 'public-stored',
                'secret_key' => 'secret-stored',
            ],
            'settings' => [
                'pickup_district_id' => 44,
            ],
        ]);

        $shippingMethod = ShippingMethod::create([
            'name' => 'Sendit Stored Label',
            'slug' => 'sendit-stored-label',
            'carrier' => 'Sendit',
            'shipping_carrier_id' => $carrier->id,
            'base_cost' => 45,
            'estimated_days' => '24h - 48h',
            'is_enabled' => true,
        ]);

        $order = $this->makeOrder($customer, 'ORD-SENDIT-LABEL-002');

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'shipping_method_id' => $shippingMethod->id,
            'status' => ShipmentStatus::READY_TO_SHIP->value,
            'carrier_name' => 'Sendit',
            'tracking_number' => 'TRACK-PDF-2002',
            'external_reference' => 'SDT-PDF-2002',
            'label_url' => 'https://cdn.sendit.test/labels/SDT-PDF-2002.pdf',
        ]);

        Http::fake([
            'https://cdn.sendit.test/labels/SDT-PDF-2002.pdf' => Http::response('%PDF-1.4 stored-label', 200, [
                'Content-Type' => 'application/pdf',
            ]),
            '*' => Http::response([], 500),
        ]);

        $response = $this->actingAs($staffUser)->get(route('admin.shipping.shipments.sendit.label', [
            $shipment,
            'format' => 'a4',
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->content());
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://cdn.sendit.test/labels/SDT-PDF-2002.pdf');
    }

    public function test_sendit_shipment_detail_renders_shareable_tracking_link_and_label_readiness(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = $this->makeShippingUser();
        $customer = User::factory()->customer()->create([
            'status' => 'active',
            'first_name' => 'QA',
            'last_name' => 'Customer',
        ]);

        $carrier = ShippingCarrier::create([
            'name' => 'Sendit',
            'code' => 'sendit-detail-page',
            'provider' => ShippingCarrier::PROVIDER_SENDIT,
            'is_enabled' => true,
            'credentials' => [
                'public_key' => 'public-detail',
                'secret_key' => 'secret-detail',
            ],
            'settings' => [
                'pickup_district_id' => 22,
            ],
        ]);

        $shippingMethod = ShippingMethod::create([
            'name' => 'Standard Maroc',
            'slug' => 'standard-maroc-detail-page',
            'carrier' => 'Sendit',
            'shipping_carrier_id' => $carrier->id,
            'base_cost' => 45,
            'estimated_days' => '24h - 48h',
            'is_enabled' => true,
        ]);

        $order = $this->makeOrder($customer, 'ORD-SENDIT-DETAIL-001');
        $order->update([
            'status' => OrderStatus::PREPARING,
            'payment_method' => 'cod',
        ]);
        $order->addresses()->create([
            ...$this->addressPayload('shipping'),
            'city' => 'Achakkar',
            'state' => null,
            'postal_code' => null,
        ]);

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'shipping_method_id' => $shippingMethod->id,
            'status' => ShipmentStatus::READY_TO_SHIP->value,
            'carrier_name' => 'Sendit',
            'tracking_number' => 'DH1BFE69390',
            'external_reference' => 'DH1BFE69390',
            'external_status' => 'PENDING',
            'label_url' => 'https://app.sendit.ma/storage/pdf/labels_colis_UHB1BB6B03.pdf?v=1774979067',
            'last_provider_sync_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($staffUser)->get(route('admin.shipping.shipments.show', $shipment));

        $response->assertOk();
        $response->assertSeeText('Customer Tracking');
        $response->assertSeeText('Customer Tracking Link');
        $response->assertSee('https://app.sendit.ma/deliveries/DH1BFE69390', false);
        $response->assertSeeText('Stored and ready');
        $response->assertSeeText('Open Sendit Delivery');
        $response->assertSeeText('Sendit links are generated automatically');
        $response->assertSeeText('Choose the next operational state');
        $response->assertSeeText('Apply Transition');
    }

    public function test_shipment_detail_renders_same_status_audit_rows_as_activity_instead_of_fake_transition_copy(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = $this->makeShippingUser();
        $customer = User::factory()->customer()->create([
            'status' => 'active',
            'first_name' => 'QA',
            'last_name' => 'Customer',
        ]);

        $carrier = ShippingCarrier::create([
            'name' => 'Sendit',
            'code' => 'sendit-audit-row',
            'provider' => ShippingCarrier::PROVIDER_SENDIT,
            'is_enabled' => true,
            'credentials' => [
                'public_key' => 'public-audit',
                'secret_key' => 'secret-audit',
            ],
            'settings' => [
                'pickup_district_id' => 33,
            ],
        ]);

        $shippingMethod = ShippingMethod::create([
            'name' => 'Standard Maroc',
            'slug' => 'standard-maroc-audit-row',
            'carrier' => 'Sendit',
            'shipping_carrier_id' => $carrier->id,
            'base_cost' => 45,
            'estimated_days' => '24h - 48h',
            'is_enabled' => true,
        ]);

        $order = $this->makeOrder($customer, 'ORD-SENDIT-DETAIL-002');
        $order->update([
            'status' => OrderStatus::PREPARING,
            'payment_method' => 'cod',
        ]);

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'shipping_method_id' => $shippingMethod->id,
            'status' => ShipmentStatus::READY_TO_SHIP->value,
            'carrier_name' => 'Sendit',
            'tracking_number' => 'DH1BFE69390',
            'external_reference' => 'DH1BFE69390',
        ]);

        ShipmentStatusHistory::create([
            'shipment_id' => $shipment->id,
            'status_from' => ShipmentStatus::READY_TO_SHIP->value,
            'status_to' => ShipmentStatus::READY_TO_SHIP->value,
            'notes' => 'Tracking updated: DH1BFE69390',
            'changed_by' => $staffUser->id,
            'changed_by_name' => $staffUser->name,
        ]);

        $response = $this->actingAs($staffUser)->get(route('admin.shipping.shipments.show', $shipment));

        $response->assertOk();
        $response->assertSeeText('Tracking updated');
        $response->assertSeeText('Ready To Ship checkpoint');
        $response->assertSeeText('Tracking updated: DH1BFE69390');
        $response->assertDontSeeText('Ready To Ship → Ready To Ship');
    }

    public function test_tracking_settings_require_tracking_before_dispatch_and_supply_fallback_tracking_url(): void
    {
        $this->activateAdminTheme('nino-v2');

        app(SettingsService::class)->setMany('shipping', [
            ['key' => 'tracking_required_on_dispatch', 'value' => true, 'type' => 'boolean'],
            ['key' => 'tracking_url_template', 'value' => 'https://track.example.test/{tracking_number}', 'type' => 'string'],
        ]);

        $staffUser = $this->makeShippingUser();

        $customer = User::factory()->create([
            'type' => 'customer',
            'status' => 'active',
        ]);

        $shippingMethod = ShippingMethod::create([
            'name' => 'Standard Delivery',
            'slug' => 'standard-delivery',
            'carrier' => 'CustomCarrier',
            'base_cost' => 25,
            'estimated_days' => '2-4',
            'is_enabled' => true,
        ]);

        $shipment = Shipment::create([
            'order_id' => $this->makeOrder($customer, 'ORD-QA-TRACK-001')->id,
            'shipping_method_id' => $shippingMethod->id,
            'status' => ShipmentStatus::READY_TO_SHIP->value,
            'carrier_name' => 'CustomCarrier',
            'tracking_number' => null,
        ]);

        $this->actingAs($staffUser)
            ->from(route('admin.shipping.shipments.show', $shipment))
            ->post(route('admin.shipping.shipments.status', $shipment), [
                'status' => ShipmentStatus::DISPATCHED->value,
                'notes' => 'Dispatch attempt without tracking',
            ])
            ->assertRedirect(route('admin.shipping.shipments.show', $shipment))
            ->assertSessionHasErrors(['tracking_number']);

        $shipment->update(['tracking_number' => 'LOCAL-123']);

        $this->assertSame(
            'https://track.example.test/LOCAL-123',
            $shipment->fresh()->getTrackingLink(),
        );
    }

    public function test_sendit_tracking_link_uses_the_shareable_delivery_url(): void
    {
        $this->activateAdminTheme('nino-v2');

        $customer = User::factory()->create([
            'type' => 'customer',
            'status' => 'active',
        ]);

        $carrier = ShippingCarrier::create([
            'name' => 'Sendit',
            'code' => 'sendit-shared-link',
            'provider' => ShippingCarrier::PROVIDER_SENDIT,
            'is_enabled' => true,
            'tracking_url_template' => 'https://app.sendit.ma/track/{tracking_number}',
            'credentials' => [
                'public_key' => 'public-track',
                'secret_key' => 'secret-track',
            ],
            'settings' => [
                'pickup_district_id' => 12,
            ],
        ]);

        $shippingMethod = ShippingMethod::create([
            'name' => 'Sendit Shared Link',
            'slug' => 'sendit-shared-link',
            'carrier' => 'Sendit',
            'shipping_carrier_id' => $carrier->id,
            'base_cost' => 45,
            'estimated_days' => '24h - 48h',
            'is_enabled' => true,
        ]);

        $shipment = Shipment::create([
            'order_id' => $this->makeOrder($customer, 'ORD-QA-SENDIT-LINK-001')->id,
            'shipping_method_id' => $shippingMethod->id,
            'status' => ShipmentStatus::READY_TO_SHIP->value,
            'carrier_name' => 'Sendit',
            'tracking_number' => 'TRACK-SENDIT-001',
            'external_reference' => 'DH1BFE69390',
            'tracking_url' => 'https://legacy.sendit.test/track/TRACK-SENDIT-001',
        ]);

        $this->assertSame(
            'https://app.sendit.ma/deliveries/DH1BFE69390',
            $shipment->fresh()->getTrackingLink(),
        );
    }

    public function test_shipment_status_transitions_sync_parent_order_status_lifecycle(): void
    {
        $this->activateAdminTheme('nino-v2');

        NotificationTemplate::create([
            'event' => NotificationEvent::ORDER_SHIPPED,
            'channel' => NotificationChannel::EMAIL,
            'name' => 'Order shipped email',
            'subject' => 'Shipment {{tracking_number}} is on the way',
            'body' => '{{carrier_name}} / {{tracking_url}} / {{estimated_delivery}}',
            'is_enabled' => true,
        ]);
        NotificationTemplate::create([
            'event' => NotificationEvent::ORDER_DELIVERED,
            'channel' => NotificationChannel::EMAIL,
            'name' => 'Order delivered email',
            'subject' => 'Delivered {{order_reference}}',
            'body' => '{{tracking_number}} / {{delivery_date}}',
            'is_enabled' => true,
        ]);

        $staffUser = $this->makeShippingUser();

        $customer = User::factory()->create([
            'type' => 'customer',
            'status' => 'active',
        ]);

        $shippingMethod = ShippingMethod::create([
            'name' => 'Express Delivery',
            'slug' => 'express-delivery',
            'carrier' => 'Amana',
            'base_cost' => 35,
            'estimated_days' => '1-2',
            'is_enabled' => true,
        ]);

        $order = $this->makeOrder($customer, 'ORD-QA-LIFE-001');
        $order->update(['status' => OrderStatus::PAID]);

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'shipping_method_id' => $shippingMethod->id,
            'status' => ShipmentStatus::PENDING->value,
            'carrier_name' => 'Amana',
            'tracking_number' => 'SHIP-1001',
        ]);

        $this->actingAs($staffUser)
            ->post(route('admin.shipping.shipments.status', $shipment), [
                'status' => ShipmentStatus::READY_TO_SHIP->value,
                'notes' => 'Ready for packing',
            ])
            ->assertRedirect(route('admin.shipping.shipments.show', $shipment));

        $this->assertSame(OrderStatus::PREPARING, $order->fresh()->status);

        $this->actingAs($staffUser)
            ->post(route('admin.shipping.shipments.status', $shipment), [
                'status' => ShipmentStatus::PACKED->value,
                'notes' => 'Packed for dispatch',
            ])
            ->assertRedirect(route('admin.shipping.shipments.show', $shipment));

        $this->assertSame(OrderStatus::PREPARING, $order->fresh()->status);

        $this->actingAs($staffUser)
            ->post(route('admin.shipping.shipments.status', $shipment), [
                'status' => ShipmentStatus::DISPATCHED->value,
                'notes' => 'Handed to carrier',
                'tracking_number' => 'SHIP-1001',
            ])
            ->assertRedirect(route('admin.shipping.shipments.show', $shipment));

        $this->assertSame(OrderStatus::SHIPPED, $order->fresh()->status);
        $this->assertDatabaseHas('notification_logs', [
            'event' => NotificationEvent::ORDER_SHIPPED->value,
            'recipient' => $customer->email,
            'customer_id' => $customer->id,
            'order_reference' => $order->reference_number,
        ]);

        $this->actingAs($staffUser)
            ->post(route('admin.shipping.shipments.status', $shipment), [
                'status' => ShipmentStatus::DELIVERED->value,
                'notes' => 'Delivered to customer',
            ])
            ->assertRedirect(route('admin.shipping.shipments.show', $shipment));

        $this->assertSame(OrderStatus::DELIVERED, $order->fresh()->status);
        $this->assertDatabaseHas('notification_logs', [
            'event' => NotificationEvent::ORDER_DELIVERED->value,
            'recipient' => $customer->email,
            'customer_id' => $customer->id,
            'order_reference' => $order->reference_number,
        ]);
    }

    public function test_failed_delivery_marks_the_parent_order_as_failed(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = $this->makeShippingUser();

        $customer = User::factory()->create([
            'type' => 'customer',
            'status' => 'active',
        ]);

        $shippingMethod = ShippingMethod::create([
            'name' => 'Standard Delivery',
            'slug' => 'standard-delivery-failed',
            'carrier' => 'Amana',
            'base_cost' => 25,
            'estimated_days' => '2-4',
            'is_enabled' => true,
        ]);

        $order = $this->makeOrder($customer, 'ORD-QA-LIFE-002');
        $order->update(['status' => OrderStatus::SHIPPED]);

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'shipping_method_id' => $shippingMethod->id,
            'status' => ShipmentStatus::IN_TRANSIT->value,
            'carrier_name' => 'Amana',
            'tracking_number' => 'SHIP-2002',
        ]);

        $this->actingAs($staffUser)
            ->post(route('admin.shipping.shipments.status', $shipment), [
                'status' => ShipmentStatus::FAILED_DELIVERY->value,
                'notes' => 'Carrier could not reach customer',
                'failure_reason' => 'Customer unavailable',
            ])
            ->assertRedirect(route('admin.shipping.shipments.show', $shipment));

        $this->assertSame(OrderStatus::FAILED, $order->fresh()->status);
    }

    public function test_cancelling_a_shipment_marks_the_parent_order_as_cancelled(): void
    {
        $this->activateAdminTheme('nino-v2');

        NotificationTemplate::create([
            'event' => NotificationEvent::ORDER_CANCELLED,
            'channel' => NotificationChannel::EMAIL,
            'name' => 'Order cancelled email',
            'subject' => 'Cancelled {{order_reference}}',
            'body' => '{{order_total}} {{order_currency}}',
            'is_enabled' => true,
        ]);

        $staffUser = $this->makeShippingUser();

        $customer = User::factory()->create([
            'type' => 'customer',
            'status' => 'active',
        ]);

        $shippingMethod = ShippingMethod::create([
            'name' => 'Standard Delivery',
            'slug' => 'standard-delivery-cancelled',
            'carrier' => 'Amana',
            'base_cost' => 25,
            'estimated_days' => '2-4',
            'is_enabled' => true,
        ]);

        $order = $this->makeOrder($customer, 'ORD-QA-LIFE-003');
        $order->update(['status' => OrderStatus::PREPARING]);

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'shipping_method_id' => $shippingMethod->id,
            'status' => ShipmentStatus::READY_TO_SHIP->value,
            'carrier_name' => 'Amana',
        ]);

        $this->actingAs($staffUser)
            ->post(route('admin.shipping.shipments.status', $shipment), [
                'status' => ShipmentStatus::CANCELLED->value,
                'notes' => 'Order cancelled before dispatch',
            ])
            ->assertRedirect(route('admin.shipping.shipments.show', $shipment));

        $this->assertSame(OrderStatus::CANCELLED, $order->fresh()->status);
        $this->assertDatabaseHas('notification_logs', [
            'event' => NotificationEvent::ORDER_CANCELLED->value,
            'recipient' => $customer->email,
            'customer_id' => $customer->id,
            'order_reference' => $order->reference_number,
        ]);
    }

    public function test_returned_shipment_creates_refund_ready_foundation_for_prepaid_orders(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = $this->makeShippingUser();

        $customer = User::factory()->create([
            'type' => 'customer',
            'status' => 'active',
        ]);

        $shippingMethod = ShippingMethod::create([
            'name' => 'Return Flow Delivery',
            'slug' => 'return-flow-delivery',
            'carrier' => 'Amana',
            'base_cost' => 30,
            'estimated_days' => '2-4',
            'is_enabled' => true,
        ]);

        $order = $this->makeOrder($customer, 'ORD-QA-LIFE-004');
        $order->update([
            'status' => OrderStatus::FAILED,
            'payment_method' => 'stripe',
            'grand_total' => 125,
        ]);

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'shipping_method_id' => $shippingMethod->id,
            'status' => ShipmentStatus::FAILED_DELIVERY->value,
            'carrier_name' => 'Amana',
            'tracking_number' => 'SHIP-4004',
        ]);

        $this->actingAs($staffUser)
            ->post(route('admin.shipping.shipments.status', $shipment), [
                'status' => ShipmentStatus::RETURNED->value,
                'notes' => 'Returned to sender after failed delivery',
            ])
            ->assertRedirect(route('admin.shipping.shipments.show', $shipment));

        $order->refresh();

        $this->assertSame(OrderStatus::REFUND_PENDING, $order->status);
        $this->assertDatabaseHas('refund_requests', [
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'status' => RefundStatus::REQUESTED->value,
            'amount' => 125,
            'currency' => 'MAD',
            'reason' => 'Shipment returned to sender',
        ]);
    }

    public function test_delivered_cash_on_delivery_order_generates_invoice(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = $this->makeShippingUser();

        $customer = User::factory()->create([
            'type' => 'customer',
            'status' => 'active',
        ]);

        $shippingMethod = ShippingMethod::create([
            'name' => 'COD Delivery',
            'slug' => 'cod-delivery',
            'carrier' => 'Amana',
            'base_cost' => 25,
            'estimated_days' => '2-4',
            'is_enabled' => true,
        ]);

        $order = $this->makeOrder($customer, 'ORD-QA-LIFE-005');
        $order->update([
            'status' => OrderStatus::SHIPPED,
            'payment_method' => 'cash_on_delivery',
        ]);

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'shipping_method_id' => $shippingMethod->id,
            'status' => ShipmentStatus::DISPATCHED->value,
            'carrier_name' => 'Amana',
            'tracking_number' => 'SHIP-5005',
        ]);

        $this->actingAs($staffUser)
            ->post(route('admin.shipping.shipments.status', $shipment), [
                'status' => ShipmentStatus::DELIVERED->value,
                'notes' => 'Delivered and COD settled',
            ])
            ->assertRedirect(route('admin.shipping.shipments.show', $shipment));

        $invoice = Invoice::query()->where('order_id', $order->id)->first();

        $this->assertNotNull($invoice);
        $this->assertStringStartsWith('INV-', $invoice->invoice_number);
        $this->assertSame($customer->id, $invoice->customer_id);
    }

    public function test_manual_cod_order_with_sendit_auto_creates_sendit_parcel_and_moves_order_to_preparing(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = User::factory()->staff()->create();
        $customer = User::factory()->customer()->create([
            'status' => 'active',
        ]);

        $carrier = ShippingCarrier::create([
            'name' => 'Sendit',
            'code' => 'sendit-auto',
            'provider' => ShippingCarrier::PROVIDER_SENDIT,
            'is_enabled' => true,
            'credentials' => [
                'public_key' => 'public-auto',
                'secret_key' => 'secret-auto',
            ],
            'settings' => [
                'pickup_district_id' => 77,
                'default_label_format' => 1,
            ],
        ]);

        $district = ShippingCarrierDistrict::create([
            'shipping_carrier_id' => $carrier->id,
            'external_id' => '901',
            'city' => 'Casablanca',
            'district_name' => 'Casablanca - Ben msik',
            'price' => 45,
            'estimated_delivery' => '24h - 48h',
            'is_pickup' => false,
            'is_active' => true,
        ]);

        $shippingMethod = ShippingMethod::create([
            'name' => 'Sendit Standard',
            'slug' => 'sendit-standard-auto',
            'carrier' => 'Sendit',
            'shipping_carrier_id' => $carrier->id,
            'base_cost' => 0,
            'estimated_days' => null,
            'is_enabled' => true,
        ]);

        $product = Product::factory()->published()->simple()->create([
            'name' => 'Audit Plush',
            'price' => 100,
            'sku' => 'APL-001',
        ]);

        StockItem::factory()->create([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'branch_id' => null,
            'quantity' => 10,
            'reserved_quantity' => 0,
            'low_stock_threshold' => 2,
            'status' => StockItem::STATUS_IN_STOCK,
        ]);

        Http::fake([
            'https://app.sendit.ma/api/v1/login' => Http::response([
                'data' => ['token' => 'sendit-auto-token'],
            ]),
            'https://app.sendit.ma/api/v1/deliveries' => Http::response([
                'data' => [
                    'code' => 'SDT-AUTO-1001',
                    'status' => 'PENDING',
                    'labelUrl' => 'https://cdn.sendit.test/SDT-AUTO-1001.pdf',
                ],
            ], 200),
        ]);

        $this->actingAs($staffUser);

        Livewire::test(OrderCreateComponent::class)
            ->set('customer_id', (string) $customer->id)
            ->set('payment_method', 'cod')
            ->set('items', [[
                'product_id' => $product->id,
                'variant_id' => null,
                'quantity' => 1,
                'price' => 100,
                'name' => 'Audit Plush',
                'sku' => 'APL-001',
            ]])
            ->set('shipping_method_id', (string) $shippingMethod->id)
            ->set('shipping_address.first_name', 'Nadia')
            ->set('shipping_address.last_name', 'Bennani')
            ->set('shipping_address.address_line_1', '12 Atlas Street')
            ->set('shipping_phone_country', 'MA')
            ->set('shipping_phone_local', '0612345678')
            ->set('shipping_destination_id', (string) $district->id)
            ->call('save')
            ->assertHasNoErrors();

        $order = Order::query()->with(['shipments', 'transactions'])->latest('id')->firstOrFail();
        $shipment = $order->shipments->sole();
        $transaction = $order->transactions->sole();

        Http::assertSent(function ($request): bool {
            $payload = $request->data();

            return str_ends_with($request->url(), '/deliveries')
                && ($payload['phone'] ?? null) === '0612345678';
        });

        $this->assertSame(OrderStatus::PREPARING, $order->status);
        $this->assertSame(ShipmentStatus::READY_TO_SHIP, $shipment->status);
        $this->assertSame('SDT-AUTO-1001', $shipment->external_reference);
        $this->assertSame('PENDING', $shipment->external_status);
        $this->assertSame('cod', $transaction->payment_method);
        $this->assertSame('cod', $transaction->payment_method_behavior);
        $this->assertSame('cod', $transaction->gateway);
        $this->assertSame('pending', $transaction->status->value);
        $this->assertSame('pending', $transaction->cod_status->value);
        $this->assertSame(1, StockItem::query()->where('product_id', $product->id)->value('reserved_quantity'));
        $this->assertSame('+212612345678', $order->shippingAddress->phone);
    }

    private function makeOrder(User $customer, string $referenceNumber): Order
    {
        return Order::create([
            'reference_number' => $referenceNumber,
            'customer_id' => $customer->id,
            'status' => 'pending',
            'currency' => 'MAD',
            'subtotal' => 100,
            'tax_total' => 0,
            'shipping_total' => 25,
            'discount_total' => 0,
            'grand_total' => 125,
        ]);
    }

    private function addressPayload(string $type): array
    {
        return [
            'type' => $type,
            'first_name' => 'Nadia',
            'last_name' => 'Bennani',
            'address_line_1' => '12 Atlas Street',
            'address_line_2' => 'Suite 4',
            'city' => 'Casablanca',
            'state' => 'Casablanca-Settat',
            'postal_code' => '20000',
            'country' => 'MA',
            'phone' => '+212600000001',
        ];
    }

    private function makeShippingUser(bool $canManageCarriers = false): User
    {
        foreach (['shipping.viewAny', 'shipping.update', 'shipping.manage_carriers'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
        ]);

        $permissions = ['shipping.viewAny', 'shipping.update'];

        if ($canManageCarriers) {
            $permissions[] = 'shipping.manage_carriers';
        }

        $user->givePermissionTo($permissions);

        return $user;
    }

    private function activateAdminTheme(string $theme): void
    {
        config(['admin_theme.active' => $theme]);

        $provider = new AdminThemeServiceProvider($this->app);
        $provider->boot($this->app->make(AdminThemeManager::class));
    }
}
