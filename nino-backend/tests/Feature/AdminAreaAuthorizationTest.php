<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Shipping\Enums\ShipmentStatus;
use App\Modules\Shipping\Models\Shipment;
use App\Modules\Shipping\Models\ShippingMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AdminAreaAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_staff_cannot_access_finance_transactions(): void
    {
        $staff = $this->makeStaffUser(['support.viewAny', 'support.manage_tickets']);

        $this->actingAs($staff)
            ->get(route('admin.finance.transactions.index'))
            ->assertForbidden();
    }

    public function test_finance_staff_cannot_access_shipping_shipments(): void
    {
        $staff = $this->makeStaffUser(['finance.viewAny', 'payments.viewAny']);

        $this->actingAs($staff)
            ->get(route('admin.shipping.shipments.index'))
            ->assertForbidden();
    }

    public function test_shipping_staff_cannot_access_support_lookup(): void
    {
        $staff = $this->makeStaffUser(['shipping.viewAny', 'shipping.update']);

        $this->actingAs($staff)
            ->get(route('admin.support.lookup'))
            ->assertForbidden();
    }

    public function test_support_staff_with_shipping_visibility_cannot_update_shipment_tracking(): void
    {
        $staff = $this->makeStaffUser(['support.viewAny', 'support.manage_tickets', 'shipping.viewAny']);
        $shipment = $this->makeShipment();

        $this->actingAs($staff)
            ->post(route('admin.shipping.shipments.tracking', $shipment), [
                'tracking_number' => 'TRACK-UPDATED-001',
                'tracking_url' => 'https://track.example.test/TRACK-UPDATED-001',
                'carrier_name' => 'Amana',
            ])
            ->assertForbidden();
    }

    public function test_staff_with_order_update_but_without_override_permission_cannot_manually_override_order_status(): void
    {
        $staff = $this->makeStaffUser(['orders.viewAny', 'orders.view', 'orders.update']);
        $order = Order::create([
            'reference_number' => 'ORD-AUTH-1001',
            'status' => OrderStatus::AWAITING_PAYMENT,
            'currency' => 'MAD',
            'subtotal' => 100,
            'tax_total' => 0,
            'shipping_total' => 20,
            'discount_total' => 0,
            'grand_total' => 120,
        ]);

        $this->actingAs($staff)
            ->post(route('admin.orders.status', $order), [
                'status' => OrderStatus::PREPARING->value,
            ])
            ->assertForbidden();
    }

    private function makeStaffUser(array $permissions): User
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user = User::factory()->staff()->create([
            'status' => 'active',
        ]);

        $user->givePermissionTo($permissions);

        return $user;
    }

    private function makeShipment(): Shipment
    {
        $customer = User::factory()->customer()->create([
            'status' => 'active',
        ]);

        $order = Order::create([
            'reference_number' => 'ORD-AUTH-SHIP-001',
            'customer_id' => $customer->id,
            'status' => OrderStatus::SHIPPED,
            'currency' => 'MAD',
            'subtotal' => 180,
            'tax_total' => 0,
            'shipping_total' => 20,
            'discount_total' => 0,
            'grand_total' => 200,
        ]);

        $method = ShippingMethod::create([
            'name' => 'Standard Delivery',
            'slug' => 'standard-delivery-auth',
            'carrier' => 'Amana',
            'base_cost' => 20,
            'estimated_days' => '2-4',
            'is_enabled' => true,
        ]);

        return Shipment::create([
            'order_id' => $order->id,
            'shipping_method_id' => $method->id,
            'status' => ShipmentStatus::DISPATCHED->value,
            'carrier_name' => 'Amana',
            'tracking_number' => 'TRACK-AUTH-001',
        ]);
    }
}
