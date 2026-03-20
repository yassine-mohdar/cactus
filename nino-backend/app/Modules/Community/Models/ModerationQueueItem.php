<?php

namespace App\Modules\Community\Models;

use App\Models\User;
use App\Modules\Community\Enums\CommunityReportState;
use App\Modules\Community\Enums\ModerationQueueStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModerationQueueItem extends Model
{
    protected $table = 'community_moderation_queue_items';

    protected $fillable = [
        'group_id',
        'report_id',
        'moderatable_type',
        'moderatable_id',
        'status',
        'priority',
        'assigned_to',
        'assigned_at',
        'queued_at',
        'resolved_at',
        'metadata',
    ];

    protected $casts = [
        'status' => ModerationQueueStatus::class,
        'priority' => 'integer',
        'assigned_at' => 'datetime',
        'queued_at' => 'datetime',
        'resolved_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(CommunityReport::class, 'report_id');
    }

    public function moderatable(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignedModerator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', [
            ModerationQueueStatus::OPEN,
            ModerationQueueStatus::ASSIGNED,
            ModerationQueueStatus::IN_REVIEW,
        ]);
    }

    public function assignTo(User $moderator): void
    {
        $this->update([
            'status' => ModerationQueueStatus::ASSIGNED,
            'assigned_to' => $moderator->getKey(),
            'assigned_at' => now(),
        ]);
    }

    public function markInReview(?User $moderator = null): void
    {
        $this->update([
            'status' => ModerationQueueStatus::IN_REVIEW,
            'assigned_to' => $moderator?->getKey() ?? $this->assigned_to,
            'assigned_at' => $this->assigned_at ?? now(),
        ]);

        if ($this->report) {
            $this->report->startReview($moderator ?? $this->assignedModerator);
        }
    }

    public function resolve(?User $moderator = null, ?string $notes = null): void
    {
        $this->update([
            'status' => ModerationQueueStatus::RESOLVED,
            'resolved_at' => now(),
        ]);

        if ($this->report && $moderator) {
            $this->report->resolve($moderator, $notes);
        } elseif ($this->report) {
            $this->report->update([
                'state' => CommunityReportState::ACTION_TAKEN,
                'resolution_notes' => $notes,
                'reviewed_at' => now(),
            ]);
        }
    }

    public function dismiss(?User $moderator = null, ?string $notes = null): void
    {
        $this->update([
            'status' => ModerationQueueStatus::DISMISSED,
            'resolved_at' => now(),
        ]);

        if ($this->report && $moderator) {
            $this->report->dismiss($moderator, $notes);
        } elseif ($this->report) {
            $this->report->update([
                'state' => CommunityReportState::DISMISSED,
                'resolution_notes' => $notes,
                'reviewed_at' => now(),
            ]);
        }
    }
}
