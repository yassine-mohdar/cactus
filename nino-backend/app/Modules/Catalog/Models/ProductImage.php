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
        'sort_order' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function altTextLabel(): string
    {
        return trim((string) $this->alt_text) !== '' ? (string) $this->alt_text : 'No alt text';
    }
}
