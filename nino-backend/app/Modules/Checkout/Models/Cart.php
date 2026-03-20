<?php

namespace App\Modules\Checkout\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'user_id',
        'currency',
        'coupon_code',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Helper to compute subtotal dynamically.
     * We don't store prices in the cart database to avoid stale data.
     */
    public function getSubtotalAttribute(): float
    {
        return $this->items->sum(function (CartItem $item) {
            return $item->line_total;
        });
    }
}
