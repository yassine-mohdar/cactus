<?php

namespace App\Modules\Community\Models;

use App\Models\User;
use App\Modules\Community\Enums\GroupJoinRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupJoinRequest extends Model
{
    protected $table = 'community_group_join_requests';

    protected $fillable = [
        'group_id',
        'user_id',
        'status',
        'message',
        'reviewed_by',
        'reviewed_at',
        'decision_notes',
        'metadata',
    ];

    protected $casts = [
        'status' => GroupJoinRequestStatus::class,
        'reviewed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', GroupJoinRequestStatus::PENDING);
    }

    public function approve(?User $reviewer = null, ?string $notes = null): void
    {
        $this->update([
            'status' => GroupJoinRequestStatus::APPROVED,
            'reviewed_by' => $reviewer?->getKey(),
            'reviewed_at' => now(),
            'decision_notes' => $notes,
        ]);
    }

    public function reject(?User $reviewer = null, ?string $notes = null): void
    {
        $this->update([
            'status' => GroupJoinRequestStatus::REJECTED,
            'reviewed_by' => $reviewer?->getKey(),
            'reviewed_at' => now(),
            'decision_notes' => $notes,
        ]);
    }

    public function cancel(?string $notes = null): void
    {
        $this->update([
            'status' => GroupJoinRequestStatus::CANCELLED,
            'decision_notes' => $notes,
        ]);
    }
}
