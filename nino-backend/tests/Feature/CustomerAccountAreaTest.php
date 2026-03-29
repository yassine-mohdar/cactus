<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Customers\Models\Address;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderAddress;
use App\Modules\Orders\Models\OrderLineItem;
use App\Modules\Shipping\Enums\ShipmentStatus;
use App\Modules\Shipping\Models\Shipment;
use App\Modules\Shipping\Models\ShipmentStatusHistory;
use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAccountAreaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->activateAdminTheme('nino-v2');
    }

    public function test_customer_account_home_renders_real_overview_metrics(): void
    {
        $customer = $this->makeCustomer();
        $customer->addresses()->create($this->addressPayload(Address::TYPE_BILLING, true));
        $customer->addresses()->create($this->addressPayload(Address::TYPE_SHIPPING, false));

        $recentOrder = $this->makeOrder($customer, 'ORD-ACC-1001', OrderStatus::SHIPPED, 210.00);
        $this->addLineItem($recentOrder, 'Atlas Jacket', 2, 105.00);
        $this->attachShipment($recentOrder, ShipmentStatus::IN_TRANSIT, 'AMANA-1001');

        $response = $this->actingAs($customer)->get(route('customer.account.home'));

        $response->assertOk();
        $response->assertViewIs('customer.account.home');
        $response->assertSeeText('Welcome back');
        $response->assertSeeText('ORD-ACC-1001');
        $response->assertSeeText('AMANA-1001');
        $response->assertSeeText('2');
    }

    public function test_customer_order_history_lists_only_their_own_orders(): void
    {
        $customer = $this->makeCustomer();
        $otherCustomer = $this->makeCustomer('other@example.test');

        $ownOrder = $this->makeOrder($customer, 'ORD-ACC-2001', OrderStatus::DELIVERED, 155.00);
        $this->addLineItem($ownOrder, 'History Mug', 1, 155.00);

        $foreignOrder = $this->makeOrder($otherCustomer, 'ORD-ACC-9999', OrderStatus::DELIVERED, 99.00);
        $this->addLineItem($foreignOrder, 'Foreign Mug', 1, 99.00);

        $response = $this->actingAs($customer)->get(route('customer.account.orders.index'));

        $response->assertOk();
        $response->assertViewIs('customer.account.orders.index');
        $response->assertSeeText('ORD-ACC-2001');
        $response->assertDontSeeText('ORD-ACC-9999');
    }

    public function test_customer_can_view_their_own_order_detail_but_not_someone_elses(): void
    {
        $customer = $this->makeCustomer();
        $otherCustomer = $this->makeCustomer('other@example.test');

        $order = $this->makeOrder($customer, 'ORD-ACC-3001', OrderStatus::PREPARING, 240.00);
        $this->addLineItem($order, 'Detail Blanket', 2, 120.00, 'Forest Green', 'DB-001');
        $this->attachOrderAddresses($order);

        $otherOrder = $this->makeOrder($otherCustomer, 'ORD-ACC-3002', OrderStatus::PREPARING, 120.00);

        $this->actingAs($customer)
            ->get(route('customer.account.orders.show', $order))
            ->assertOk()
            ->assertViewIs('customer.account.orders.show')
            ->assertSeeText('ORD-ACC-3001')
            ->assertSeeText('Detail Blanket')
            ->assertSeeText('Forest Green')
            ->assertSeeText('Casablanca');

        $this->actingAs($customer)
            ->get(route('customer.account.orders.show', $otherOrder))
            ->assertForbidden();
    }

    public function test_customer_order_detail_and_tracking_api_show_tracking_visibility(): void
    {
        $customer = $this->makeCustomer();
        $order = $this->makeOrder($customer, 'ORD-ACC-4001', OrderStatus::SHIPPED, 180.00);
        $this->addLineItem($order, 'Tracking Lamp', 1, 180.00);
        $this->attachOrderAddresses($order);
        $shipment = $this->attachShipment($order, ShipmentStatus::DISPATCHED, 'TRACK-4001');

        ShipmentStatusHistory::create([
            'shipment_id' => $shipment->id,
            'status_from' => ShipmentStatus::PACKED->value,
            'status_to' => ShipmentStatus::DISPATCHED->value,
            'notes' => 'Handed to carrier',
            'changed_by_name' => 'Ops Agent',
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($customer)
            ->get(route('customer.account.orders.show', $order))
            ->assertOk()
            ->assertSeeText('TRACK-4001')
            ->assertSeeText('Dispatched')
            ->assertSeeText('Handed to carrier');

        $this->actingAs($customer)
            ->getJson(route('api.customer.orders.tracking', $order))
            ->assertOk()
            ->assertJsonPath('tracking_number', 'TRACK-4001')
            ->assertJsonPath('status', ShipmentStatus::DISPATCHED->label())
            ->assertJsonPath('status_raw', ShipmentStatus::DISPATCHED->value);
    }

    private function makeCustomer(string $email = 'customer@example.test'): User
    {
        return User::factory()->customer()->create([
            'email' => $email,
            'status' => User::STATUS_ACTIVE,
            'first_name' => 'Nadia',
            'last_name' => 'Bennani',
        ]);
    }

    private function makeOrder(User $customer, string $referenceNumber, OrderStatus $status, float $grandTotal): Order
    {
        return Order::create([
            'reference_number' => $referenceNumber,
            'customer_id' => $customer->id,
            'status' => $status,
            'currency' => 'MAD',
            'subtotal' => $grandTotal - 30,
            'tax_total' => 0,
            'shipping_total' => 30,
            'discount_total' => 0,
            'grand_total' => $grandTotal,
            'payment_method' => 'cash_on_delivery',
            'shipping_method' => 'standard',
        ]);
    }

    private function addLineItem(Order $order, string $name, int $quantity, float $unitPrice, ?string $variant = null, ?string $sku = null): void
    {
        OrderLineItem::create([
            'order_id' => $order->id,
            'product_name' => $name,
            'variant_name' => $variant,
            'sku' => $sku,
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'line_total' => $unitPrice * $quantity,
        ]);
    }

    private function attachOrderAddresses(Order $order): void
    {
        OrderAddress::create([
            'order_id' => $order->id,
            'type' => 'shipping',
            'first_name' => 'Nadia',
            'last_name' => 'Bennani',
            'phone' => '0612345678',
            'address_line_1' => '12 Atlas Street',
            'city' => 'Casablanca',
            'state' => 'Casablanca-Settat',
            'postal_code' => '20000',
            'country' => 'MA',
        ]);

        OrderAddress::create([
            'order_id' => $order->id,
            'type' => 'billing',
            'first_name' => 'Nadia',
            'last_name' => 'Bennani',
            'phone' => '0612345678',
            'address_line_1' => '12 Atlas Street',
            'city' => 'Casablanca',
            'state' => 'Casablanca-Settat',
            'postal_code' => '20000',
            'country' => 'MA',
        ]);
    }

    private function attachShipment(Order $order, ShipmentStatus $status, string $trackingNumber): Shipment
    {
        return Shipment::create([
            'order_id' => $order->id,
            'status' => $status->value,
            'carrier_name' => 'Amana',
            'tracking_number' => $trackingNumber,
        ]);
    }

    private function addressPayload(string $type, bool $isDefault): array
    {
        return [
            'type' => $type,
            'is_default' => $isDefault,
            'first_name' => 'Nadia',
            'last_name' => 'Bennani',
            'company' => 'NinoWorld',
            'address_line_1' => '12 Atlas Street',
            'address_line_2' => null,
            'city' => 'Casablanca',
            'state' => 'Casablanca-Settat',
            'postal_code' => '20000',
            'country' => 'MA',
            'phone' => '+212600000000',
        ];
    }

    private function activateAdminTheme(string $theme): void
    {
        config(['admin_theme.active' => $theme]);

        $provider = new AdminThemeServiceProvider($this->app);
        $provider->boot($this->app->make(AdminThemeManager::class));
    }
}
