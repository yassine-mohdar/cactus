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

    public function option()
    {
        return $this->belongsTo(ProductOption::class, 'product_option_id');
    }

    public function variants()
    {
        return $this->belongsToMany(ProductVariant::class);
    }
}
