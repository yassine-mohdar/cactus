<?php

namespace App\Modules\Finance\Models;

use App\Modules\Finance\Enums\CodStatus;
use App\Modules\Finance\Enums\PaymentMethod;
use App\Modules\Finance\Enums\TransactionStatus;
use App\Modules\Finance\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'reference', 'order_id', 'customer_id',
        'type', 'status', 'payment_method', 'gateway', 'gateway_transaction_id',
        'amount', 'fee_amount', 'net_amount', 'currency',
        'cod_status', 'cod_collected_at', 'cod_deposited_at', 'cod_collected_by',
        'cod_collected_amount', 'cod_notes',
        'order_subtotal', 'order_discount', 'order_shipping', 'order_tax', 'order_total',
        'metadata', 'failure_reason', 'processed_by',
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'status' => TransactionStatus::class,
        'payment_method' => PaymentMethod::class,
        'cod_status' => CodStatus::class,
        'amount' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'cod_collected_amount' => 'decimal:2',
        'order_subtotal' => 'decimal:2',
        'order_discount' => 'decimal:2',
        'order_shipping' => 'decimal:2',
        'order_tax' => 'decimal:2',
        'order_total' => 'decimal:2',
        'metadata' => 'array',
        'cod_collected_at' => 'datetime',
        'cod_deposited_at' => 'datetime',
    ];

    // ── Boot ───────────────────────────────────────────────
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($txn) {
            if (empty($txn->reference)) {
                $txn->reference = 'TXN-' . strtoupper(Str::random(12));
            }
            if (is_null($txn->net_amount)) {
                $txn->net_amount = $txn->amount - ($txn->fee_amount ?? 0);
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

    public function processor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'processed_by');
    }

    public function refundRequests()
    {
        return $this->hasMany(RefundRequest::class, 'transaction_id');
    }

    // ── Scopes ─────────────────────────────────────────────
    public function scopeCompleted($query)
    {
        return $query->where('status', TransactionStatus::COMPLETED);
    }

    public function scopePayments($query)
    {
        return $query->where('type', TransactionType::PAYMENT);
    }

    public function scopeRefunds($query)
    {
        return $query->whereIn('type', [TransactionType::REFUND, TransactionType::PARTIAL_REFUND]);
    }

    public function scopeCod($query)
    {
        return $query->where('payment_method', PaymentMethod::CASH_ON_DELIVERY);
    }

    public function scopeCodPending($query)
    {
        return $query->cod()->where('cod_status', CodStatus::PENDING);
    }

    public function scopeCodUnreconciled($query)
    {
        return $query->cod()->whereIn('cod_status', [CodStatus::PENDING, CodStatus::COLLECTED, CodStatus::DEPOSITED]);
    }

    // ── Helpers ─────────────────────────────────────────────
    public function isCod(): bool
    {
        return $this->payment_method === PaymentMethod::CASH_ON_DELIVERY;
    }

    public function markCodCollected(string $collectedBy, ?float $collectedAmount = null): void
    {
        $this->update([
            'cod_status' => CodStatus::COLLECTED,
            'cod_collected_at' => now(),
            'cod_collected_by' => $collectedBy,
            'cod_collected_amount' => $collectedAmount ?? $this->amount,
        ]);
    }

    public function markCodDeposited(): void
    {
        $this->update([
            'cod_status' => CodStatus::DEPOSITED,
            'cod_deposited_at' => now(),
        ]);
    }

    public function markCodReconciled(): void
    {
        $this->update(['cod_status' => CodStatus::RECONCILED]);
    }

    public function hasCodDiscrepancy(): bool
    {
        return $this->isCod()
            && $this->cod_collected_amount !== null
            && abs($this->cod_collected_amount - $this->amount) > 0.01;
    }

    public function codDiscrepancyAmount(): float
    {
        return round(($this->cod_collected_amount ?? $this->amount) - $this->amount, 2);
    }

    public function formattedAmount(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }
}
