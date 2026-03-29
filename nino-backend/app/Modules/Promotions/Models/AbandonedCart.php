<?php

namespace App\Modules\Promotions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbandonedCart extends Model
{
    protected $fillable = [
        'customer_id', 'session_id', 'email',
        'cart_items', 'cart_total', 'items_count', 'currency',
        'status', 'notification_count', 'last_notified_at',
        'recovered_at', 'recovered_order_id', 'abandoned_at',
    ];

    protected $casts = [
        'cart_items' => 'array',
        'cart_total' => 'decimal:2',
        'items_count' => 'integer',
        'notification_count' => 'integer',
        'last_notified_at' => 'datetime',
        'recovered_at' => 'datetime',
        'abandoned_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────
    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'customer_id');
    }

    public function recoveredOrder(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Orders\Models\Order::class, 'recovered_order_id');
    }

    // ── Scopes ─────────────────────────────────────────────
    public function scopeAbandoned($query)
    {
        return $query->where('status', 'abandoned');
    }

    public function scopeRecoverable($query)
    {
        return $query->where('status', 'abandoned')
            ->where('notification_count', '<', 3)
            ->where('abandoned_at', '>=', now()->subDays(7));
    }

    public function scopeRecovered($query)
    {
        return $query->where('status', 'recovered');
    }

    // ── Helpers ─────────────────────────────────────────────
    public function isRecoverable(): bool
    {
        return $this->status === 'abandoned'
            && $this->notification_count < 3
            && $this->abandoned_at->greaterThanOrEqualTo(now()->subDays(7));
    }

    public function markNotified(): void
    {
        $this->update([
            'status' => 'notified',
            'notification_count' => $this->notification_count + 1,
            'last_notified_at' => now(),
        ]);
    }

    public function markRecovered(int $orderId): void
    {
        $this->update([
            'status' => 'recovered',
            'recovered_at' => now(),
            'recovered_order_id' => $orderId,
        ]);
    }
}
