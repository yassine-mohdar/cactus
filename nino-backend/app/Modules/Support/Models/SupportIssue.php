<?php

namespace App\Modules\Support\Models;

use App\Modules\Support\Enums\IssuePriority;
use App\Modules\Support\Enums\IssueStatus;
use App\Modules\Support\Enums\IssueType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class SupportIssue extends Model
{
    protected $fillable = [
        'reference', 'order_id', 'customer_id', 'customer_email', 'customer_name',
        'type', 'status', 'priority', 'subject', 'description',
        'assigned_to', 'created_by', 'resolved_at', 'closed_at',
    ];

    protected $casts = [
        'type' => IssueType::class,
        'status' => IssueStatus::class,
        'priority' => IssuePriority::class,
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($issue) {
            if (empty($issue->reference)) {
                $issue->reference = 'TKT-' . strtoupper(Str::random(8));
            }
        });
    }

    // ── Relationships ──────────────────────────────────────
    public function order(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Orders\Models\Order::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(InternalNote::class, 'notable');
    }

    public function timeline(): MorphMany
    {
        return $this->morphMany(ActivityTimeline::class, 'subject');
    }

    // ── Scopes ─────────────────────────────────────────────
    public function scopeOpen($query)
    {
        return $query->where('status', IssueStatus::OPEN);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            IssueStatus::OPEN, IssueStatus::IN_PROGRESS, IssueStatus::WAITING_CUSTOMER,
        ]);
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ── Actions ────────────────────────────────────────────
    public function assignTo(int $userId): void
    {
        $this->update(['assigned_to' => $userId]);
        ActivityTimeline::log($this, 'assigned', 'Issue assigned', auth()->id(), [
            'assigned_to' => $userId,
        ]);
    }

    public function markInProgress(): void
    {
        $old = $this->status;
        $this->update(['status' => IssueStatus::IN_PROGRESS]);
        ActivityTimeline::logStatusChange($this, $old->value, IssueStatus::IN_PROGRESS->value);
    }

    public function resolve(): void
    {
        $old = $this->status;
        $this->update([
            'status' => IssueStatus::RESOLVED,
            'resolved_at' => now(),
        ]);
        ActivityTimeline::logStatusChange($this, $old->value, IssueStatus::RESOLVED->value);
    }

    public function close(): void
    {
        $old = $this->status;
        $this->update([
            'status' => IssueStatus::CLOSED,
            'closed_at' => now(),
        ]);
        ActivityTimeline::logStatusChange($this, $old->value, IssueStatus::CLOSED->value);
    }

    public function reopen(): void
    {
        $old = $this->status;
        $this->update([
            'status' => IssueStatus::OPEN,
            'resolved_at' => null,
            'closed_at' => null,
        ]);
        ActivityTimeline::logStatusChange($this, $old->value, IssueStatus::OPEN->value);
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }
}
