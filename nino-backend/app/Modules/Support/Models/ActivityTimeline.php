<?php

namespace App\Modules\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityTimeline extends Model
{
    protected $table = 'activity_timeline';

    protected $fillable = [
        'subject_type', 'subject_id', 'action', 'description', 'metadata', 'performed_by',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // ── Relationships ──────────────────────────────────────
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'performed_by');
    }

    // ── Scopes ─────────────────────────────────────────────
    public function scopeRecent($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    // ── Factory Methods ────────────────────────────────────
    /**
     * Log an activity for any subject model.
     */
    public static function log(
        Model $subject,
        string $action,
        string $description,
        ?int $performedBy = null,
        ?array $metadata = null
    ): self {
        return static::create([
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
            'action' => $action,
            'description' => $description,
            'performed_by' => $performedBy ?? auth()->id(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log a status change.
     */
    public static function logStatusChange(Model $subject, string $from, string $to, ?int $performedBy = null): self
    {
        return static::log(
            $subject,
            'status_changed',
            "Status changed from {$from} to {$to}",
            $performedBy,
            ['old_value' => $from, 'new_value' => $to]
        );
    }
}
