<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderAddress;
use App\Modules\Support\Models\InternalNote;
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

        [$staff, $order] = $this->createOrderContext(withRecentOrders: true);

        $response = $this->actingAs($staff)->get(route('admin.support.lookup.timeline', $order));

        $response->assertOk();
        $response->assertSeeText($order->reference_number);
        $response->assertSeeText('Yassine Bennani');
        $response->assertSeeText('yassine@example.com');
        $response->assertSeeText('0612345678');
        $response->assertSeeText('130.00 MAD');
        $response->assertSeeText('Customer Order Summary');
        $response->assertSeeText('Total Orders');
        $response->assertSeeText('ORD-TEST-1000');
        $response->assertSeeText('ORD-TEST-0999');
        $response->assertSeeText('Recent Statuses');
    }

    public function test_support_timeline_supports_internal_note_create_pin_and_delete_flow(): void
    {
        $this->withoutVite();

        [$staff, $order] = $this->createOrderContext();

        $this->actingAs($staff)->post(route('admin.support.notes.store'), [
            'notable_type' => Order::class,
            'notable_id' => $order->id,
            'content' => 'Customer requested evening delivery follow-up.',
        ])->assertRedirect();

        $note = InternalNote::query()->firstOrFail();

        $this->assertSame('Customer requested evening delivery follow-up.', $note->content);
        $this->assertFalse($note->is_pinned);

        $this->actingAs($staff)->post(route('admin.support.notes.pin', $note))
            ->assertRedirect();

        $this->assertTrue($note->fresh()->is_pinned);

        $timeline = $this->actingAs($staff)->get(route('admin.support.lookup.timeline', $order));
        $timeline->assertOk();
        $timeline->assertSeeText('Internal Notes');
        $timeline->assertSeeText('Customer requested evening delivery follow-up.');

        $this->actingAs($staff)->delete(route('admin.support.notes.destroy', $note))
            ->assertRedirect();

        $this->assertDatabaseCount('internal_notes', 0);
    }

    private function createOrderContext(bool $withRecentOrders = false): array
    {
        $staff = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
        ]);

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

        if ($withRecentOrders) {
            Order::create([
                'reference_number' => 'ORD-TEST-1000',
                'customer_id' => $customer->id,
                'status' => OrderStatus::DELIVERED,
                'currency' => 'MAD',
                'subtotal' => 80,
                'tax_total' => 10,
                'shipping_total' => 5,
                'discount_total' => 0,
                'grand_total' => 95,
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ]);

            Order::create([
                'reference_number' => 'ORD-TEST-0999',
                'customer_id' => $customer->id,
                'status' => OrderStatus::SHIPPED,
                'currency' => 'MAD',
                'subtotal' => 60,
                'tax_total' => 6,
                'shipping_total' => 4,
                'discount_total' => 0,
                'grand_total' => 70,
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ]);
        }

        return [$staff, $order];
    }
}
