<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'sale_price',
        'quantity',
        'image_path',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function optionValues()
    {
        return $this->belongsToMany(ProductOptionValue::class);
    }
}
