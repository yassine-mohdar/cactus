<?php

namespace App\Modules\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InternalNote extends Model
{
    protected $fillable = [
        'notable_type', 'notable_id', 'content', 'is_pinned', 'created_by',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────
    public function notable(): MorphTo
    {
        return $this->morphTo();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    // ── Scopes ─────────────────────────────────────────────
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('created_at');
    }

    // ── Helpers ─────────────────────────────────────────────
    public function togglePin(): void
    {
        $this->update(['is_pinned' => !$this->is_pinned]);
    }

    /**
     * Create a note for any notable model.
     */
    public static function addTo(Model $notable, string $content, int $userId, bool $pinned = false): self
    {
        return static::create([
            'notable_type' => get_class($notable),
            'notable_id' => $notable->id,
            'content' => $content,
            'created_by' => $userId,
            'is_pinned' => $pinned,
        ]);
    }
}
