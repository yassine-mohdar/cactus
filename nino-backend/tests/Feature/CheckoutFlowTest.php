<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Checkout\Models\Cart;
use App\Modules\Checkout\Models\CartItem;
use App\Modules\Customers\Models\Address;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\IAM\Notifications\CustomerPasswordSetupNotification;
use App\Modules\Notifications\Enums\NotificationChannel;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Notifications\Models\NotificationTemplate;
use App\Modules\Notifications\Enums\NotificationEvent;
use App\Modules\Payments\Models\GatewaySetting;
use App\Modules\Shipping\Enums\ShipmentStatus;
use App\Modules\Shipping\Models\ShippingCarrier;
use App\Modules\Shipping\Models\ShippingCarrierDistrict;
use App\Modules\Shipping\Models\ShippingMethod;
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_checkout_creates_customer_order_snapshots_and_clears_the_session_cart(): void
    {
        Notification::fake();

        NotificationTemplate::create([
            'event' => NotificationEvent::ORDER_PLACED,
            'channel' => NotificationChannel::EMAIL,
            'name' => 'Order placed email',
            'subject' => 'Order {{order_reference}} placed',
            'body' => 'Order total {{order_total}} {{order_currency}}',
            'is_enabled' => true,
        ]);

        NotificationTemplate::create([
            'event' => NotificationEvent::WELCOME,
            'channel' => NotificationChannel::EMAIL,
            'name' => 'Welcome email',
            'subject' => 'Welcome {{customer_name}}',
            'body' => 'Hello {{customer_name}}',
            'is_enabled' => true,
        ]);

        NotificationTemplate::create([
            'event' => NotificationEvent::PASSWORD_SETUP,
            'channel' => NotificationChannel::EMAIL,
            'name' => 'Password setup email',
            'subject' => 'Set up your account',
            'body' => 'Use {{setup_url}} within {{expiry_hours}} hours',
            'is_enabled' => true,
        ]);

        $product = $this->createProduct('guest-checkout-product', 120);
        $sessionId = 'guest-session-001';

        $cart = Cart::create([
            'session_id' => $sessionId,
            'currency' => 'MAD',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $shippingAddress = $this->addressPayload('Guest', 'Customer', [
            'address_line_1' => '123 Shipping Street',
            'city' => 'Casablanca',
            'postal_code' => '20000',
        ]);
        $billingAddress = $this->addressPayload('Guest', 'Customer', [
            'address_line_1' => '456 Billing Avenue',
            'city' => 'Rabat',
            'postal_code' => '10000',
        ]);

        $response = $this->postJson(
            route('api.checkout.process'),
            [
                'cart_session_id' => $sessionId,
                'customer_email' => 'guest.checkout@example.com',
                'customer_first_name' => 'Guest',
                'customer_last_name' => 'Customer',
                'payment_method' => 'cash_on_delivery',
                'shipping_address' => $shippingAddress,
                'billing_address' => $billingAddress,
            ],
            ['X-Cart-Session-Id' => $sessionId],
        );

        $response->assertCreated();
        $response->assertJson([
            'message' => 'Checkout processed successfully',
        ]);
        $response->assertJsonPath('account.account_created', true);
        $response->assertJsonPath('account.customer_email', 'guest.checkout@example.com');
        $response->assertJsonPath('account.account_home_url', null);

        $thankYouUrl = $response->json('thank_you_url');
        $this->assertIsString($thankYouUrl);
        $this->assertStringContainsString('/checkout/success/', $thankYouUrl);

        $customer = User::where('email', 'guest.checkout@example.com')->firstOrFail();
        $order = Order::firstOrFail();

        $this->assertSame('customer', $customer->type);
        $this->assertSame($customer->id, $order->customer_id);
        $this->assertSame(240.0, (float) $order->subtotal);
        $this->assertSame(0.0, (float) $order->tax_total);
        $this->assertSame(45.0, (float) $order->shipping_total);
        $this->assertSame(285.0, (float) $order->grand_total);
        $this->assertSame(OrderStatus::PENDING->value, $order->status->value);
        $this->assertSame(1, $order->lineItems()->count());
        $this->assertSame(2, $order->addresses()->count());
        $this->assertSame(1, $customer->communityGroupMemberships()->count());
        $this->assertNotNull($customer->community_default_group_invited_at);
        Notification::assertSentTo($customer, CustomerPasswordSetupNotification::class, function (CustomerPasswordSetupNotification $notification): bool {
            return str_contains($notification->setupUrl, 'password/setup/')
                && str_contains($notification->setupUrl, 'email=guest.checkout%40example.com')
                && $notification->expiryMinutes > 0
                && $notification->source === 'checkout_auto_create';
        });
        $this->assertDatabaseHas('notification_logs', [
            'event' => NotificationEvent::WELCOME->value,
            'recipient' => $customer->email,
            'customer_id' => $customer->id,
        ]);
        $this->assertDatabaseHas('notification_logs', [
            'event' => NotificationEvent::PASSWORD_SETUP->value,
            'recipient' => $customer->email,
            'customer_id' => $customer->id,
        ]);
        $this->assertDatabaseHas('notification_logs', [
            'event' => NotificationEvent::ORDER_PLACED->value,
            'recipient' => $customer->email,
            'customer_id' => $customer->id,
            'order_reference' => $order->reference_number,
        ]);
        $this->assertNotSame('password', $customer->getRawOriginal('password'));

        $this->assertDatabaseMissing('carts', ['id' => $cart->id]);
        $this->assertDatabaseHas('order_addresses', [
            'order_id' => $order->id,
            'type' => 'shipping',
            'first_name' => 'Guest',
            'address_line_1' => '123 Shipping Street',
            'city' => 'Casablanca',
        ]);
        $this->assertDatabaseHas('order_addresses', [
            'order_id' => $order->id,
            'type' => 'billing',
            'first_name' => 'Guest',
            'address_line_1' => '456 Billing Avenue',
            'city' => 'Rabat',
        ]);

        $this->get($thankYouUrl)
            ->assertOk()
            ->assertSeeText('Your order is confirmed.')
            ->assertSeeText($order->reference_number)
            ->assertSeeText('Check your inbox for your password setup link')
            ->assertSeeText('View public order status')
            ->assertSeeText('Open tracking data');
    }

    public function test_authenticated_checkout_uses_the_existing_customer_and_clears_the_user_cart(): void
    {
        Notification::fake();

        $customer = User::factory()->create([
            'name' => 'Existing Customer',
            'first_name' => 'Existing',
            'last_name' => 'Customer',
            'email' => 'existing.customer@example.com',
            'type' => 'customer',
            'status' => 'active',
        ]);

        $product = $this->createProduct('auth-checkout-product', 600);

        $cart = Cart::create([
            'user_id' => $customer->id,
            'currency' => 'MAD',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        Sanctum::actingAs($customer);

        $response = $this->postJson(route('api.checkout.process'), [
            'payment_method' => 'stripe',
            'shipping_address' => $this->addressPayload('Existing', 'Customer', [
                'address_line_1' => '99 Auth Shipping Road',
                'city' => 'Marrakech',
                'postal_code' => '40000',
            ]),
            'billing_address' => $this->addressPayload('Existing', 'Customer', [
                'address_line_1' => '11 Auth Billing Lane',
                'city' => 'Tangier',
                'postal_code' => '90000',
            ]),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('account.account_created', false);
        $response->assertJsonPath('account.customer_email', $customer->email);
        $response->assertJsonPath('account.account_home_url', route('customer.account.home'));

        $order = Order::firstOrFail();

        $this->assertSame($customer->id, $order->customer_id);
        $this->assertSame(600.0, (float) $order->subtotal);
        $this->assertSame(0.0, (float) $order->shipping_total);
        $this->assertSame(600.0, (float) $order->grand_total);
        $this->assertSame(1, User::where('email', $customer->email)->count());
        Notification::assertNothingSent();

        $this->assertDatabaseMissing('carts', ['id' => $cart->id]);
        $this->assertDatabaseHas('order_line_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('order_addresses', [
            'order_id' => $order->id,
            'type' => 'shipping',
            'address_line_1' => '99 Auth Shipping Road',
            'city' => 'Marrakech',
        ]);
        $this->assertDatabaseHas('order_addresses', [
            'order_id' => $order->id,
            'type' => 'billing',
            'address_line_1' => '11 Auth Billing Lane',
            'city' => 'Tangier',
        ]);
        $this->assertSame(OrderStatus::AWAITING_PAYMENT->value, $order->status->value);
        $this->assertStringStartsWith('ORD-', $order->reference_number);
        $this->assertSame('stripe', $order->payment_method);

        $this->get($response->json('thank_you_url'))
            ->assertOk()
            ->assertSeeText('Open my account')
            ->assertSeeText('Track this order')
            ->assertSeeText('Awaiting Payment')
            ->assertSeeText($order->reference_number);
    }

    public function test_checkout_returns_primary_gateway_redirect_payload_for_cmi_orders(): void
    {
        GatewaySetting::create([
            'gateway_id' => 'cmi',
            'name' => 'CMI',
            'is_enabled' => true,
            'mode' => 'test',
            'credentials' => [
                'store_id' => 'store-test-01',
                'client_id' => 'client-test-01',
                'hash_key' => 'super-secret-hash',
                'terminal_id' => 'terminal-99',
            ],
            'metadata' => [
                'currency_code' => '504',
                'language' => 'fr',
            ],
        ]);

        $customer = User::factory()->customer()->create([
            'status' => 'active',
        ]);

        $product = $this->createProduct('cmi-checkout-product', 350);
        $cart = Cart::create([
            'user_id' => $customer->id,
            'currency' => 'MAD',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        Sanctum::actingAs($customer);

        $response = $this->postJson(route('api.checkout.process'), [
            'payment_method' => 'cmi',
            'shipping_address' => $this->addressPayload('CMI', 'Customer'),
            'billing_address' => $this->addressPayload('CMI', 'Customer', [
                'address_line_1' => '9 Billing Route',
                'city' => 'Rabat',
                'postal_code' => '10001',
            ]),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('payment.gateway', 'cmi');
        $response->assertJsonPath('payment.redirect_url', 'https://testpayment.cmi.co.ma/fim/est3Dgate');
        $response->assertJsonPath('payment.redirect_method', 'POST');
        $this->assertNotEmpty($response->json('payment.form_fields.hash'));
        $this->assertStringStartsWith('CMI-', (string) $response->json('payment.reference'));

        $order = Order::firstOrFail();

        $this->assertDatabaseHas('payment_transactions', [
            'order_id' => $order->id,
            'gateway' => 'cmi',
        ]);
    }

    public function test_bank_transfer_checkout_returns_offline_payment_details_and_renders_instructions_on_success_page(): void
    {
        GatewaySetting::create([
            'gateway_id' => 'offline_transfer',
            'name' => 'Offline Bank Transfer',
            'is_enabled' => true,
            'mode' => 'live',
            'credentials' => [],
            'metadata' => [
                'method_label' => 'Manual Bank Transfer',
                'checkout_title' => 'Pay by bank transfer',
                'checkout_description' => 'Use the company banking details below to complete payment.',
                'instructions' => 'Transfer the funds and include the reference shown here.',
                'bank_name' => 'Attijariwafa Bank',
                'account_holder' => 'NinoWorld SARL AU',
                'iban' => 'MA64001122334455667788990011',
                'payment_window_hours' => 72,
                'reference_prefix' => 'NW',
                'require_receipt' => true,
            ],
        ]);

        $product = $this->createProduct('bank-transfer-product', 220);
        $sessionId = 'offline-bank-transfer-session';

        $cart = Cart::create([
            'session_id' => $sessionId,
            'currency' => 'MAD',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->postJson(
            route('api.checkout.process'),
            [
                'cart_session_id' => $sessionId,
                'customer_email' => 'bank.transfer@example.com',
                'customer_first_name' => 'Bank',
                'customer_last_name' => 'Transfer',
                'payment_method' => 'bank_transfer',
                'shipping_address' => $this->addressPayload('Bank', 'Transfer'),
                'billing_address' => $this->addressPayload('Bank', 'Transfer', [
                    'address_line_1' => '8 Billing Street',
                    'city' => 'Rabat',
                    'postal_code' => '10010',
                ]),
            ],
            ['X-Cart-Session-Id' => $sessionId],
        );

        $response->assertCreated();
        $response->assertJsonPath('payment.gateway', 'offline_transfer');
        $response->assertJsonPath('payment.method_code', 'bank_transfer');
        $response->assertJsonPath('payment.offline_method.checkout_title', 'Pay by bank transfer');
        $this->assertNotNull($response->json('payment.reference'));

        $order = Order::firstOrFail();

        $this->assertSame(OrderStatus::AWAITING_PAYMENT->value, $order->status->value);
        $this->assertDatabaseHas('payment_transactions', [
            'order_id' => $order->id,
            'gateway' => 'offline_transfer',
        ]);

        $this->get($response->json('thank_you_url'))
            ->assertOk()
            ->assertSeeText('Pay by bank transfer')
            ->assertSeeText('Attijariwafa Bank')
            ->assertSeeText('Transfer reference');
    }

    public function test_authenticated_checkout_can_reuse_saved_customer_addresses_by_id(): void
    {
        $customer = User::factory()->customer()->create([
            'status' => 'active',
        ]);

        $shippingAddress = Address::create([
            'user_id' => $customer->id,
            'type' => Address::TYPE_SHIPPING,
            'is_default' => true,
            'first_name' => 'Saved',
            'last_name' => 'Shipping',
            'address_line_1' => '12 Saved Shipping Blvd',
            'city' => 'Fes',
            'state' => 'Fes-Meknes',
            'postal_code' => '30000',
            'country' => 'MA',
            'phone' => '0600000001',
        ]);

        $billingAddress = Address::create([
            'user_id' => $customer->id,
            'type' => Address::TYPE_BILLING,
            'is_default' => true,
            'first_name' => 'Saved',
            'last_name' => 'Billing',
            'address_line_1' => '34 Saved Billing Ave',
            'city' => 'Agadir',
            'state' => 'Souss-Massa',
            'postal_code' => '80000',
            'country' => 'MA',
            'phone' => '0600000002',
        ]);

        $product = $this->createProduct('saved-address-checkout-product', 150);

        $cart = Cart::create([
            'user_id' => $customer->id,
            'currency' => 'MAD',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        Sanctum::actingAs($customer);

        $this->postJson(route('api.checkout.process'), [
            'payment_method' => 'cash_on_delivery',
            'shipping_address_id' => $shippingAddress->id,
            'billing_address_id' => $billingAddress->id,
        ])->assertCreated();

        $order = Order::query()->latest('id')->firstOrFail();

        $this->assertDatabaseHas('order_addresses', [
            'order_id' => $order->id,
            'type' => 'shipping',
            'first_name' => 'Saved',
            'address_line_1' => '12 Saved Shipping Blvd',
            'city' => 'Fes',
        ]);
        $this->assertDatabaseHas('order_addresses', [
            'order_id' => $order->id,
            'type' => 'billing',
            'first_name' => 'Saved',
            'address_line_1' => '34 Saved Billing Ave',
            'city' => 'Agadir',
        ]);
    }

    public function test_checkout_returns_validation_error_for_existing_guest_email_and_missing_addresses(): void
    {
        User::factory()->customer()->create([
            'email' => 'existing.guest@example.com',
        ]);

        $product = $this->createProduct('validation-checkout-product', 100);
        $sessionId = 'validation-checkout-session';

        $cart = Cart::create([
            'session_id' => $sessionId,
            'currency' => 'MAD',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->postJson(route('api.checkout.process'), [
            'cart_session_id' => $sessionId,
            'customer_email' => 'existing.guest@example.com',
            'customer_first_name' => 'Existing',
            'customer_last_name' => 'Guest',
            'payment_method' => 'cash_on_delivery',
        ], ['X-Cart-Session-Id' => $sessionId]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'customer_email',
            'shipping_address.first_name',
            'billing_address.first_name',
        ]);
    }

    public function test_checkout_returns_clear_error_state_for_empty_cart(): void
    {
        $sessionId = 'empty-cart-checkout-session';

        Cart::create([
            'session_id' => $sessionId,
            'currency' => 'MAD',
        ]);

        $response = $this->postJson(route('api.checkout.process'), [
            'cart_session_id' => $sessionId,
            'customer_email' => 'empty.cart@example.com',
            'customer_first_name' => 'Empty',
            'customer_last_name' => 'Cart',
            'payment_method' => 'cash_on_delivery',
            'shipping_address' => $this->addressPayload('Empty', 'Cart'),
            'billing_address' => $this->addressPayload('Empty', 'Cart'),
        ], ['X-Cart-Session-Id' => $sessionId]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Checkout failed.',
            'error_code' => 'EMPTY_CART',
        ]);
    }

    public function test_checkout_uses_configured_shipping_defaults_for_order_method_and_shipping_total(): void
    {
        app(SettingsService::class)->setMany('shipping', [
            ['key' => 'default_method_code', 'value' => 'express-priority', 'type' => 'string'],
            ['key' => 'default_shipping_cost', 'value' => 65, 'type' => 'number'],
            ['key' => 'free_shipping_threshold', 'value' => 999, 'type' => 'number'],
        ]);

        $product = $this->createProduct('shipping-default-product', 120);
        $sessionId = 'shipping-default-session';

        $cart = Cart::create([
            'session_id' => $sessionId,
            'currency' => 'MAD',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->postJson(
            route('api.checkout.process'),
            [
                'cart_session_id' => $sessionId,
                'customer_email' => 'shipping.defaults@example.com',
                'customer_first_name' => 'Shipping',
                'customer_last_name' => 'Defaults',
                'payment_method' => 'cash_on_delivery',
                'shipping_address' => $this->addressPayload('Shipping', 'Defaults'),
                'billing_address' => $this->addressPayload('Shipping', 'Defaults'),
            ],
            ['X-Cart-Session-Id' => $sessionId],
        )->assertCreated();

        $order = Order::query()->latest('id')->firstOrFail();

        $this->assertSame('express-priority', $order->shipping_method);
        $this->assertSame(65.0, (float) $order->shipping_total);
        $this->assertSame(305.0, (float) $order->grand_total);
    }

    public function test_cod_checkout_with_sendit_shipping_auto_creates_sendit_parcel_and_moves_order_to_preparing(): void
    {
        $product = $this->createProduct('sendit-cod-checkout-product', 120);
        $stockItem = StockItem::factory()->create([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'branch_id' => null,
            'quantity' => 10,
            'reserved_quantity' => 0,
            'low_stock_threshold' => 3,
            'status' => StockItem::STATUS_IN_STOCK,
        ]);

        $carrier = ShippingCarrier::create([
            'name' => 'Sendit',
            'code' => 'sendit-checkout',
            'provider' => ShippingCarrier::PROVIDER_SENDIT,
            'is_enabled' => true,
            'credentials' => [
                'public_key' => 'public-checkout',
                'secret_key' => 'secret-checkout',
            ],
            'settings' => [
                'pickup_district_id' => 88,
                'default_label_format' => 1,
            ],
        ]);

        ShippingCarrierDistrict::create([
            'shipping_carrier_id' => $carrier->id,
            'external_id' => '1001',
            'city' => 'Casablanca',
            'district_name' => 'Casablanca - Achakkar',
            'price' => 45,
            'estimated_delivery' => '24h - 48h',
            'is_pickup' => false,
            'is_active' => true,
        ]);

        $shippingMethod = ShippingMethod::create([
            'name' => 'Sendit Maroc',
            'slug' => 'sendit-maroc',
            'carrier' => 'Sendit',
            'shipping_carrier_id' => $carrier->id,
            'base_cost' => 0,
            'estimated_days' => null,
            'is_enabled' => true,
        ]);

        $sessionId = 'sendit-cod-checkout-session';

        $cart = Cart::create([
            'session_id' => $sessionId,
            'currency' => 'MAD',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        Http::fake([
            'https://app.sendit.ma/api/v1/login' => Http::response([
                'data' => ['token' => 'sendit-checkout-token'],
            ]),
            'https://app.sendit.ma/api/v1/deliveries' => Http::response([
                'data' => [
                    'code' => 'SDT-CHECKOUT-1001',
                    'status' => 'PENDING',
                    'labelUrl' => 'https://cdn.sendit.test/SDT-CHECKOUT-1001.pdf',
                ],
            ], 200),
        ]);

        $this->postJson(
            route('api.checkout.process'),
            [
                'cart_session_id' => $sessionId,
                'customer_email' => 'sendit.cod.checkout@example.com',
                'customer_first_name' => 'Sendit',
                'customer_last_name' => 'Checkout',
                'payment_method' => 'cash_on_delivery',
                'shipping_method_id' => $shippingMethod->id,
                'shipping_address' => $this->addressPayload('Sendit', 'Checkout', [
                    'city' => 'Casablanca',
                ]),
                'billing_address' => $this->addressPayload('Sendit', 'Checkout', [
                    'city' => 'Casablanca',
                ]),
            ],
            ['X-Cart-Session-Id' => $sessionId],
        )->assertCreated();

        $order = Order::query()->with(['shipments', 'transactions'])->latest('id')->firstOrFail();
        $shipment = $order->shipments->sole();
        $transaction = $order->transactions->sole();

        $this->assertSame(OrderStatus::PREPARING->value, $order->status->value);
        $this->assertSame(ShipmentStatus::READY_TO_SHIP, $shipment->status);
        $this->assertSame('SDT-CHECKOUT-1001', $shipment->external_reference);
        $this->assertSame('PENDING', $shipment->external_status);
        $this->assertSame('cod', $transaction->payment_method);
        $this->assertSame('cod', $transaction->payment_method_behavior);
        $this->assertSame('cod', $transaction->gateway);
        $this->assertSame(1, $stockItem->fresh()->reserved_quantity);
    }

    public function test_checkout_reserves_matching_global_stock_item_during_order_creation(): void
    {
        $product = $this->createProduct('reservation-checkout-product', 80);
        $stockItem = StockItem::factory()->create([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'branch_id' => null,
            'quantity' => 10,
            'reserved_quantity' => 0,
            'low_stock_threshold' => 3,
            'status' => StockItem::STATUS_IN_STOCK,
        ]);

        $sessionId = 'reservation-checkout-session';

        $cart = Cart::create([
            'session_id' => $sessionId,
            'currency' => 'MAD',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $this->postJson(
            route('api.checkout.process'),
            [
                'cart_session_id' => $sessionId,
                'customer_email' => 'reservation.checkout@example.com',
                'customer_first_name' => 'Reserve',
                'customer_last_name' => 'Stock',
                'payment_method' => 'cash_on_delivery',
                'shipping_address' => $this->addressPayload('Reserve', 'Stock'),
                'billing_address' => $this->addressPayload('Reserve', 'Stock'),
            ],
            ['X-Cart-Session-Id' => $sessionId],
        )->assertCreated();

        $order = Order::query()->latest('id')->firstOrFail();
        $stockItem->refresh();
        $movement = StockMovement::query()->where('reason', 'order_reserved')->latest('id')->firstOrFail();

        $this->assertSame(3, $stockItem->reserved_quantity);
        $this->assertSame(7, $stockItem->available_quantity);
        $this->assertSame('reservation', $movement->type);
        $this->assertSame(Order::class, $movement->reference_type);
        $this->assertSame((string) $order->id, (string) $movement->reference_id);
    }

    public function test_checkout_persists_hard_line_item_snapshots_for_variant_pricing(): void
    {
        $product = $this->createProduct('variant-snapshot-product', 210);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'VAR-SNAP-001',
            'price' => 240,
            'sale_price' => 180,
            'quantity' => 8,
        ]);

        $sessionId = 'variant-snapshot-session';

        $cart = Cart::create([
            'session_id' => $sessionId,
            'currency' => 'MAD',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $this->postJson(route('api.checkout.process'), [
            'cart_session_id' => $sessionId,
            'customer_email' => 'variant.snapshot@example.com',
            'customer_first_name' => 'Variant',
            'customer_last_name' => 'Snapshot',
            'payment_method' => 'cash_on_delivery',
            'shipping_address' => $this->addressPayload('Variant', 'Snapshot'),
            'billing_address' => $this->addressPayload('Variant', 'Snapshot'),
        ], ['X-Cart-Session-Id' => $sessionId])->assertCreated();

        $order = Order::query()->with('lineItems')->latest('id')->firstOrFail();
        $lineItem = $order->lineItems->sole();

        $product->update(['name' => 'Mutated Product Name', 'sku' => 'MUTATED-SKU', 'price' => 999]);
        $variant->update(['sku' => 'VAR-MUTATED-001', 'price' => 999, 'sale_price' => null]);
        $lineItem->refresh();

        $this->assertSame('Variant Snapshot Product', $lineItem->product_name);
        $this->assertSame('VAR-SNAP-001', $lineItem->variant_name);
        $this->assertSame('VAR-SNAP-001', $lineItem->sku);
        $this->assertSame(180.0, (float) $lineItem->unit_price);
        $this->assertSame(360.0, (float) $lineItem->line_total);
    }

    public function test_checkout_persists_hard_address_snapshots_even_if_saved_addresses_change(): void
    {
        $customer = User::factory()->customer()->create([
            'status' => 'active',
        ]);

        $shippingAddress = Address::create([
            'user_id' => $customer->id,
            'type' => Address::TYPE_SHIPPING,
            'is_default' => true,
            'first_name' => 'Snapshot',
            'last_name' => 'Shipping',
            'address_line_1' => '55 Immutable Shipping Rd',
            'city' => 'Meknes',
            'state' => 'Fes-Meknes',
            'postal_code' => '50000',
            'country' => 'MA',
            'phone' => '0600000011',
        ]);

        $billingAddress = Address::create([
            'user_id' => $customer->id,
            'type' => Address::TYPE_BILLING,
            'is_default' => true,
            'first_name' => 'Snapshot',
            'last_name' => 'Billing',
            'address_line_1' => '88 Immutable Billing St',
            'city' => 'Oujda',
            'state' => 'Oriental',
            'postal_code' => '60000',
            'country' => 'MA',
            'phone' => '0600000022',
        ]);

        $product = $this->createProduct('address-snapshot-product', 130);

        $cart = Cart::create([
            'user_id' => $customer->id,
            'currency' => 'MAD',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        Sanctum::actingAs($customer);

        $this->postJson(route('api.checkout.process'), [
            'payment_method' => 'cash_on_delivery',
            'shipping_address_id' => $shippingAddress->id,
            'billing_address_id' => $billingAddress->id,
        ])->assertCreated();

        $order = Order::query()->with(['shippingAddress', 'billingAddress'])->latest('id')->firstOrFail();

        $shippingAddress->update([
            'address_line_1' => 'Changed Shipping Source',
            'city' => 'Changed City',
        ]);
        $billingAddress->update([
            'address_line_1' => 'Changed Billing Source',
            'city' => 'Changed Billing City',
        ]);

        $order->refresh()->load(['shippingAddress', 'billingAddress']);

        $this->assertSame('55 Immutable Shipping Rd', $order->shippingAddress?->address_line_1);
        $this->assertSame('Meknes', $order->shippingAddress?->city);
        $this->assertSame('88 Immutable Billing St', $order->billingAddress?->address_line_1);
        $this->assertSame('Oujda', $order->billingAddress?->city);
    }

    public function test_checkout_generates_internal_reference_and_complete_pricing_summary(): void
    {
        $product = $this->createProduct('pricing-summary-product', 120);
        $sessionId = 'pricing-summary-session';

        $cart = Cart::create([
            'session_id' => $sessionId,
            'currency' => 'MAD',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->postJson(route('api.checkout.process'), [
            'cart_session_id' => $sessionId,
            'customer_email' => 'pricing.summary@example.com',
            'customer_first_name' => 'Pricing',
            'customer_last_name' => 'Summary',
            'payment_method' => 'cash_on_delivery',
            'shipping_address' => $this->addressPayload('Pricing', 'Summary'),
            'billing_address' => $this->addressPayload('Pricing', 'Summary'),
        ], ['X-Cart-Session-Id' => $sessionId])->assertCreated();

        $firstOrder = Order::query()->latest('id')->firstOrFail();

        $secondSessionId = 'pricing-summary-session-2';
        $secondCart = Cart::create([
            'session_id' => $secondSessionId,
            'currency' => 'MAD',
        ]);

        CartItem::create([
            'cart_id' => $secondCart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->postJson(route('api.checkout.process'), [
            'cart_session_id' => $secondSessionId,
            'customer_email' => 'pricing.summary.second@example.com',
            'customer_first_name' => 'Pricing',
            'customer_last_name' => 'Summary Two',
            'payment_method' => 'cash_on_delivery',
            'shipping_address' => $this->addressPayload('Pricing', 'Summary'),
            'billing_address' => $this->addressPayload('Pricing', 'Summary'),
        ], ['X-Cart-Session-Id' => $secondSessionId])->assertCreated();

        $secondOrder = Order::query()->latest('id')->firstOrFail();

        $this->assertMatchesRegularExpression('/^ORD-\d{8}-[A-Z0-9]{6}$/', $firstOrder->reference_number);
        $this->assertNotSame($firstOrder->reference_number, $secondOrder->reference_number);
        $this->assertSame(240.0, (float) $firstOrder->subtotal);
        $this->assertSame(0.0, (float) $firstOrder->tax_total);
        $this->assertSame(0.0, (float) $firstOrder->discount_total);
        $this->assertSame(45.0, (float) $firstOrder->shipping_total);
        $this->assertSame(285.0, (float) $firstOrder->grand_total);
    }

    public function test_public_success_page_can_be_reopened_by_order_reference_lookup(): void
    {
        $product = $this->createProduct('lookup-success-product', 90);
        $sessionId = 'lookup-success-session';

        $cart = Cart::create([
            'session_id' => $sessionId,
            'currency' => 'MAD',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->postJson(route('api.checkout.process'), [
            'cart_session_id' => $sessionId,
            'customer_email' => 'lookup.success@example.com',
            'customer_first_name' => 'Lookup',
            'customer_last_name' => 'Success',
            'payment_method' => 'cash_on_delivery',
            'shipping_address' => $this->addressPayload('Lookup', 'Success'),
            'billing_address' => $this->addressPayload('Lookup', 'Success'),
        ], ['X-Cart-Session-Id' => $sessionId])->assertCreated();

        $order = Order::query()->latest('id')->firstOrFail();

        $this->get(route('checkout.success', ['ref' => $order->reference_number]))
            ->assertOk()
            ->assertSeeText($order->reference_number)
            ->assertSeeText('Open status page')
            ->assertSeeText('Open tracking data');
    }

    private function createProduct(string $slug, float $price): Product
    {
        return Product::create([
            'name' => ucwords(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'type' => 'simple',
            'status' => 'published',
            'sku' => strtoupper(str_replace('-', '_', $slug)),
            'price' => $price,
            'quantity' => 20,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function addressPayload(string $firstName, string $lastName, array $overrides = []): array
    {
        return array_merge([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => '0612345678',
            'address_line_1' => '123 Test Street',
            'address_line_2' => 'Suite 4',
            'city' => 'Casablanca',
            'postal_code' => '20000',
            'country' => 'MA',
        ], $overrides);
    }
}
