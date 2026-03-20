<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Orders\Models\Order;
use App\Modules\Shipping\Enums\ShipmentStatus;
use App\Modules\Shipping\Models\Shipment;
use App\Modules\Shipping\Models\ShippingMethod;
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
