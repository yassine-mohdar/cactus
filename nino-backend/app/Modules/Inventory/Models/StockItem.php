<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Organizations\Models\Organization;
use Database\Factories\StockItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockItem extends Model
{
    use HasFactory;

    protected $table = 'inventory_stock_items';

    public const STATUS_IN_STOCK = 'in_stock';
    public const STATUS_LOW_STOCK = 'low_stock';
    public const STATUS_OUT_OF_STOCK = 'out_of_stock';

    public const STATUSES = [
        self::STATUS_IN_STOCK,
        self::STATUS_LOW_STOCK,
        self::STATUS_OUT_OF_STOCK,
    ];

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'branch_id',
        'quantity',
        'reserved_quantity',
        'low_stock_threshold',
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
    ];

    protected static function newFactory(): Factory
    {
        return StockItemFactory::new();
    }

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

    public function outgoingTransfers()
    {
        return $this->hasMany(StockTransfer::class, 'source_stock_item_id');
    }

    public function incomingTransfers()
    {
        return $this->hasMany(StockTransfer::class, 'destination_stock_item_id');
    }

    public function scopeInStock($query)
    {
        return $query->where('status', self::STATUS_IN_STOCK);
    }

    public function scopeLowStock($query)
    {
        return $query->where('status', self::STATUS_LOW_STOCK);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('status', self::STATUS_OUT_OF_STOCK);
    }

    public function scopeProductLevel($query)
    {
        return $query->whereNull('product_variant_id');
    }

    public function scopeVariantLevel($query)
    {
        return $query->whereNotNull('product_variant_id');
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeGlobalStock($query)
    {
        return $query->whereNull('branch_id');
    }
    
    /**
     * Get the available physical stock (quantity minus reserved).
     */
    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->reserved_quantity);
    }

    public function isInStock(): bool
    {
        return $this->status === self::STATUS_IN_STOCK;
    }

    public function isLowStock(): bool
    {
        return $this->status === self::STATUS_LOW_STOCK;
    }

    public function isOutOfStock(): bool
    {
        return $this->status === self::STATUS_OUT_OF_STOCK;
    }

    public function isProductLevel(): bool
    {
        return $this->product_variant_id === null;
    }

    public function isVariantLevel(): bool
    {
        return $this->product_variant_id !== null;
    }

    public function isBranchAware(): bool
    {
        return $this->branch_id !== null;
    }

    public function isGlobalStock(): bool
    {
        return $this->branch_id === null;
    }

    public function hasReservations(): bool
    {
        return $this->reserved_quantity > 0;
    }

    public function reservableQuantity(): int
    {
        return $this->available_quantity;
    }

    public function canReserve(int $quantity): bool
    {
        return $quantity > 0 && $this->reservableQuantity() >= $quantity;
    }

    public function canRelease(int $quantity): bool
    {
        return $quantity > 0 && $this->reserved_quantity >= $quantity;
    }

    public function stockLevelLabel(): string
    {
        return $this->isVariantLevel() ? 'Variant stock' : 'Product stock';
    }

    public function stockScopeLabel(): string
    {
        return $this->isBranchAware() ? 'Branch stock' : 'Global stock';
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
