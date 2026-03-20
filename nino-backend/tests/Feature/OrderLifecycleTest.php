<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderAddress;
use App\Modules\Orders\Models\OrderLineItem;
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
        $response->assertSeeText('Casablanca, 20000');
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

        return Order::create([
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
            'created_at' => $createdAt ?? now(),
            'updated_at' => $createdAt ?? now(),
        ]);
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

    private function makeStaffUser(): User
    {
        $user = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
        ]);

        foreach (['orders.viewAny', 'orders.view'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo(['orders.viewAny', 'orders.view']);

        return $user;
    }

    private function activateAdminTheme(string $theme): void
    {
        config(['admin_theme.active' => $theme]);

        $provider = new AdminThemeServiceProvider($this->app);
        $provider->boot($this->app->make(AdminThemeManager::class));
    }
}
