<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Organizations\Models\Organization;

class StockItem extends Model
{
    protected $table = 'inventory_stock_items';

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'branch_id',
        'quantity',
        'reserved_quantity',
        'low_stock_threshold',
        'status',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function branch()
    {
        return $this->belongsTo(Organization::class, 'branch_id');
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }
    
    /**
     * Get the available physical stock (quantity minus reserved).
     */
    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->reserved_quantity);
    }

    public function getSkuAttribute(): ?string
    {
        return $this->variant?->sku ?? $this->product?->sku;
    }

    public function getUnitPriceAttribute(): float
    {
        return (float) ($this->variant?->price ?? $this->product?->price ?? 0);
    }
}
