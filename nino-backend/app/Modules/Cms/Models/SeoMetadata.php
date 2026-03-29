<?php

namespace App\Modules\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Polymorphic SEO metadata — attachable to any entity.
 *
 * Usage:
 *   $product->seo()->create([...]);
 *   $product->seo->meta_title;
 */
class SeoMetadata extends Model
{
    protected $table = 'seo_metadata';

    protected $fillable = [
        'seoable_type', 'seoable_id',
        'meta_title', 'meta_description', 'canonical_url',
        'og_title', 'og_description', 'og_image',
        'noindex', 'structured_data',
    ];

    protected $casts = [
        'noindex' => 'boolean',
        'structured_data' => 'array',
    ];

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }
}
