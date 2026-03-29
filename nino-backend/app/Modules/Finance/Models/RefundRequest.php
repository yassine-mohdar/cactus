<?php

namespace App\Modules\Finance\Models;

use App\Modules\Finance\Enums\RefundStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RefundRequest extends Model
{
    protected $fillable = [
        'reference', 'order_id', 'customer_id', 'transaction_id',
        'status', 'amount', 'original_order_total', 'currency',
        'reason', 'notes', 'admin_notes', 'items',
        'approved_by', 'processed_by', 'approved_at', 'completed_at', 'rejected_at',
    ];

    protected $casts = [
        'status' => RefundStatus::class,
        'amount' => 'decimal:2',
        'original_order_total' => 'decimal:2',
        'items' => 'array',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($refund) {
            if (empty($refund->reference)) {
                $refund->reference = 'RFD-' . strtoupper(Str::random(10));
            }
        });
    }

    // ── Relationships ──────────────────────────────────────
    public function order(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Orders\Models\Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Customers\Models\Customer::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'transaction_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'processed_by');
    }

    // ── Scopes ─────────────────────────────────────────────
    public function scopePending($query)
    {
        return $query->where('status', RefundStatus::REQUESTED);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', RefundStatus::APPROVED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', RefundStatus::COMPLETED);
    }

    // ── Actions ────────────────────────────────────────────
    public function approve(int $approvedBy): void
    {
        $this->update([
            'status' => RefundStatus::APPROVED,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    public function markProcessing(): void
    {
        $this->update(['status' => RefundStatus::PROCESSING]);
    }

    public function complete(int $processedBy): void
    {
        $this->update([
            'status' => RefundStatus::COMPLETED,
            'processed_by' => $processedBy,
            'completed_at' => now(),
        ]);
    }

    public function reject(int $processedBy, ?string $reason = null): void
    {
        $this->update([
            'status' => RefundStatus::REJECTED,
            'processed_by' => $processedBy,
            'admin_notes' => $reason,
            'rejected_at' => now(),
        ]);
    }

    public function refundPercentage(): float
    {
        if ($this->original_order_total <= 0) return 0;
        return round(($this->amount / $this->original_order_total) * 100, 1);
    }

    public function formattedAmount(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }
}
