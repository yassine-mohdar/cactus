<?php

namespace App\Modules\Checkout\Services;

use App\Models\User;
use App\Modules\Checkout\Models\Cart;
use App\Modules\Checkout\Models\CartItem;
use Illuminate\Support\Str;

class CartService
{
    /**
     * Resolve the cart for the current guest or authenticated user.
     */
    public function getCart(?User $user, ?string $sessionId): Cart
    {
        if ($user) {
            return Cart::firstOrCreate(['user_id' => $user->id]);
        }

        if (!$sessionId) {
            $sessionId = Str::uuid()->toString();
        }

        return Cart::firstOrCreate(['session_id' => $sessionId]);
    }

    /**
     * Add or increment a product inside the cart
     */
    public function addItem(Cart $cart, int $productId, ?int $variantId = null, int $quantity = 1): CartItem
    {
        $item = CartItem::firstOrNew([
            'cart_id' => $cart->id,
            'product_id' => $productId,
            'variant_id' => $variantId,
        ]);

        $item->quantity = $item->exists ? $item->quantity + $quantity : $quantity;
        $item->save();

        return $item;
    }

    /**
     * Updates an exact quantity for a cart line. If 0, removes it.
     */
    public function updateQuantity(Cart $cart, int $itemId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeItem($cart, $itemId);
            return;
        }

        $cart->items()->where('id', $itemId)->update(['quantity' => $quantity]);
    }

    public function removeItem(Cart $cart, int $itemId): void
    {
        $cart->items()->where('id', $itemId)->delete();
    }

    public function applyCoupon(Cart $cart, string $code): void
    {
        // Validation of coupon would happen here
        $cart->update(['coupon_code' => $code]);
    }

    public function removeCoupon(Cart $cart): void
    {
        $cart->update(['coupon_code' => null]);
    }

    /**
     * Called when a guest logs in. Moves items from session cart to user cart.
     */
    public function mergeGuestIntoUserCart(string $sessionId, User $user): void
    {
        $guestCart = Cart::where('session_id', $sessionId)->first();
        if (!$guestCart) return;

        $userCart = Cart::firstOrCreate(['user_id' => $user->id]);

        foreach ($guestCart->items as $item) {
            $this->addItem($userCart, $item->product_id, $item->variant_id, $item->quantity);
        }

        $guestCart->delete(); // Clean up old guest cart
    }

    /**
     * P7-CART-04 to 07 (Summary format builder)
     */
    public function getSummary(Cart $cart): array
    {
        $cart->loadMissing('items.product'); // Load products dynamically
        
        $subtotal = $cart->subtotal;
        $discount = 0; // P7-CART-03 placeholder logic
        
        // P7-CART-05 & P7-CART-06 Shipping logic
        $shippingThreshold = 500; // Free shipping > MAD 500
        $shippingEstimate = $subtotal > $shippingThreshold ? 0 : 45.00;
        
        $grandTotal = ($subtotal - $discount) + $shippingEstimate;

        return [
            'id' => $cart->id,
            'session_id' => $cart->session_id,
            'currency' => $cart->currency,
            'items' => $cart->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'name' => $item->product->name ?? 'Unknown',
                    'unit_price' => $item->product->price ?? 0,
                    'quantity' => $item->quantity,
                    'line_total' => $item->line_total,
                ];
            }),
            'totals' => [
                'subtotal' => round($subtotal, 2),
                'discount' => round($discount, 2),
                'shipping_estimate' => round($shippingEstimate, 2),
                'grand_total' => max(0, round($grandTotal, 2)),
            ],
            'coupon' => $cart->coupon_code,
            // P7-CART-06
            'free_shipping_progress' => [
                'threshold' => $shippingThreshold,
                'remaining' => max(0, $shippingThreshold - $subtotal),
                'achieved' => ($subtotal >= $shippingThreshold),
            ]
        ];
    }
}
