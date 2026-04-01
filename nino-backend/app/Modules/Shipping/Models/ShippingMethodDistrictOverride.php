<?php

namespace App\Modules\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingMethodDistrictOverride extends Model
{
    protected $fillable = [
        'shipping_method_id',
        'shipping_carrier_district_id',
        'forced_price',
    ];

    protected $casts = [
        'forced_price' => 'decimal:2',
    ];

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(ShippingCarrierDistrict::class, 'shipping_carrier_district_id');
    }
}
