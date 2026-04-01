<?php

namespace App\Modules\Payments\Models;

use App\Modules\Finance\Models\PaymentTransaction;
use App\Modules\Orders\Models\Order;
use App\Modules\Shipping\Models\ShippingCarrier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    public const CHANNEL_ONLINE = 'online';
    public const CHANNEL_OFFLINE = 'offline';

    public const BEHAVIOR_GATEWAY = 'gateway';
    public const BEHAVIOR_OFFLINE_MANUAL = 'offline_manual';
    public const BEHAVIOR_COD = 'cod';

    protected $fillable = [
        'code',
        'name',
        'channel',
        'behavior',
        'gateway_setting_id',
        'is_enabled',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    public function gatewaySetting(): BelongsTo
    {
        return $this->belongsTo(GatewaySetting::class);
    }

    public function shippingCarriers(): BelongsToMany
    {
        return $this->belongsToMany(ShippingCarrier::class, 'payment_method_shipping_carrier')
            ->withTimestamps();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function isOnline(): bool
    {
        return $this->channel === self::CHANNEL_ONLINE;
    }

    public function isOffline(): bool
    {
        return $this->channel === self::CHANNEL_OFFLINE;
    }

    public function isGatewayBehavior(): bool
    {
        return $this->behavior === self::BEHAVIOR_GATEWAY;
    }

    public function isOfflineManual(): bool
    {
        return $this->behavior === self::BEHAVIOR_OFFLINE_MANUAL;
    }

    public function isCod(): bool
    {
        return $this->behavior === self::BEHAVIOR_COD;
    }

    public function requiresCarrierSelection(): bool
    {
        return $this->shippingCarriers()->exists();
    }

    public function hasLinkedCarriers(): bool
    {
        return $this->relationLoaded('shippingCarriers')
            ? $this->shippingCarriers->isNotEmpty()
            : $this->shippingCarriers()->exists();
    }

    public function checkoutLabel(): string
    {
        return trim((string) data_get($this->metadata ?? [], 'method_label', $this->name));
    }

    public function checkoutTitle(): string
    {
        return trim((string) data_get($this->metadata ?? [], 'checkout_title', $this->checkoutLabel()));
    }

    public function checkoutDescription(): ?string
    {
        $value = trim((string) data_get($this->metadata ?? [], 'checkout_description', ''));

        return $value !== '' ? $value : null;
    }

    public function instructions(): ?string
    {
        $value = trim((string) data_get($this->metadata ?? [], 'instructions', ''));

        return $value !== '' ? $value : null;
    }

    public function adminInstructions(): ?string
    {
        $value = trim((string) data_get($this->metadata ?? [], 'admin_instructions', ''));

        return $value !== '' ? $value : null;
    }

    public function paymentWindowHours(): ?int
    {
        $value = data_get($this->metadata ?? [], 'payment_window_hours');

        if ($value === null || $value === '') {
            return null;
        }

        return max(1, (int) $value);
    }

    public function referencePrefix(): ?string
    {
        $value = strtoupper(trim((string) data_get($this->metadata ?? [], 'reference_prefix', '')));

        return $value !== '' ? $value : null;
    }

    public function requiresReceipt(): bool
    {
        return filter_var(data_get($this->metadata ?? [], 'require_receipt', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function providerGatewayId(): ?string
    {
        if ($this->isGatewayBehavior()) {
            return $this->gatewaySetting?->gateway_id
                ?? trim((string) data_get($this->metadata ?? [], 'provider_gateway_id', ''));
        }

        return $this->isOfflineManual() ? 'offline_transfer' : null;
    }

    public function optionMeta(): ?string
    {
        if ($this->isCod()) {
            return 'Carrier-collected payment';
        }

        if ($this->isOfflineManual()) {
            return $this->checkoutDescription()
                ?? 'Pending manual verification';
        }

        return $this->gatewaySetting?->name
            ?? $this->checkoutDescription();
    }
}
