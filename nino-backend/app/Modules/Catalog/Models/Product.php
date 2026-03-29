<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    private static ?bool $supportsTagsCache = null;

    public const TYPE_SIMPLE = 'simple';
    public const TYPE_VARIABLE = 'variable';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    public const TYPES = [
        self::TYPE_SIMPLE,
        self::TYPE_VARIABLE,
    ];

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_ARCHIVED,
    ];

    protected $fillable = [
        'name',
        'slug',
        'type',
        'status',
        'sku',
        'barcode',
        'short_description',
        'description',
        'price',
        'sale_price',
        'cost_price',
        'quantity',
        'weight',
        'length',
        'width',
        'height',
        'is_featured',
        'badge_labels',
        'meta_title',
        'meta_description',
        'canonical_url',
        'og_title',
        'og_description',
        'og_image',
        'noindex',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'badge_labels' => 'array',
        'noindex' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::saving(function (Product $product): void {
            $product->slug = static::buildUniqueSlug($product);
        });
    }

    protected static function newFactory(): Factory
    {
        return \Database\Factories\ProductFactory::new();
    }

    public static function supportsTags(): bool
    {
        if (self::$supportsTagsCache !== null) {
            return self::$supportsTagsCache;
        }

        return self::$supportsTagsCache = Schema::hasTable('product_tags')
            && Schema::hasTable('product_product_tag');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeArchived($query)
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    public function scopeSimple($query)
    {
        return $query->where('type', self::TYPE_SIMPLE);
    }

    public function scopeVariable($query)
    {
        return $query->where('type', self::TYPE_VARIABLE);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function relatedProducts(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'product_related_products',
            'product_id',
            'related_product_id',
        );
    }

    public function upsellProducts(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'product_upsells',
            'product_id',
            'upsell_product_id',
        );
    }

    public function crossSellProducts(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'product_cross_sells',
            'product_id',
            'cross_sell_product_id',
        );
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ProductTag::class, 'product_product_tag')
            ->orderBy('name');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function featuredImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_featured', true);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('position');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function isSimple(): bool
    {
        return $this->type === self::TYPE_SIMPLE;
    }

    public function isVariable(): bool
    {
        return $this->type === self::TYPE_VARIABLE;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    public function typeLabel(): string
    {
        return $this->isVariable() ? 'Variable' : 'Simple';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_ARCHIVED => 'Archived',
            default => 'Draft',
        };
    }

    /**
     * @return array<int, string>
     */
    public function badges(): array
    {
        return collect($this->badge_labels ?? [])
            ->map(fn ($badge) => trim((string) $badge))
            ->filter()
            ->unique(fn (string $badge) => Str::lower($badge))
            ->values()
            ->all();
    }

    public function hasSalePrice(): bool
    {
        return $this->sale_price !== null
            && ($this->price === null || (float) $this->sale_price < (float) $this->price);
    }

    public function effectivePrice(): ?float
    {
        $price = $this->hasSalePrice() ? $this->sale_price : $this->price;

        return $price !== null ? (float) $price : null;
    }

    public function hasCostPrice(): bool
    {
        return $this->cost_price !== null;
    }

    public function marginAmount(): ?float
    {
        if ($this->effectivePrice() === null || $this->cost_price === null) {
            return null;
        }

        return round($this->effectivePrice() - (float) $this->cost_price, 2);
    }

    public function marginPercent(): ?float
    {
        if ($this->effectivePrice() === null || $this->cost_price === null || (float) $this->effectivePrice() <= 0.0) {
            return null;
        }

        return round(($this->marginAmount() / $this->effectivePrice()) * 100, 2);
    }

    public function hasPhysicalProfile(): bool
    {
        return $this->weight !== null
            || $this->length !== null
            || $this->width !== null
            || $this->height !== null;
    }

    public function dimensionsSummary(): ?string
    {
        if ($this->length === null || $this->width === null || $this->height === null) {
            return null;
        }

        return sprintf(
            '%s x %s x %s cm',
            rtrim(rtrim(number_format((float) $this->length, 2, '.', ''), '0'), '.'),
            rtrim(rtrim(number_format((float) $this->width, 2, '.', ''), '0'), '.'),
            rtrim(rtrim(number_format((float) $this->height, 2, '.', ''), '0'), '.')
        );
    }

    public function excerpt(int $limit = 140): ?string
    {
        $source = $this->short_description ?: strip_tags((string) $this->description);
        $source = trim($source);

        if ($source === '') {
            return null;
        }

        return Str::limit($source, $limit);
    }

    public function seoTitle(): string
    {
        return trim((string) ($this->meta_title ?: $this->name));
    }

    public function seoDescription(int $limit = 160): ?string
    {
        $source = trim((string) ($this->meta_description ?: $this->excerpt($limit)));

        if ($source === '') {
            return null;
        }

        return Str::limit($source, $limit);
    }

    public function canonicalUrl(): string
    {
        $configured = trim((string) $this->canonical_url);

        if ($configured !== '') {
            return $configured;
        }

        return rtrim(config('app.url'), '/').'/products/'.$this->slug;
    }

    public function ogTitle(): string
    {
        return trim((string) ($this->og_title ?: $this->seoTitle()));
    }

    public function ogDescription(int $limit = 200): ?string
    {
        $source = trim((string) ($this->og_description ?: $this->seoDescription($limit)));

        if ($source === '') {
            return null;
        }

        return Str::limit($source, $limit);
    }

    public function ogImage(): ?string
    {
        $configured = trim((string) $this->og_image);

        if ($configured !== '') {
            return $configured;
        }

        return $this->primary_image;
    }

    public function supportsRichDescription(): bool
    {
        return trim((string) $this->description) !== '';
    }

    public function renderedDescriptionHtml(): HtmlString
    {
        $description = trim((string) $this->description);

        if ($description === '') {
            return new HtmlString('');
        }

        $normalized = preg_replace("/\r\n|\r/", "\n", $description) ?? $description;
        $containsHtml = $normalized !== strip_tags($normalized);

        if ($containsHtml) {
            $safeHtml = strip_tags($normalized, '<p><br><ul><ol><li><strong><em><b><i><h2><h3><blockquote><a>');

            return new HtmlString($safeHtml);
        }

        return new HtmlString(nl2br(e($normalized)));
    }

    public function getPrimaryImageAttribute()
    {
        if ($this->featuredImage) {
            return $this->featuredImage->path;
        }

        $fallback = $this->images->first();
        return $fallback ? $fallback->path : null;
    }

    private static function buildUniqueSlug(self $product): string
    {
        $source = trim((string) ($product->slug ?: $product->name));
        $baseSlug = Str::slug($source);

        if ($baseSlug === '') {
            $baseSlug = 'product';
        }

        return static::resolveUniqueSlug($baseSlug, $product->id);
    }

    private static function resolveUniqueSlug(string $baseSlug, ?int $ignoreId = null): string
    {
        $slug = $baseSlug;
        $suffix = 2;

        while (
            static::query()
                ->where('slug', $slug)
                ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
