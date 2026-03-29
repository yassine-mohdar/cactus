<?php

namespace App\Modules\Checkout\Models;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'variant_id',
        'quantity',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Compute dynamic line total based on current active product price
     */
    public function getLineTotalAttribute(): float
    {
        if (!$this->product) {
            return 0;
        }

        // Logic placeholder for variant prices if variants exist
        // For now, assuming base product price
        $unitPrice = $this->product->price ?? 0;
        
        return $unitPrice * $this->quantity;
    }
}
