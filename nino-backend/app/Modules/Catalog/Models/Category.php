<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'image_path',
        'is_active',
        'sort_order',
        'meta_title',
        'meta_description',
        'canonical_url',
        'og_title',
        'og_description',
        'og_image',
        'noindex',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'noindex' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (Category $category): void {
            $category->slug = static::buildUniqueSlug($category);
        });
    }

    protected static function newFactory(): Factory
    {
        return \Database\Factories\CategoryFactory::new();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function childrenRecursive(): HasMany
    {
        return $this->children()->ordered()->with('childrenRecursive');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * @return Collection<int, Category>
     */
    public function ancestors(): Collection
    {
        $ancestors = collect();
        $current = $this->parent;

        while ($current) {
            $ancestors->prepend($current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * @return Collection<int, Category>
     */
    public function descendants(): Collection
    {
        $this->loadMissing('children.childrenRecursive');

        return $this->children->flatMap(function (Category $child): Collection {
            return collect([$child])->merge($child->descendants());
        })->values();
    }

    public function depth(): int
    {
        return $this->ancestors()->count();
    }

    public function seoTitle(): string
    {
        return trim((string) ($this->meta_title ?: $this->name));
    }

    public function seoDescription(int $limit = 160): ?string
    {
        $source = trim((string) ($this->meta_description ?: $this->description));

        if ($source === '') {
            return null;
        }

        return Str::limit(strip_tags($source), $limit);
    }

    public function canonicalUrl(): string
    {
        $configured = trim((string) $this->canonical_url);

        if ($configured !== '') {
            return $configured;
        }

        return rtrim(config('app.url'), '/').'/categories/'.$this->slug;
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

        return Str::limit(strip_tags($source), $limit);
    }

    public function ogImage(): ?string
    {
        $configured = trim((string) $this->og_image);

        if ($configured !== '') {
            return $configured;
        }

        return $this->image_path;
    }

    public function treePath(): string
    {
        return $this->ancestors()
            ->pluck('name')
            ->push($this->name)
            ->implode(' > ');
    }

    private static function buildUniqueSlug(self $category): string
    {
        $source = trim((string) ($category->slug ?: $category->name));
        $baseSlug = Str::slug($source);

        if ($baseSlug === '') {
            $baseSlug = 'category';
        }

        return static::resolveUniqueSlug($baseSlug, $category->id);
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
