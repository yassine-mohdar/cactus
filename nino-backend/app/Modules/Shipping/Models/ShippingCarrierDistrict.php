<?php

namespace App\Modules\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingCarrierDistrict extends Model
{
    protected $fillable = [
        'shipping_carrier_id',
        'external_id',
        'city',
        'district_name',
        'arabic_name',
        'price',
        'estimated_delivery',
        'is_pickup',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_pickup' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function shippingCarrier(): BelongsTo
    {
        return $this->belongsTo(ShippingCarrier::class);
    }
}
