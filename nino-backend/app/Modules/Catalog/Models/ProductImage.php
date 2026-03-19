<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'path',
        'is_featured',
        'sort_order',
        'alt_text',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
