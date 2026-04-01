<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\Catalog\Models\Product;
use App\Modules\Finance\Enums\PaymentMethod;
use App\Modules\Finance\Enums\TransactionStatus;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\PaymentTransaction;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderAddress;
use App\Modules\Orders\Models\OrderLineItem;
use App\Modules\Shipping\Enums\ShipmentStatus;
use App\Modules\Shipping\Models\Shipment;
use App\Modules\Shipping\Models\ShipmentStatusHistory;
use App\Modules\Shipping\Models\ShippingMethod;
use App\Modules\Support\Models\InternalNote;
use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->activateAdminTheme('nino-v2');
    }

    public function test_admin_orders_index_supports_filters_and_exposes_summary_metrics(): void
    {
        $staffUser = $this->makeStaffUser();

        $awaitingPayment = $this->createOrder('ORD-LIFE-1001', OrderStatus::AWAITING_PAYMENT, 185.00, now()->subDays(2));
        $this->createOrder('ORD-LIFE-1002', OrderStatus::PREPARING, 220.00, now()->subDay());
        $this->createOrder('ORD-LIFE-1003', OrderStatus::DELIVERED, 95.00, now()->subDays(10));

        $response = $this->actingAs($staffUser)->get(route('admin.orders.index', [
            'search' => '1001',
            'status' => OrderStatus::AWAITING_PAYMENT->value,
        ]));

        $response->assertOk();
        $response->assertViewIs('admin.orders.index');
        $response->assertSeeText('Recent Orders');
        $response->assertSeeText('ORD-LIFE-1001');
        $response->assertDontSeeText('ORD-LIFE-1002');
        $response->assertDontSeeText('ORD-LIFE-1003');
        $response->assertViewHas('summary', function (array $summary): bool {
            return $summary['total_orders'] === 3
                && $summary['awaiting_payment'] === 1
                && $summary['preparing'] === 1
                && (float) $summary['gross_30_days'] === 500.0;
        });
        $response->assertViewHas('orders', function ($orders) use ($awaitingPayment): bool {
            return $orders->total() === 1
                && $orders->getCollection()->pluck('id')->all() === [$awaitingPayment->id];
        });
    }

    public function test_admin_orders_index_search_can_match_customer_identity_fields(): void
    {
        $staffUser = $this->makeStaffUser();

        $matchingOrder = $this->createOrder('ORD-LIFE-1004', OrderStatus::PENDING, 125.00);
        $matchingOrder->customer()->update([
            'first_name' => 'Rania',
            'last_name' => 'Bennani',
            'name' => 'Rania Bennani',
            'email' => 'rania.bennani@example.test',
        ]);

        $nonMatchingOrder = $this->createOrder('ORD-LIFE-1005', OrderStatus::PENDING, 150.00);
        $nonMatchingOrder->customer()->update([
            'first_name' => 'Samir',
            'last_name' => 'El Idrissi',
            'name' => 'Samir El Idrissi',
            'email' => 'samir@example.test',
        ]);

        $response = $this->actingAs($staffUser)->get(route('admin.orders.index', [
            'search' => 'rania.bennani@example.test',
        ]));

        $response->assertOk();
        $response->assertSeeText('ORD-LIFE-1004');
        $response->assertDontSeeText('ORD-LIFE-1005');
    }

    public function test_admin_orders_index_supports_date_range_filters(): void
    {
        $staffUser = $this->makeStaffUser();

        $matchingOrder = $this->createOrder('ORD-LIFE-1006', OrderStatus::PAID, 180.00, now()->subDays(3));
        $matchingOrder->customer()->update([
            'first_name' => 'Leila',
            'last_name' => 'Alaoui',
            'name' => 'Leila Alaoui',
            'email' => 'leila.alaoui@example.test',
        ]);

        $olderOrder = $this->createOrder('ORD-LIFE-1007', OrderStatus::PAID, 210.00, now()->subDays(12));
        $currentOrder = $this->createOrder('ORD-LIFE-1008', OrderStatus::PAID, 130.00, now());

        $response = $this->actingAs($staffUser)->get(route('admin.orders.index', [
            'date_from' => now()->subDays(5)->toDateString(),
            'date_to' => now()->subDay()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSeeText('ORD-LIFE-1006');
        $response->assertDontSeeText('ORD-LIFE-1007');
        $response->assertDontSeeText('ORD-LIFE-1008');
    }

    public function test_admin_orders_index_supports_customer_filters(): void
    {
        $staffUser = $this->makeStaffUser();

        $matchingOrder = $this->createOrder('ORD-LIFE-1012', OrderStatus::PAID, 180.00);
        $matchingOrder->customer()->update([
            'first_name' => 'Leila',
            'last_name' => 'Alaoui',
            'name' => 'Leila Alaoui',
            'email' => 'leila.alaoui@example.test',
        ]);

        $secondMatchingOrder = $this->createOrder('ORD-LIFE-1013', OrderStatus::PAID, 210.00);
        $secondMatchingOrder->update(['customer_id' => $matchingOrder->customer_id]);

        $differentCustomerOrder = $this->createOrder('ORD-LIFE-1014', OrderStatus::PAID, 130.00);
        $differentCustomerOrder->customer()->update([
            'first_name' => 'Nadia',
            'last_name' => 'Karim',
            'name' => 'Nadia Karim',
            'email' => 'nadia.karim@example.test',
        ]);

        $response = $this->actingAs($staffUser)->get(route('admin.orders.index', [
            'customer_id' => $matchingOrder->customer_id,
        ]));

        $response->assertOk();
        $response->assertSeeText('ORD-LIFE-1012');
        $response->assertSeeText('ORD-LIFE-1013');
        $response->assertDontSeeText('ORD-LIFE-1014');
    }

    public function test_admin_orders_index_supports_payment_and_shipping_filters(): void
    {
        $staffUser = $this->makeStaffUser();

        $matchingOrder = $this->createOrder('ORD-LIFE-1009', OrderStatus::AWAITING_PAYMENT, 245.00);
        $matchingOrder->update([
            'payment_method' => 'stripe',
            'shipping_method' => 'express_delivery',
        ]);

        $differentPayment = $this->createOrder('ORD-LIFE-1010', OrderStatus::AWAITING_PAYMENT, 199.00);
        $differentPayment->update([
            'payment_method' => 'cash_on_delivery',
            'shipping_method' => 'express_delivery',
        ]);

        $differentShipping = $this->createOrder('ORD-LIFE-1011', OrderStatus::AWAITING_PAYMENT, 205.00);
        $differentShipping->update([
            'payment_method' => 'stripe',
            'shipping_method' => 'standard_delivery',
        ]);

        $response = $this->actingAs($staffUser)->get(route('admin.orders.index', [
            'payment_method' => 'stripe',
            'shipping_method' => 'express_delivery',
        ]));

        $response->assertOk();
        $response->assertSeeText('ORD-LIFE-1009');
        $response->assertDontSeeText('ORD-LIFE-1010');
        $response->assertDontSeeText('ORD-LIFE-1011');
    }

    public function test_admin_order_show_renders_snapshot_items_and_shipping_context(): void
    {
        $staffUser = $this->makeStaffUser();
        $order = $this->createOrder('ORD-LIFE-2001', OrderStatus::PREPARING, 285.00);
        $product = $this->createProduct('lifecycle-plush', 120.00);

        OrderLineItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'Lifecycle Plush',
            'variant_name' => 'Forest Green',
            'sku' => 'LIFE-PLUSH-001',
            'unit_price' => 120.00,
            'quantity' => 2,
            'line_total' => 240.00,
        ]);

        OrderAddress::create([
            'order_id' => $order->id,
            'type' => 'shipping',
            'first_name' => 'Yassine',
            'last_name' => 'Bennani',
            'phone' => '0612345678',
            'address_line_1' => '123 Test Street',
            'address_line_2' => 'Suite 4',
            'city' => 'Casablanca',
            'postal_code' => '20000',
            'country' => 'MA',
        ]);

        OrderAddress::create([
            'order_id' => $order->id,
            'type' => 'billing',
            'first_name' => 'Yassine',
            'last_name' => 'Bennani',
            'phone' => '0612345678',
            'address_line_1' => '123 Test Street',
            'city' => 'Casablanca',
            'postal_code' => '20000',
            'country' => 'MA',
        ]);

        $response = $this->actingAs($staffUser)->get(route('admin.orders.show', $order));

        $response->assertOk();
        $response->assertViewIs('admin.orders.show');
        $response->assertSeeText('ORD-LIFE-2001');
        $response->assertSeeText('Lifecycle Plush');
        $response->assertSeeText('Forest Green');
        $response->assertSeeText('LIFE-PLUSH-001');
        $response->assertSeeText('285.00 MAD');
        $response->assertSeeText('Yassine Bennani');
        $response->assertSeeText('ord-life-2001@example.test');
        $response->assertSeeText('0612345678');
        $response->assertSeeText('Casablanca');
        $response->assertSeeText('20000');
    }

    public function test_admin_order_show_renders_timeline_internal_notes_customer_notes_and_audit_history(): void
    {
        $staffUser = $this->makeStaffUser(canOverrideStatus: true);
        $order = $this->createOrder('ORD-LIFE-2002', OrderStatus::SHIPPED, 310.00);
        $order->update([
            'payment_method' => 'stripe',
            'shipping_method' => 'express_delivery',
            'customer_notes' => 'Please call before delivery.',
        ]);

        PaymentTransaction::create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'type' => TransactionType::PAYMENT,
            'status' => TransactionStatus::COMPLETED,
            'payment_method' => PaymentMethod::STRIPE,
            'gateway' => 'stripe',
            'amount' => 310.00,
            'fee_amount' => 5.00,
            'currency' => 'MAD',
            'created_at' => now()->subHours(6),
            'updated_at' => now()->subHours(6),
        ]);

        $shippingMethod = ShippingMethod::create([
            'name' => 'Express Delivery',
            'slug' => 'express-delivery-order-show',
            'carrier' => 'Amana',
            'base_cost' => 35,
            'estimated_days' => '1-2',
            'is_enabled' => true,
        ]);

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'shipping_method_id' => $shippingMethod->id,
            'status' => ShipmentStatus::IN_TRANSIT,
            'carrier_name' => 'Amana',
            'tracking_number' => 'ORDER-SHOW-123',
        ]);

        ShipmentStatusHistory::create([
            'shipment_id' => $shipment->id,
            'status_from' => ShipmentStatus::READY_TO_SHIP->value,
            'status_to' => ShipmentStatus::DISPATCHED->value,
            'notes' => 'Handed off to carrier',
            'changed_by' => $staffUser->id,
            'changed_by_name' => $staffUser->name,
            'created_at' => now()->subHours(4),
            'updated_at' => now()->subHours(4),
        ]);

        $note = InternalNote::addTo($order, 'Customer requested silent delivery after 6 PM.', $staffUser->id, true);

        AuditLog::create([
            'user_id' => $staffUser->id,
            'actor_type' => $staffUser::class,
            'actor_name' => $staffUser->name,
            'actor_email' => $staffUser->email,
            'action' => 'orders.updated',
            'auditable_type' => Order::class,
            'auditable_id' => $order->id,
            'target_label' => 'Order: '.$order->reference_number,
            'old_values' => ['status' => 'paid'],
            'new_values' => ['status' => 'shipped'],
            'context' => ['method' => 'PUT'],
            'notes' => 'Shipment status pushed order into shipped state.',
        ]);

        $response = $this->actingAs($staffUser)->get(route('admin.orders.show', $order));

        $response->assertOk();
        $response->assertSeeText('Operational Timeline');
        $response->assertSeeText('Payment transaction Completed');
        $response->assertSeeText('Shipment status updated');
        $response->assertSeeText('Handed off to carrier');
        $response->assertSeeText('Internal Notes');
        $response->assertSeeText('Customer requested silent delivery after 6 PM.');
        $response->assertSeeText('Customer Notes');
        $response->assertSeeText('Please call before delivery.');
        $response->assertSeeText('Recent Audit Activity');
        $response->assertSeeText('Orders Updated');
        $response->assertSeeText('Shipment status pushed order into shipped state.');
        $response->assertSeeText('Manual status override');
        $response->assertSeeText('Update Order Status');
    }

    public function test_admin_order_show_renders_same_status_shipment_audit_rows_as_activity(): void
    {
        $staffUser = $this->makeStaffUser();
        $order = $this->createOrder('ORD-LIFE-2002A', OrderStatus::PREPARING, 145.00);

        $shippingMethod = ShippingMethod::create([
            'name' => 'Standard Maroc',
            'slug' => 'standard-maroc-order-audit',
            'carrier' => 'Sendit',
            'base_cost' => 45,
            'estimated_days' => '24h - 48h',
            'is_enabled' => true,
        ]);

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'shipping_method_id' => $shippingMethod->id,
            'status' => ShipmentStatus::READY_TO_SHIP,
            'carrier_name' => 'Sendit',
            'tracking_number' => 'DH1BFE69390',
        ]);

        ShipmentStatusHistory::create([
            'shipment_id' => $shipment->id,
            'status_from' => ShipmentStatus::READY_TO_SHIP->value,
            'status_to' => ShipmentStatus::READY_TO_SHIP->value,
            'notes' => 'Tracking updated: DH1BFE69390',
            'changed_by' => $staffUser->id,
            'changed_by_name' => $staffUser->name,
        ]);

        $response = $this->actingAs($staffUser)->get(route('admin.orders.show', $order));

        $response->assertOk();
        $response->assertSeeText('Shipment tracking updated');
        $response->assertSeeText('Tracking updated: DH1BFE69390');
        $response->assertDontSeeText('Ready To Ship -> Ready To Ship');
    }

    public function test_admin_order_show_renders_sendit_fulfillment_tracking_and_label_actions(): void
    {
        $staffUser = $this->makeStaffUser();
        $order = $this->createOrder('ORD-LIFE-2006', OrderStatus::PREPARING, 245.00);

        $carrier = \App\Modules\Shipping\Models\ShippingCarrier::create([
            'name' => 'Sendit',
            'code' => 'sendit-order-show',
            'provider' => \App\Modules\Shipping\Models\ShippingCarrier::PROVIDER_SENDIT,
            'is_enabled' => true,
            'credentials' => [
                'public_key' => 'public-order-show',
                'secret_key' => 'secret-order-show',
            ],
            'settings' => [
                'pickup_district_id' => 101,
            ],
        ]);

        $shippingMethod = ShippingMethod::create([
            'name' => 'Sendit Standard',
            'slug' => 'sendit-standard-order-show',
            'carrier' => 'Sendit',
            'shipping_carrier_id' => $carrier->id,
            'base_cost' => 45,
            'estimated_days' => '24h - 48h',
            'is_enabled' => true,
        ]);

        Shipment::create([
            'order_id' => $order->id,
            'shipping_method_id' => $shippingMethod->id,
            'status' => ShipmentStatus::READY_TO_SHIP,
            'carrier_name' => 'Sendit',
            'tracking_number' => 'TRACK-SENDIT-1001',
            'external_reference' => 'SDT-ORDER-1001',
            'external_status' => 'PENDING',
            'last_provider_sync_at' => now()->subMinutes(10),
        ]);

        $response = $this->actingAs($staffUser)->get(route('admin.orders.show', $order));

        $response->assertOk();
        $response->assertSeeText('Shipment Fulfillment');
        $response->assertSeeText('TRACK-SENDIT-1001');
        $response->assertSeeText('SDT-ORDER-1001');
        $response->assertSeeText('Download A4 Label');
        $response->assertSeeText('Download Thermal Label');
    }

    public function test_admin_order_detail_can_manually_override_status_and_write_audit_log(): void
    {
        $staffUser = $this->makeStaffUser(canOverrideStatus: true);
        $order = $this->createOrder('ORD-LIFE-2004', OrderStatus::AWAITING_PAYMENT, 210.00);

        $this->actingAs($staffUser)
            ->post(route('admin.orders.status', $order), [
                'status' => OrderStatus::PREPARING->value,
                'notes' => 'Payment confirmed manually by operations.',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame(OrderStatus::PREPARING, $order->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'orders.status_overridden',
            'auditable_type' => Order::class,
            'auditable_id' => $order->id,
            'notes' => 'Payment confirmed manually by operations.',
        ]);
    }

    public function test_admin_order_detail_status_override_requires_override_permission(): void
    {
        $staffUser = $this->makeStaffUser();
        $order = $this->createOrder('ORD-LIFE-2005', OrderStatus::AWAITING_PAYMENT, 210.00);

        $this->actingAs($staffUser)
            ->post(route('admin.orders.status', $order), [
                'status' => OrderStatus::PREPARING->value,
            ])
            ->assertForbidden();

        $this->assertSame(OrderStatus::AWAITING_PAYMENT, $order->fresh()->status);
    }

    public function test_admin_order_internal_notes_can_be_added_pinned_and_deleted_from_the_detail_flow(): void
    {
        $staffUser = $this->makeStaffUser(canManageSupport: true);
        $order = $this->createOrder('ORD-LIFE-2003', OrderStatus::PREPARING, 250.00);

        $this->actingAs($staffUser)
            ->post(route('admin.support.notes.store'), [
                'notable_type' => Order::class,
                'notable_id' => $order->id,
                'content' => 'Flag for support callback if carrier misses first attempt.',
            ])
            ->assertRedirect();

        $note = InternalNote::query()
            ->where('notable_type', Order::class)
            ->where('notable_id', $order->id)
            ->latest('id')
            ->firstOrFail();

        $this->actingAs($staffUser)
            ->post(route('admin.support.notes.pin', $note))
            ->assertRedirect();

        $this->assertTrue($note->fresh()->is_pinned);

        $this->actingAs($staffUser)
            ->delete(route('admin.support.notes.destroy', $note))
            ->assertRedirect();

        $this->assertDatabaseMissing('internal_notes', [
            'id' => $note->id,
        ]);
    }

    public function test_public_order_tracking_returns_safe_order_snapshot_json(): void
    {
        $order = $this->createOrder('ORD-LIFE-3001', OrderStatus::SHIPPED, 199.00);

        OrderLineItem::create([
            'order_id' => $order->id,
            'product_name' => 'Tracking Plush',
            'variant_name' => 'Sky Blue',
            'sku' => 'TRACK-001',
            'unit_price' => 149.00,
            'quantity' => 1,
            'line_total' => 149.00,
        ]);

        OrderAddress::create([
            'order_id' => $order->id,
            'type' => 'shipping',
            'first_name' => 'Yassine',
            'last_name' => 'Bennani',
            'phone' => '0612345678',
            'address_line_1' => '123 Test Street',
            'city' => 'Casablanca',
            'postal_code' => '20000',
            'country' => 'MA',
        ]);

        $response = $this->getJson(route('api.orders.track', $order->reference_number));

        $response->assertOk();
        $response->assertJsonPath('order.reference_number', 'ORD-LIFE-3001');
        $response->assertJsonPath('order.status', OrderStatus::SHIPPED->value);
        $response->assertJsonPath('order.status_label', OrderStatus::SHIPPED->label());
        $response->assertJsonPath('order.totals.grand_total', '199.00');
        $response->assertJsonPath('order.line_items.0.name', 'Tracking Plush');
        $response->assertJsonPath('order.shipping_address.city', 'Casablanca');
        $response->assertJsonMissingPath('order.admin_notes');
        $response->assertJsonMissingPath('order.billing_address');
    }

    public function test_public_order_tracking_exposes_failed_status_label_safely(): void
    {
        $order = $this->createOrder('ORD-LIFE-3002', OrderStatus::FAILED, 149.00);

        $response = $this->getJson(route('api.orders.track', $order->reference_number));

        $response->assertOk();
        $response->assertJsonPath('order.reference_number', 'ORD-LIFE-3002');
        $response->assertJsonPath('order.status', OrderStatus::FAILED->value);
        $response->assertJsonPath('order.status_label', OrderStatus::FAILED->label());
        $response->assertJsonMissingPath('order.admin_notes');
    }

    public function test_public_order_tracking_exposes_refunded_status_label_safely(): void
    {
        $order = $this->createOrder('ORD-LIFE-3003', OrderStatus::REFUNDED, 149.00);

        $response = $this->getJson(route('api.orders.track', $order->reference_number));

        $response->assertOk();
        $response->assertJsonPath('order.reference_number', 'ORD-LIFE-3003');
        $response->assertJsonPath('order.status', OrderStatus::REFUNDED->value);
        $response->assertJsonPath('order.status_label', OrderStatus::REFUNDED->label());
        $response->assertJsonMissingPath('order.admin_notes');
    }

    private function createOrder(
        string $referenceNumber,
        OrderStatus $status,
        float $grandTotal,
        ?\Illuminate\Support\Carbon $createdAt = null,
    ): Order {
        $customer = User::factory()->create([
            'name' => 'Yassine Bennani',
            'first_name' => 'Yassine',
            'last_name' => 'Bennani',
            'email' => strtolower($referenceNumber) . '@example.test',
            'phone' => '0612345678',
            'type' => 'customer',
            'status' => 'active',
        ]);

        $order = Order::create([
            'reference_number' => $referenceNumber,
            'customer_id' => $customer->id,
            'status' => $status,
            'currency' => 'MAD',
            'subtotal' => $grandTotal - 35.00,
            'tax_total' => 0,
            'shipping_total' => 35.00,
            'discount_total' => 0,
            'grand_total' => $grandTotal,
            'payment_method' => 'cash_on_delivery',
            'shipping_method' => 'standard',
            'customer_notes' => 'Leave at the front desk',
            'admin_notes' => 'Internal only',
        ]);

        if ($createdAt !== null) {
            $order->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->saveQuietly();
        }

        return $order;
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

    private function makeStaffUser(bool $canOverrideStatus = false, bool $canManageSupport = false): User
    {
        $user = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
        ]);

        foreach (['orders.viewAny', 'orders.view', 'orders.override_status', 'support.manage_tickets'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $permissions = ['orders.viewAny', 'orders.view'];

        if ($canOverrideStatus) {
            $permissions[] = 'orders.override_status';
        }

        if ($canManageSupport) {
            $permissions[] = 'support.manage_tickets';
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
