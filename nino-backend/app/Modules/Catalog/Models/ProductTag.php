<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class ProductTag extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (ProductTag $tag): void {
            $tag->name = trim(preg_replace('/\s+/', ' ', (string) $tag->name) ?? '');
            $tag->slug = $tag->slug !== null && $tag->slug !== ''
                ? Str::slug($tag->slug)
                : Str::slug($tag->name);
        });
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_product_tag')
            ->orderByDesc('products.id');
    }
}
