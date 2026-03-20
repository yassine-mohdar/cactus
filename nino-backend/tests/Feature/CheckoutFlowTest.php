<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Checkout\Models\Cart;
use App\Modules\Checkout\Models\CartItem;
use App\Modules\Orders\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_checkout_creates_customer_order_snapshots_and_clears_the_session_cart(): void
    {
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

        $response = $this->postJson(
            route('api.checkout.process'),
            [
                'cart_session_id' => $sessionId,
                'customer_email' => 'guest.checkout@example.com',
                'customer_first_name' => 'Guest',
                'customer_last_name' => 'Customer',
                'payment_method' => 'cash_on_delivery',
                'shipping_address' => $this->addressPayload('Guest', 'Customer'),
                'billing_address' => $this->addressPayload('Guest', 'Customer'),
            ],
            ['X-Cart-Session-Id' => $sessionId],
        );

        $response->assertCreated();
        $response->assertJson([
            'message' => 'Checkout processed successfully',
        ]);

        $customer = User::where('email', 'guest.checkout@example.com')->firstOrFail();
        $order = Order::firstOrFail();

        $this->assertSame('customer', $customer->type);
        $this->assertSame($customer->id, $order->customer_id);
        $this->assertSame(240.0, (float) $order->subtotal);
        $this->assertSame(45.0, (float) $order->shipping_total);
        $this->assertSame(285.0, (float) $order->grand_total);
        $this->assertSame(1, $order->lineItems()->count());
        $this->assertSame(2, $order->addresses()->count());
        $this->assertSame(1, $customer->communityGroupMemberships()->count());
        $this->assertNotNull($customer->community_default_group_invited_at);

        $this->assertDatabaseMissing('carts', ['id' => $cart->id]);
        $this->assertDatabaseHas('order_addresses', [
            'order_id' => $order->id,
            'type' => 'shipping',
            'first_name' => 'Guest',
        ]);
        $this->assertDatabaseHas('order_addresses', [
            'order_id' => $order->id,
            'type' => 'billing',
            'first_name' => 'Guest',
        ]);
    }

    public function test_authenticated_checkout_uses_the_existing_customer_and_clears_the_user_cart(): void
    {
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
            'shipping_address' => $this->addressPayload('Existing', 'Customer'),
            'billing_address' => $this->addressPayload('Existing', 'Customer'),
        ]);

        $response->assertCreated();

        $order = Order::firstOrFail();

        $this->assertSame($customer->id, $order->customer_id);
        $this->assertSame(600.0, (float) $order->subtotal);
        $this->assertSame(0.0, (float) $order->shipping_total);
        $this->assertSame(600.0, (float) $order->grand_total);
        $this->assertSame(1, User::where('email', $customer->email)->count());

        $this->assertDatabaseMissing('carts', ['id' => $cart->id]);
        $this->assertDatabaseHas('order_line_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
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

    /**
     * @return array<string, string>
     */
    private function addressPayload(string $firstName, string $lastName): array
    {
        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => '0612345678',
            'address_line_1' => '123 Test Street',
            'address_line_2' => 'Suite 4',
            'city' => 'Casablanca',
            'postal_code' => '20000',
            'country' => 'MA',
        ];
    }
}
