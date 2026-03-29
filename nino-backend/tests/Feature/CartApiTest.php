<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Checkout\Models\Cart;
use App\Modules\Checkout\Models\CartItem;
use App\Modules\Promotions\Enums\CouponType;
use App\Modules\Promotions\Models\Coupon;
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CartApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cart_supports_add_increment_update_and_remove_item_flows(): void
    {
        $product = Product::factory()->published()->create([
            'price' => 120,
        ]);

        $sessionId = 'cart-session-guest';

        $this->postJson(route('api.cart.items.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ], ['X-Cart-Session-Id' => $sessionId])
            ->assertOk()
            ->assertJsonPath('cart.items.0.quantity', 1)
            ->assertJsonPath('cart.totals.line_items_count', 1)
            ->assertJsonPath('cart.totals.units_count', 1)
            ->assertJsonPath('cart.totals.subtotal', 120);

        $addAgain = $this->postJson(route('api.cart.items.add'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ], ['X-Cart-Session-Id' => $sessionId])
            ->assertOk()
            ->assertJsonPath('cart.items.0.quantity', 3)
            ->assertJsonPath('cart.totals.units_count', 3)
            ->assertJsonPath('cart.totals.subtotal', 360);

        $itemId = $addAgain->json('cart.items.0.id');

        $this->putJson(route('api.cart.items.update', $itemId), [
            'quantity' => 5,
        ], ['X-Cart-Session-Id' => $sessionId])
            ->assertOk()
            ->assertJsonPath('cart.items.0.quantity', 5)
            ->assertJsonPath('cart.totals.units_count', 5)
            ->assertJsonPath('cart.totals.subtotal', 600);

        $this->deleteJson(route('api.cart.items.remove', $itemId), [], ['X-Cart-Session-Id' => $sessionId])
            ->assertOk()
            ->assertJsonPath('cart.items', []);

        $this->assertDatabaseHas('carts', ['session_id' => $sessionId]);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_cart_coupon_apply_and_remove_use_real_coupon_validation(): void
    {
        $product = Product::factory()->published()->create([
            'price' => 200,
        ]);

        $coupon = Coupon::create([
            'code' => 'SAVE10',
            'name' => 'Save 10',
            'type' => CouponType::FIXED,
            'value' => 10,
            'is_active' => true,
            'minimum_cart_total' => 100,
        ]);

        $sessionId = 'cart-session-coupon';
        $cart = Cart::create([
            'session_id' => $sessionId,
            'currency' => 'MAD',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->postJson(route('api.cart.coupon.apply'), [
            'code' => 'save10',
        ], ['X-Cart-Session-Id' => $sessionId])
            ->assertOk()
            ->assertJsonPath('cart.coupon.code', 'SAVE10')
            ->assertJsonPath('cart.coupon.applied', true)
            ->assertJsonPath('cart.totals.discount', 10)
            ->assertJsonPath('cart.totals.grand_total', 235);

        $this->deleteJson(route('api.cart.coupon.remove'), [], ['X-Cart-Session-Id' => $sessionId])
            ->assertOk()
            ->assertJsonPath('cart.coupon.code', null)
            ->assertJsonPath('cart.totals.discount', 0);

        $coupon->delete();
    }

    public function test_cart_coupon_returns_validation_error_for_invalid_code(): void
    {
        $customer = User::factory()->customer()->create();
        $product = Product::factory()->published()->create([
            'price' => 50,
        ]);

        Sanctum::actingAs($customer);

        $cart = Cart::create([
            'user_id' => $customer->id,
            'currency' => 'MAD',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->postJson(route('api.cart.coupon.apply'), [
            'code' => 'missing',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'COUPON_NOT_FOUND')
            ->assertJsonPath('cart.coupon.applied', false);
    }

    public function test_cart_summary_exposes_shipping_estimate_and_free_shipping_progress(): void
    {
        app(SettingsService::class)->setMany('shipping', [
            ['key' => 'default_method_code', 'value' => 'express-priority', 'type' => 'string'],
            ['key' => 'default_shipping_cost', 'value' => 35, 'type' => 'number'],
            ['key' => 'free_shipping_threshold', 'value' => 250, 'type' => 'number'],
        ]);

        $product = Product::factory()->published()->create([
            'price' => 100,
        ]);

        $sessionId = 'cart-session-shipping';

        $this->postJson(route('api.cart.items.add'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ], ['X-Cart-Session-Id' => $sessionId])
            ->assertOk()
            ->assertJsonPath('cart.shipping.method_code', 'express-priority')
            ->assertJsonPath('cart.shipping.estimated_cost', 35)
            ->assertJsonPath('cart.free_shipping_progress.threshold', 250)
            ->assertJsonPath('cart.free_shipping_progress.remaining', 50)
            ->assertJsonPath('cart.free_shipping_progress.achieved', false)
            ->assertJsonPath('cart.totals.grand_total', 235);

        $this->postJson(route('api.cart.items.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ], ['X-Cart-Session-Id' => $sessionId])
            ->assertOk()
            ->assertJsonPath('cart.free_shipping_progress.achieved', true)
            ->assertJsonPath('cart.shipping.estimated_cost', 0)
            ->assertJsonPath('cart.shipping.remaining_for_free_shipping', 0)
            ->assertJsonPath('cart.totals.grand_total', 300);
    }

    public function test_cart_summary_exposes_upsell_and_cross_sell_suggestions(): void
    {
        $category = Category::factory()->create();

        $baseProduct = Product::factory()->published()->create([
            'name' => 'Starter Kit',
            'price' => 180,
        ]);
        $baseProduct->categories()->sync([$category->id]);

        $upsell = Product::factory()->published()->featured()->create([
            'name' => 'Premium Add-on',
            'price' => 80,
            'badge_labels' => ['Bestseller'],
        ]);

        $crossSell = Product::factory()->published()->create([
            'name' => 'Matching Accessory',
            'price' => 45,
        ]);

        $baseProduct->upsellProducts()->sync([$upsell->id]);
        $baseProduct->crossSellProducts()->sync([$crossSell->id]);

        $sessionId = 'cart-session-suggestions';

        $response = $this->postJson(route('api.cart.items.add'), [
            'product_id' => $baseProduct->id,
            'quantity' => 1,
        ], ['X-Cart-Session-Id' => $sessionId])->assertOk();

        $response->assertJsonPath('cart.suggestions.upsells.0.name', 'Premium Add-on');
        $response->assertJsonPath('cart.suggestions.upsells.0.type', 'upsell');
        $response->assertJsonPath('cart.suggestions.upsells.0.badges.0', 'Bestseller');
        $response->assertJsonPath('cart.suggestions.cross_sells.0.name', 'Matching Accessory');
        $response->assertJsonPath('cart.suggestions.cross_sells.0.type', 'cross_sell');
    }
}
