<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_lookup_searches_orders_using_the_current_order_schema(): void
    {
        $this->withoutVite();

        [$staff, $order] = $this->createOrderContext();

        $response = $this->actingAs($staff)->get(route('admin.support.lookup', ['q' => 'yassine']));

        $response->assertOk();
        $response->assertSeeText($order->reference_number);
        $response->assertSeeText('Yassine Bennani');
        $response->assertSeeText('yassine@example.com');
        $response->assertSeeText('0612345678');
        $response->assertSeeText('Paid');
    }

    public function test_support_order_timeline_uses_current_order_relations(): void
    {
        $this->withoutVite();

        [$staff, $order] = $this->createOrderContext();

        $response = $this->actingAs($staff)->get(route('admin.support.lookup.timeline', $order));

        $response->assertOk();
        $response->assertSeeText($order->reference_number);
        $response->assertSeeText('Yassine Bennani');
        $response->assertSeeText('yassine@example.com');
        $response->assertSeeText('0612345678');
        $response->assertSeeText('130.00 MAD');
    }

    private function createOrderContext(): array
    {
        $staff = User::factory()->create();

        $customer = User::factory()->create([
            'name' => 'Yassine Bennani',
            'first_name' => 'Yassine',
            'last_name' => 'Bennani',
            'email' => 'yassine@example.com',
            'phone' => '0612345678',
            'type' => 'customer',
        ]);

        $order = Order::create([
            'reference_number' => 'ORD-TEST-1001',
            'customer_id' => $customer->id,
            'status' => OrderStatus::PAID,
            'currency' => 'MAD',
            'subtotal' => 100,
            'tax_total' => 20,
            'shipping_total' => 10,
            'discount_total' => 0,
            'grand_total' => 130,
        ]);

        OrderAddress::create([
            'order_id' => $order->id,
            'type' => 'billing',
            'first_name' => 'Yassine',
            'last_name' => 'Bennani',
            'phone' => '0612345678',
            'address_line_1' => '123 Test Street',
            'city' => 'Casablanca',
            'country' => 'MA',
        ]);

        return [$staff, $order];
    }
}
