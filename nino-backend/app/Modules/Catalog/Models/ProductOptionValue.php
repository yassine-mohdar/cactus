<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOptionValue extends Model
{
    protected $fillable = [
        'product_option_id',
        'value',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function option()
    {
        return $this->belongsTo(ProductOption::class, 'product_option_id');
    }

    public function variants()
    {
        return $this->belongsToMany(
            ProductVariant::class,
            'product_variant_option_value',
            'option_value_id',
            'product_variant_id',
        );
    }
}
