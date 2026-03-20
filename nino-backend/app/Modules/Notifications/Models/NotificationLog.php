<?php

namespace App\Modules\Notifications\Models;

use App\Modules\Notifications\Enums\NotificationChannel;
use App\Modules\Notifications\Enums\NotificationEvent;
use App\Modules\Notifications\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected $fillable = [
        'template_id', 'event', 'channel', 'status',
        'recipient', 'customer_id',
        'subject', 'body', 'order_reference', 'variables',
        'attempts', 'max_attempts', 'error_message', 'external_id',
        'sent_at', 'next_retry_at',
    ];

    protected $casts = [
        'event' => NotificationEvent::class,
        'channel' => NotificationChannel::class,
        'status' => NotificationStatus::class,
        'variables' => 'array',
        'attempts' => 'integer',
        'max_attempts' => 'integer',
        'sent_at' => 'datetime',
        'next_retry_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────
    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'customer_id');
    }

    // ── Scopes ─────────────────────────────────────────────
    public function scopeSent($query)
    {
        return $query->where('status', NotificationStatus::SENT);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', NotificationStatus::FAILED);
    }

    public function scopeRetryable($query)
    {
        return $query->where('status', NotificationStatus::FAILED)
            ->whereColumn('attempts', '<', 'max_attempts')
            ->where(function ($q) {
                $q->whereNull('next_retry_at')
                    ->orWhere('next_retry_at', '<=', now());
            });
    }

    public function scopeForChannel($query, NotificationChannel $channel)
    {
        return $query->where('channel', $channel);
    }

    // ── Helpers ─────────────────────────────────────────────
    public function canRetry(): bool
    {
        return $this->status === NotificationStatus::FAILED
            && $this->attempts < $this->max_attempts;
    }

    public function markSent(?string $externalId = null): void
    {
        $this->update([
            'status' => NotificationStatus::SENT,
            'sent_at' => now(),
            'external_id' => $externalId ?? $this->external_id,
            'error_message' => null,
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => $this->canRetry() ? NotificationStatus::RETRYING : NotificationStatus::FAILED,
            'error_message' => $error,
            'next_retry_at' => $this->canRetry() ? now()->addMinutes(pow(2, $this->attempts)) : null,
        ]);
    }

    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }
}
