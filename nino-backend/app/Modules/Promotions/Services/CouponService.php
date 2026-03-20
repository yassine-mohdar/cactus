<?php

namespace App\Modules\Promotions\Services;

use App\Modules\Promotions\Enums\CouponStatus;
use App\Modules\Promotions\Models\Coupon;
use App\Modules\Promotions\Models\CouponUsage;
use Illuminate\Support\Facades\DB;

/**
 * Coupon Validation & Application Engine
 *
 * Validates coupon eligibility against cart context,
 * applies discounts, records usage, and provides structured error messages.
 *
 * Usage:
 *   $result = app(CouponService::class)->validate('SAVE10', [
 *       'cart_total' => 250.00,
 *       'items_count' => 3,
 *       'customer_id' => 42,
 *       'product_ids' => [1, 5, 8],
 *       'category_ids' => [2, 3],
 *   ]);
 *
 *   if (!$result['valid']) echo $result['error'];
 *   else echo "Discount: " . $result['discount'];
 */
class CouponService
{
    /**
     * Validate a coupon code against cart context.
     *
     * @return array{valid: bool, coupon?: Coupon, discount?: float, error?: string, error_code?: string}
     */
    public function validate(string $code, array $context = []): array
    {
        $coupon = Coupon::byCode($code)->first();

        if (!$coupon) {
            return $this->fail('Coupon code not found.', 'COUPON_NOT_FOUND');
        }

        // Run all eligibility rules
        $rules = [
            'checkActive',
            'checkDateRange',
            'checkUsageLimit',
            'checkPerUserLimit',
            'checkMinimumCart',
            'checkMaximumCart',
            'checkMinimumItems',
            'checkCustomerRestriction',
            'checkProductTargeting',
            'checkCategoryTargeting',
            'checkExclusions',
        ];

        foreach ($rules as $rule) {
            $result = $this->$rule($coupon, $context);
            if ($result !== null) {
                return $result;
            }
        }

        // Calculate discount
        $cartTotal = $context['cart_total'] ?? 0;
        $discount = $coupon->calculateDiscount($cartTotal);

        return [
            'valid' => true,
            'coupon' => $coupon,
            'discount' => $discount,
            'formatted_discount' => $coupon->type->formatValue($coupon->value),
            'new_total' => round(max(0, $cartTotal - $discount), 2),
        ];
    }

    /**
     * Apply a validated coupon to an order (records usage + increments counter).
     */
    public function apply(Coupon $coupon, int $orderId, float $orderTotal, ?int $customerId = null): CouponUsage
    {
        return DB::transaction(function () use ($coupon, $orderId, $orderTotal, $customerId) {
            $discount = $coupon->calculateDiscount($orderTotal);

            $usage = CouponUsage::create([
                'coupon_id' => $coupon->id,
                'order_id' => $orderId,
                'customer_id' => $customerId,
                'discount_amount' => $discount,
                'order_total_before' => $orderTotal,
                'order_total_after' => round(max(0, $orderTotal - $discount), 2),
            ]);

            $coupon->increment('usage_count');

            return $usage;
        });
    }

    /**
     * Remove a coupon application (reverses usage).
     */
    public function remove(CouponUsage $usage): void
    {
        DB::transaction(function () use ($usage) {
            $coupon = $usage->coupon;
            $usage->delete();
            $coupon->decrement('usage_count');
        });
    }

    // ── Eligibility Rules ──────────────────────────────────

    private function checkActive(Coupon $coupon, array $ctx): ?array
    {
        if (!$coupon->is_active) {
            return $this->fail('This coupon is currently disabled.', 'COUPON_INACTIVE');
        }
        return null;
    }

    private function checkDateRange(Coupon $coupon, array $ctx): ?array
    {
        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return $this->fail('This coupon is not yet active. It starts on ' . $coupon->starts_at->format('M d, Y') . '.', 'COUPON_NOT_STARTED');
        }
        if ($coupon->ends_at && $coupon->ends_at->isPast()) {
            return $this->fail('This coupon has expired.', 'COUPON_EXPIRED');
        }
        return null;
    }

    private function checkUsageLimit(Coupon $coupon, array $ctx): ?array
    {
        if ($coupon->usage_limit && $coupon->usage_count >= $coupon->usage_limit) {
            return $this->fail('This coupon has reached its usage limit.', 'COUPON_EXHAUSTED');
        }
        return null;
    }

    private function checkPerUserLimit(Coupon $coupon, array $ctx): ?array
    {
        $customerId = $ctx['customer_id'] ?? null;
        if ($coupon->per_user_limit && $customerId) {
            $userUsage = $coupon->usageCountForCustomer($customerId);
            if ($userUsage >= $coupon->per_user_limit) {
                return $this->fail('You have already used this coupon the maximum number of times.', 'COUPON_PER_USER_LIMIT');
            }
        }
        return null;
    }

    private function checkMinimumCart(Coupon $coupon, array $ctx): ?array
    {
        $cartTotal = $ctx['cart_total'] ?? 0;
        if ($coupon->minimum_cart_total && $cartTotal < $coupon->minimum_cart_total) {
            return $this->fail('Minimum order total of ' . number_format($coupon->minimum_cart_total, 2) . ' MAD required.', 'MINIMUM_CART_NOT_MET');
        }
        return null;
    }

    private function checkMaximumCart(Coupon $coupon, array $ctx): ?array
    {
        $cartTotal = $ctx['cart_total'] ?? 0;
        if ($coupon->maximum_cart_total && $cartTotal > $coupon->maximum_cart_total) {
            return $this->fail('This coupon is not valid for orders over ' . number_format($coupon->maximum_cart_total, 2) . ' MAD.', 'MAXIMUM_CART_EXCEEDED');
        }
        return null;
    }

    private function checkMinimumItems(Coupon $coupon, array $ctx): ?array
    {
        $itemsCount = $ctx['items_count'] ?? 0;
        if ($coupon->minimum_items && $itemsCount < $coupon->minimum_items) {
            return $this->fail("At least {$coupon->minimum_items} items required.", 'MINIMUM_ITEMS_NOT_MET');
        }
        return null;
    }

    private function checkCustomerRestriction(Coupon $coupon, array $ctx): ?array
    {
        $customerId = $ctx['customer_id'] ?? null;
        if (!empty($coupon->customer_ids) && $customerId) {
            if (!in_array($customerId, $coupon->customer_ids)) {
                return $this->fail('This coupon is not available for your account.', 'CUSTOMER_RESTRICTED');
            }
        }
        return null;
    }

    private function checkProductTargeting(Coupon $coupon, array $ctx): ?array
    {
        $cartProductIds = $ctx['product_ids'] ?? [];
        if (!empty($coupon->product_ids) && !empty($cartProductIds)) {
            $overlap = array_intersect($coupon->product_ids, $cartProductIds);
            if (empty($overlap)) {
                return $this->fail('This coupon does not apply to any products in your cart.', 'PRODUCT_NOT_TARGETED');
            }
        }
        return null;
    }

    private function checkCategoryTargeting(Coupon $coupon, array $ctx): ?array
    {
        $cartCategoryIds = $ctx['category_ids'] ?? [];
        if (!empty($coupon->category_ids) && !empty($cartCategoryIds)) {
            $overlap = array_intersect($coupon->category_ids, $cartCategoryIds);
            if (empty($overlap)) {
                return $this->fail('This coupon does not apply to any categories in your cart.', 'CATEGORY_NOT_TARGETED');
            }
        }
        return null;
    }

    private function checkExclusions(Coupon $coupon, array $ctx): ?array
    {
        $cartProductIds = $ctx['product_ids'] ?? [];
        $cartCategoryIds = $ctx['category_ids'] ?? [];

        if (!empty($coupon->excluded_product_ids) && !empty($cartProductIds)) {
            $allExcluded = empty(array_diff($cartProductIds, $coupon->excluded_product_ids));
            if ($allExcluded) {
                return $this->fail('All products in your cart are excluded from this coupon.', 'ALL_PRODUCTS_EXCLUDED');
            }
        }

        if (!empty($coupon->excluded_category_ids) && !empty($cartCategoryIds)) {
            $allExcluded = empty(array_diff($cartCategoryIds, $coupon->excluded_category_ids));
            if ($allExcluded) {
                return $this->fail('All categories in your cart are excluded from this coupon.', 'ALL_CATEGORIES_EXCLUDED');
            }
        }

        return null;
    }

    private function fail(string $message, string $code): array
    {
        return [
            'valid' => false,
            'error' => $message,
            'error_code' => $code,
        ];
    }
}
