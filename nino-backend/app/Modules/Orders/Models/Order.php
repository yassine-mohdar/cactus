<?php

namespace App\Modules\Orders\Models;

use App\Models\User;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\PaymentTransaction;
use App\Modules\Finance\Enums\PaymentMethod as LegacyPaymentMethod;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\Payments\Services\PaymentMethodAvailabilityService;
use App\Modules\Shipping\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference_number',
        'customer_id',
        'status',
        'currency',
        'subtotal',
        'tax_total',
        'shipping_total',
        'discount_total',
        'grand_total',
        'payment_method',
        'payment_method_id',
        'payment_method_label',
        'shipping_method',
        'shipping_method_id',
        'customer_notes',
        'admin_notes',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'shipping_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if (! $order->reference_number) {
                $order->reference_number = static::generateReferenceNumber();
            }
        });
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
    }

    public static function generateReferenceNumber(): string
    {
        do {
            $reference = 'ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (static::query()->where('reference_number', $reference)->exists());

        return $reference;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(OrderLineItem::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(OrderAddress::class);
    }

    public function billingAddress(): HasOne
    {
        return $this->hasOne(OrderAddress::class)->where('type', 'billing');
    }

    public function shippingAddress(): HasOne
    {
        return $this->hasOne(OrderAddress::class)->where('type', 'shipping');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->transactions();
    }

    public function paymentMethodRecord(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(\App\Modules\Shipping\Models\Shipment::class);
    }

    public function shippingMethodRecord(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
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

    public function resolvedPaymentMethodLabel(): ?string
    {
        if ($this->paymentMethodRecord) {
            return $this->paymentMethodRecord->checkoutLabel();
        }

        if (filled($this->payment_method_label)) {
            return $this->payment_method_label;
        }

        return match ($this->resolvedPaymentMethodCode()) {
            'cod' => 'Cash on Delivery',
            'bank_transfer' => 'Bank Transfer',
            'cmi' => 'CMI',
            'payzone' => 'Payzone',
            'stripe' => 'Stripe',
            default => $this->payment_method
                ? str($this->payment_method)->replace(['_', '-'], ' ')->title()->value()
                : null,
        };
    }

    public function paymentMethodBehavior(): ?string
    {
        if ($this->paymentMethodRecord) {
            return $this->paymentMethodRecord->behavior;
        }

        return match ($this->resolvedPaymentMethodCode()) {
            'cod' => PaymentMethod::BEHAVIOR_COD,
            'bank_transfer' => PaymentMethod::BEHAVIOR_OFFLINE_MANUAL,
            'cmi', 'payzone', 'stripe' => PaymentMethod::BEHAVIOR_GATEWAY,
            default => null,
        };
    }

    public function isCodPaymentMethod(): bool
    {
        return $this->paymentMethodBehavior() === PaymentMethod::BEHAVIOR_COD
            || $this->resolvedPaymentMethodCode() === 'cod';
    }

    /**
     * Get the primary (first) shipment for this order.
     */
    public function shipment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Modules\Shipping\Models\Shipment::class)->latestOfMany();
    }
}
