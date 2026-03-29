<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Finance\Enums\RefundStatus;
use App\Modules\Notifications\Enums\NotificationChannel;
use App\Modules\Notifications\Enums\NotificationEvent;
use App\Modules\Notifications\Models\NotificationTemplate;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Shipping\Enums\ShipmentStatus;
use App\Modules\Shipping\Models\Shipment;
use App\Modules\Shipping\Models\ShippingMethod;
use App\Modules\Settings\Services\SettingsService;
use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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

        $staffUser = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
        ]);

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

        $staffUser = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
        ]);

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

    public function test_tracking_settings_require_tracking_before_dispatch_and_supply_fallback_tracking_url(): void
    {
        $this->activateAdminTheme('nino-v2');

        app(SettingsService::class)->setMany('shipping', [
            ['key' => 'tracking_required_on_dispatch', 'value' => true, 'type' => 'boolean'],
            ['key' => 'tracking_url_template', 'value' => 'https://track.example.test/{tracking_number}', 'type' => 'string'],
        ]);

        $staffUser = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
        ]);

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

        $staffUser = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
        ]);

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
    }

    public function test_failed_delivery_marks_the_parent_order_as_failed(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
        ]);

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

        $staffUser = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
        ]);

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
    }

    public function test_returned_shipment_creates_refund_ready_foundation_for_prepaid_orders(): void
    {
        $this->activateAdminTheme('nino-v2');

        $staffUser = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
        ]);

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

        $this->assertSame(OrderStatus::REFUNDED, $order->status);
        $this->assertDatabaseHas('refund_requests', [
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'status' => RefundStatus::REQUESTED->value,
            'amount' => 125,
            'currency' => 'MAD',
            'reason' => 'Shipment returned to sender',
        ]);
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

    private function activateAdminTheme(string $theme): void
    {
        config(['admin_theme.active' => $theme]);

        $provider = new AdminThemeServiceProvider($this->app);
        $provider->boot($this->app->make(AdminThemeManager::class));
    }
}
