<?php

namespace App\Modules\Checkout\Services;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Checkout\Models\Cart;
use App\Modules\Checkout\Models\CartItem;
use App\Modules\Finance\Services\FinanceSettingsService;
use App\Modules\Promotions\Services\CouponService;
use App\Modules\Shipping\Services\ShippingSettingsService;
use Illuminate\Support\Str;

class CartService
{
    public function __construct(
        private readonly FinanceSettingsService $financeSettings,
        private readonly ShippingSettingsService $shippingSettings,
        private readonly CouponService $couponService,
    ) {}

    /**
     * Resolve the cart for the current guest or authenticated user.
     */
    public function getCart(?User $user, ?string $sessionId): Cart
    {
        if ($user) {
            return Cart::firstOrCreate(
                ['user_id' => $user->id],
                ['currency' => $this->financeSettings->baseCurrency()],
            );
        }

        if (!$sessionId) {
            $sessionId = Str::uuid()->toString();
        }

        return Cart::firstOrCreate(
            ['session_id' => $sessionId],
            ['currency' => $this->financeSettings->baseCurrency()],
        );
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

    /**
     * @return array{valid: bool, coupon?: \App\Modules\Promotions\Models\Coupon, discount?: float, formatted_discount?: string, new_total?: float, error?: string, error_code?: string}
     */
    public function applyCoupon(Cart $cart, string $code, ?User $user = null): array
    {
        $validation = $this->couponService->validate($code, $this->buildCouponContext($cart, $user));

        if (! ($validation['valid'] ?? false)) {
            return $validation;
        }

        $cart->update([
            'coupon_code' => strtoupper(trim($code)),
        ]);

        return $validation;
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
        $cart->loadMissing([
            'items.product.categories',
            'items.product.featuredImage',
            'items.product.upsellProducts.featuredImage',
            'items.product.crossSellProducts.featuredImage',
        ]);
        
        $subtotal = $cart->subtotal;
        $appliedCoupon = $this->resolveAppliedCoupon($cart);
        $discount = (float) ($appliedCoupon['discount'] ?? 0);
        $lineItemsCount = (int) $cart->items->count();
        $unitsCount = (int) $cart->items->sum('quantity');
        $taxTotal = 0.0;
        
        // P7-CART-05 & P7-CART-06 Shipping logic
        $shippingThreshold = $this->shippingSettings->freeShippingThreshold();
        $shippingEstimate = $subtotal >= $shippingThreshold ? 0 : $this->shippingSettings->defaultShippingCost();
        $remainingForFreeShipping = max(0, $shippingThreshold - $subtotal);
        
        $grandTotal = ($subtotal - $discount) + $shippingEstimate + $taxTotal;
        $suggestions = $this->buildSuggestions($cart);

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
                'line_items_count' => $lineItemsCount,
                'units_count' => $unitsCount,
                'subtotal' => round($subtotal, 2),
                'tax' => round($taxTotal, 2),
                'discount' => round($discount, 2),
                'shipping_estimate' => round($shippingEstimate, 2),
                'grand_total' => max(0, round($grandTotal, 2)),
            ],
            'shipping' => [
                'method_code' => $this->shippingSettings->defaultMethodCode(),
                'estimated_cost' => round($shippingEstimate, 2),
                'free_shipping_threshold' => round($shippingThreshold, 2),
                'remaining_for_free_shipping' => round($remainingForFreeShipping, 2),
                'is_free' => $shippingEstimate <= 0,
            ],
            'coupon' => [
                'code' => $cart->coupon_code,
                'applied' => (bool) ($appliedCoupon['valid'] ?? false),
                'discount' => round($discount, 2),
                'formatted_discount' => $appliedCoupon['formatted_discount'] ?? null,
                'error' => $appliedCoupon['error'] ?? null,
            ],
            // P7-CART-06
            'free_shipping_progress' => [
                'threshold' => $shippingThreshold,
                'remaining' => $remainingForFreeShipping,
                'achieved' => ($subtotal >= $shippingThreshold),
            ],
            'suggestions' => $suggestions,
        ];
    }

    /**
     * @return array{cart_total: float, items_count: int, customer_id: int|null, product_ids: array<int, int>, category_ids: array<int, int>}
     */
    private function buildCouponContext(Cart $cart, ?User $user = null): array
    {
        $cart->loadMissing('items.product.categories');

        $productIds = $cart->items
            ->pluck('product_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $categoryIds = $cart->items
            ->flatMap(function (CartItem $item) {
                /** @var Product|null $product */
                $product = $item->product;

                return $product?->categories?->pluck('id') ?? collect();
            })
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return [
            'cart_total' => (float) $cart->subtotal,
            'items_count' => (int) $cart->items->sum('quantity'),
            'customer_id' => $user?->id ?? $cart->user_id,
            'product_ids' => $productIds,
            'category_ids' => $categoryIds,
        ];
    }

    /**
     * @return array{valid?: bool, discount?: float, formatted_discount?: string, error?: string}|array{}
     */
    private function resolveAppliedCoupon(Cart $cart): array
    {
        $couponCode = trim((string) $cart->coupon_code);

        if ($couponCode === '') {
            return [];
        }

        $validation = $this->couponService->validate($couponCode, $this->buildCouponContext($cart, $cart->user));

        if (! ($validation['valid'] ?? false)) {
            return [
                'error' => $validation['error'] ?? 'Coupon is no longer valid.',
            ];
        }

        return $validation;
    }

    /**
     * @return array{upsells: array<int, array<string, mixed>>, cross_sells: array<int, array<string, mixed>>}
     */
    private function buildSuggestions(Cart $cart): array
    {
        $cartProductIds = $cart->items
            ->pluck('product_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $buildPayload = function ($products, string $type) use ($cartProductIds) {
            return $products
                ->filter(fn ($product) => ! $cartProductIds->contains((int) $product->id))
                ->filter(fn ($product) => $product->isPublished())
                ->unique('id')
                ->take(4)
                ->map(function ($product) use ($type) {
                    return [
                        'id' => $product->id,
                        'type' => $type,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'price' => $product->effectivePrice() ?? $product->price,
                        'image' => $product->primary_image,
                        'badges' => $product->badges(),
                    ];
                })
                ->values()
                ->all();
        };

        $upsells = $cart->items
            ->flatMap(fn (CartItem $item) => $item->product?->upsellProducts ?? collect());

        $crossSells = $cart->items
            ->flatMap(fn (CartItem $item) => $item->product?->crossSellProducts ?? collect());

        return [
            'upsells' => $buildPayload($upsells, 'upsell'),
            'cross_sells' => $buildPayload($crossSells, 'cross_sell'),
        ];
    }
}
