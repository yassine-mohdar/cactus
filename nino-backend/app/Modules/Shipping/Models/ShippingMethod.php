<?php

namespace App\Modules\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingMethod extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'carrier',
        'description',
        'base_cost',
        'free_shipping_threshold',
        'estimated_days',
        'is_enabled',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'base_cost' => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
        'is_enabled' => 'boolean',
        'metadata' => 'array',
    ];

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    /**
     * Scope to only enabled methods.
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Calculate shipping cost for a given order subtotal.
     */
    public function calculateCost(float $orderSubtotal): float
    {
        if ($this->free_shipping_threshold && $orderSubtotal >= $this->free_shipping_threshold) {
            return 0.00;
        }
        return (float) $this->base_cost;
    }
}
