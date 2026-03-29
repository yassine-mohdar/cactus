<?php

namespace App\Modules\Community\Models;

use App\Models\User;
use App\Modules\Community\Enums\CommunityReportReason;
use App\Modules\Community\Enums\CommunityReportState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CommunityReport extends Model
{
    protected $table = 'community_reports';

    protected $fillable = [
        'group_id',
        'reportable_type',
        'reportable_id',
        'reporter_id',
        'reason',
        'state',
        'description',
        'reviewed_by',
        'reviewed_at',
        'resolution_notes',
        'metadata',
    ];

    protected $casts = [
        'reason' => CommunityReportReason::class,
        'state' => CommunityReportState::class,
        'reviewed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function queueItem(): HasOne
    {
        return $this->hasOne(ModerationQueueItem::class, 'report_id');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('state', [
            CommunityReportState::SUBMITTED,
            CommunityReportState::QUEUED,
            CommunityReportState::IN_REVIEW,
        ]);
    }

    public function queueForModeration(int $priority = 100): ModerationQueueItem
    {
        $queueItem = $this->queueItem()->firstOrCreate(
            ['report_id' => $this->id],
            [
                'group_id' => $this->group_id,
                'moderatable_type' => $this->reportable_type,
                'moderatable_id' => $this->reportable_id,
                'status' => \App\Modules\Community\Enums\ModerationQueueStatus::OPEN,
                'priority' => $priority,
                'queued_at' => now(),
            ],
        );

        if ($this->state === CommunityReportState::SUBMITTED) {
            $this->update(['state' => CommunityReportState::QUEUED]);
        }

        return $queueItem;
    }

    public function startReview(?User $reviewer = null): void
    {
        $this->update([
            'state' => CommunityReportState::IN_REVIEW,
            'reviewed_by' => $reviewer?->getKey(),
            'reviewed_at' => now(),
        ]);
    }

    public function resolve(User $reviewer, ?string $notes = null): void
    {
        $this->update([
            'state' => CommunityReportState::ACTION_TAKEN,
            'reviewed_by' => $reviewer->getKey(),
            'reviewed_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }

    public function dismiss(User $reviewer, ?string $notes = null): void
    {
        $this->update([
            'state' => CommunityReportState::DISMISSED,
            'reviewed_by' => $reviewer->getKey(),
            'reviewed_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }
}
