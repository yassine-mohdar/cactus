<?php

namespace App\Modules\Finance\Models;

use App\Models\User;
use App\Modules\Finance\Enums\CodStatus;
use App\Modules\Finance\Enums\PaymentMethod as LegacyPaymentMethod;
use App\Modules\Finance\Enums\TransactionStatus;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\Payments\Services\PaymentMethodAvailabilityService;
use App\Modules\Payments\Enums\PaymentStatus as GatewayPaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'reference', 'order_id', 'customer_id',
        'type', 'status', 'payment_method', 'payment_method_id', 'payment_method_label', 'payment_method_behavior', 'gateway', 'gateway_transaction_id',
        'gateway_reference', 'payload', 'error_code', 'error_message',
        'amount', 'fee_amount', 'net_amount', 'currency',
        'cod_status', 'cod_collected_at', 'cod_deposited_at', 'cod_collected_by',
        'cod_collected_amount', 'cod_notes',
        'order_subtotal', 'order_discount', 'order_shipping', 'order_tax', 'order_total',
        'metadata', 'failure_reason', 'processed_by',
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'status' => TransactionStatus::class,
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

    public function setStatusAttribute(mixed $value): void
    {
        $this->attributes['status'] = match (true) {
            $value instanceof TransactionStatus => $value->value,
            $value instanceof GatewayPaymentStatus => $this->normalizeGatewayStatus($value)->value,
            is_string($value) => $this->normalizeStatusString($value),
            default => $value,
        };
    }

    public function setPaymentMethodAttribute(mixed $value): void
    {
        $normalized = match (true) {
            $value instanceof LegacyPaymentMethod => $value->value,
            default => is_string($value) ? $value : (string) $value,
        };

        $snapshot = app(PaymentMethodAvailabilityService::class)->snapshot($normalized);

        $this->attributes['payment_method'] = $snapshot['code'] ?? $normalized;

        if (! array_key_exists('payment_method_id', $this->attributes) || blank($this->attributes['payment_method_id'] ?? null)) {
            $this->attributes['payment_method_id'] = $snapshot['id'];
        }

        if (! array_key_exists('payment_method_label', $this->attributes) || blank($this->attributes['payment_method_label'] ?? null)) {
            $this->attributes['payment_method_label'] = $snapshot['label'];
        }

        if (! array_key_exists('payment_method_behavior', $this->attributes) || blank($this->attributes['payment_method_behavior'] ?? null)) {
            $this->attributes['payment_method_behavior'] = $snapshot['behavior'];
        }
    }

    // ── Relationships ──────────────────────────────────────
    public function order(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Orders\Models\Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function paymentMethodRecord(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function refundRequests()
    {
        return $this->hasMany(RefundRequest::class, 'transaction_id');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'transaction_id');
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
        return $query->where(function ($builder) {
            $builder
                ->where('payment_method_behavior', PaymentMethod::BEHAVIOR_COD)
                ->orWhereIn('payment_method', ['cod', 'cash_on_delivery']);
        });
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
        return $this->resolvedPaymentMethodBehavior() === PaymentMethod::BEHAVIOR_COD
            || in_array($this->resolvedPaymentMethodCode(), ['cod', 'cash_on_delivery'], true);
    }

    public function isOfflineManual(): bool
    {
        return $this->resolvedPaymentMethodBehavior() === PaymentMethod::BEHAVIOR_OFFLINE_MANUAL
            || $this->gateway === 'offline_transfer';
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

    public function resolvedPaymentMethodCode(): ?string
    {
        $code = strtolower(trim((string) ($this->paymentMethodRecord?->code ?? $this->payment_method ?? '')));

        return match ($code) {
            '', 'other' => null,
            'cash_on_delivery' => 'cod',
            'offline_transfer' => 'bank_transfer',
            default => $code,
        };
    }

    public function resolvedPaymentMethodLabel(): string
    {
        if ($this->paymentMethodRecord) {
            return $this->paymentMethodRecord->checkoutLabel();
        }

        if (filled($this->payment_method_label)) {
            return (string) $this->payment_method_label;
        }

        $legacy = LegacyPaymentMethod::tryFrom((string) $this->payment_method);

        if ($legacy) {
            return $legacy->label();
        }

        return app(PaymentMethodAvailabilityService::class)->legacyLabel($this->payment_method) ?? 'Payment Method';
    }

    public function resolvedPaymentMethodIcon(): string
    {
        $legacy = LegacyPaymentMethod::tryFrom((string) $this->payment_method);

        if ($legacy) {
            return $legacy->icon();
        }

        return app(PaymentMethodAvailabilityService::class)->legacyIcon($this->resolvedPaymentMethodCode());
    }

    public function resolvedPaymentMethodBehavior(): ?string
    {
        if (filled($this->payment_method_behavior)) {
            return (string) $this->payment_method_behavior;
        }

        if ($this->paymentMethodRecord) {
            return $this->paymentMethodRecord->behavior;
        }

        return $this->resolvedPaymentMethodCode() === 'cod'
            ? PaymentMethod::BEHAVIOR_COD
            : null;
    }

    public function gatewayStatusValue(): string
    {
        return match ($this->status) {
            TransactionStatus::PENDING => GatewayPaymentStatus::PENDING->value,
            TransactionStatus::COMPLETED => GatewayPaymentStatus::CAPTURED->value,
            TransactionStatus::FAILED => GatewayPaymentStatus::FAILED->value,
            TransactionStatus::CANCELLED => GatewayPaymentStatus::FAILED->value,
            TransactionStatus::REFUNDED => GatewayPaymentStatus::REFUNDED->value,
        };
    }

    public function getGatewayReferenceAttribute(): ?string
    {
        return $this->gateway_transaction_id
            ?? data_get($this->metadata, 'gateway_reference');
    }

    public function setGatewayReferenceAttribute(?string $value): void
    {
        $this->attributes['gateway_transaction_id'] = $value;
        $this->mergeMetadata(['gateway_reference' => $value]);
    }

    public function getPayloadAttribute(): ?array
    {
        return $this->metadata;
    }

    public function setPayloadAttribute(null|array $value): void
    {
        $this->attributes['metadata'] = $value === null ? null : json_encode($value, JSON_THROW_ON_ERROR);
    }

    public function getErrorCodeAttribute(): ?string
    {
        return data_get($this->metadata, 'error_code');
    }

    public function setErrorCodeAttribute(?string $value): void
    {
        $this->mergeMetadata(['error_code' => $value]);
    }

    public function getErrorMessageAttribute(): ?string
    {
        return $this->failure_reason;
    }

    public function setErrorMessageAttribute(?string $value): void
    {
        $this->attributes['failure_reason'] = $value;
    }

    protected function normalizeGatewayStatus(GatewayPaymentStatus $status): TransactionStatus
    {
        return match ($status) {
            GatewayPaymentStatus::PENDING,
            GatewayPaymentStatus::AUTHORIZED => TransactionStatus::PENDING,
            GatewayPaymentStatus::CAPTURED => TransactionStatus::COMPLETED,
            GatewayPaymentStatus::FAILED => TransactionStatus::FAILED,
            GatewayPaymentStatus::REFUNDED,
            GatewayPaymentStatus::PARTIALLY_REFUNDED => TransactionStatus::REFUNDED,
        };
    }

    protected function normalizeStatusString(string $value): string
    {
        return match ($value) {
            GatewayPaymentStatus::PENDING->value,
            GatewayPaymentStatus::AUTHORIZED->value => TransactionStatus::PENDING->value,
            GatewayPaymentStatus::CAPTURED->value => TransactionStatus::COMPLETED->value,
            GatewayPaymentStatus::FAILED->value => TransactionStatus::FAILED->value,
            GatewayPaymentStatus::REFUNDED->value,
            GatewayPaymentStatus::PARTIALLY_REFUNDED->value => TransactionStatus::REFUNDED->value,
            default => $value,
        };
    }

    protected function mergeMetadata(array $values): void
    {
        $metadata = $this->metadata ?? [];

        foreach ($values as $key => $value) {
            if ($value === null || $value === '') {
                unset($metadata[$key]);
                continue;
            }

            $metadata[$key] = $value;
        }

        $this->attributes['metadata'] = $metadata === []
            ? null
            : json_encode($metadata, JSON_THROW_ON_ERROR);
    }
}
