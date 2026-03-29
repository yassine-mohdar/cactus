<?php

namespace App\Modules\Cms\Models;

use App\Modules\Cms\Enums\PostStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlogPost extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'excerpt', 'body', 'featured_image',
        'status', 'published_at', 'scheduled_at',
        'author_id', 'blog_category_id',
        'meta_title', 'meta_description', 'canonical_url',
        'og_image', 'og_title', 'og_description', 'noindex',
        'views_count',
    ];

    protected $casts = [
        'status' => PostStatus::class,
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'noindex' => 'boolean',
        'views_count' => 'integer',
    ];

    // ── Relationships ──────────────────────────────────────
    public function author(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tag');
    }

    public function seo()
    {
        return $this->morphOne(SeoMetadata::class, 'seoable');
    }

    // ── Scopes ─────────────────────────────────────────────
    public function scopePublished($query)
    {
        return $query->where('status', PostStatus::PUBLISHED)
            ->where('published_at', '<=', now());
    }

    public function scopeDraft($query)
    {
        return $query->where('status', PostStatus::DRAFT);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', PostStatus::SCHEDULED)
            ->whereNotNull('scheduled_at');
    }

    /**
     * Publish scheduled posts whose time has arrived.
     */
    public static function publishScheduled(): int
    {
        return self::scheduled()
            ->where('scheduled_at', '<=', now())
            ->update([
                'status' => PostStatus::PUBLISHED->value,
                'published_at' => now(),
            ]);
    }

    // ── Helpers ─────────────────────────────────────────────
    public function publish(): void
    {
        $this->update([
            'status' => PostStatus::PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function unpublish(): void
    {
        $this->update(['status' => PostStatus::DRAFT]);
    }

    /**
     * Resolved meta title: explicit or fallback to post title.
     */
    public function resolvedMetaTitle(): string
    {
        return $this->meta_title ?: $this->title;
    }

    public function resolvedMetaDescription(): string
    {
        return $this->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($this->excerpt ?: $this->body), 160);
    }

    public function readingTime(): int
    {
        $wordCount = str_word_count(strip_tags($this->body));
        return max(1, (int) ceil($wordCount / 200));
    }
}
