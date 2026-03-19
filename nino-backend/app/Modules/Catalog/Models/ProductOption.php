<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOption extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'position',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function values()
    {
        return $this->hasMany(ProductOptionValue::class)->orderBy('position');
    }
}
