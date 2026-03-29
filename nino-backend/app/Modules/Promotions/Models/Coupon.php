<?php

namespace App\Modules\Promotions\Models;

use App\Modules\Promotions\Enums\CouponStatus;
use App\Modules\Promotions\Enums\CouponType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'description', 'type', 'value', 'max_discount',
        'is_active', 'starts_at', 'ends_at',
        'usage_limit', 'usage_count', 'per_user_limit',
        'minimum_cart_total', 'maximum_cart_total', 'minimum_items',
        'product_ids', 'category_ids', 'excluded_product_ids', 'excluded_category_ids', 'customer_ids',
        'is_stackable', 'stackable_with', 'created_by',
    ];

    protected $casts = [
        'type' => CouponType::class,
        'is_active' => 'boolean',
        'value' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'minimum_cart_total' => 'decimal:2',
        'maximum_cart_total' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'per_user_limit' => 'integer',
        'minimum_items' => 'integer',
        'product_ids' => 'array',
        'category_ids' => 'array',
        'excluded_product_ids' => 'array',
        'excluded_category_ids' => 'array',
        'customer_ids' => 'array',
        'is_stackable' => 'boolean',
        'stackable_with' => 'array',
    ];

    // ── Relationships ──────────────────────────────────────
    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    // ── Computed Status ────────────────────────────────────
    public function computedStatus(): CouponStatus
    {
        if (!$this->is_active) {
            return CouponStatus::INACTIVE;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return CouponStatus::EXPIRED;
        }

        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return CouponStatus::EXHAUSTED;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return CouponStatus::SCHEDULED;
        }

        return CouponStatus::ACTIVE;
    }

    // ── Scopes ─────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')
                    ->orWhereColumn('usage_count', '<', 'usage_limit');
            });
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', strtoupper(trim($code)));
    }

    // ── Helpers ─────────────────────────────────────────────
    /**
     * Calculate the discount for a given cart total.
     */
    public function calculateDiscount(float $cartTotal): float
    {
        $discount = match($this->type) {
            CouponType::FIXED => min($this->value, $cartTotal),
            CouponType::PERCENTAGE => $cartTotal * ($this->value / 100),
        };

        // Apply cap for percentage type
        if ($this->type === CouponType::PERCENTAGE && $this->max_discount) {
            $discount = min($discount, $this->max_discount);
        }

        return round(max(0, $discount), 2);
    }

    /**
     * Format the discount value for display.
     */
    public function formattedValue(): string
    {
        return $this->type->formatValue($this->value);
    }

    /**
     * Get usage count for a specific customer.
     */
    public function usageCountForCustomer(int $customerId): int
    {
        return $this->usages()->where('customer_id', $customerId)->count();
    }

    /**
     * Check if the coupon has product/category targeting enabled.
     */
    public function hasTargeting(): bool
    {
        return !empty($this->product_ids)
            || !empty($this->category_ids)
            || !empty($this->excluded_product_ids)
            || !empty($this->excluded_category_ids)
            || !empty($this->customer_ids);
    }

    /**
     * Remaining uses before exhaustion.
     */
    public function remainingUses(): ?int
    {
        if (!$this->usage_limit) return null;
        return max(0, $this->usage_limit - $this->usage_count);
    }
}
