<?php

namespace App\Modules\Shipping\Models;

use App\Models\User;
use App\Modules\Orders\Models\Order;
use App\Modules\Shipping\Enums\ShipmentStatus;
use App\Modules\Shipping\Services\ShippingSettingsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    protected $fillable = [
        'order_id',
        'shipping_method_id',
        'status',
        'carrier_name',
        'carrier_service',
        'tracking_number',
        'tracking_url',
        'weight',
        'dimensions',
        'package_count',
        'packed_at',
        'dispatched_at',
        'delivered_at',
        'failed_at',
        'returned_at',
        'has_delivery_issue',
        'delivery_issue_notes',
        'failure_reason',
        'packed_by',
        'dispatched_by',
        'internal_notes',
    ];

    protected $casts = [
        'status' => ShipmentStatus::class,
        'weight' => 'decimal:2',
        'has_delivery_issue' => 'boolean',
        'packed_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    // ──────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ──────────────────────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function packedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'packed_by');
    }

    public function dispatchedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ShipmentStatusHistory::class)->orderByDesc('created_at');
    }

    // ──────────────────────────────────────────────────────────────
    // SCOPES
    // ──────────────────────────────────────────────────────────────

    public function scopeReadyToShip($query)
    {
        return $query->where('status', ShipmentStatus::READY_TO_SHIP);
    }

    public function scopeInProgress($query)
    {
        return $query->whereIn('status', [
            ShipmentStatus::PENDING,
            ShipmentStatus::READY_TO_SHIP,
            ShipmentStatus::PACKED,
            ShipmentStatus::DISPATCHED,
            ShipmentStatus::IN_TRANSIT,
        ]);
    }

    public function scopeWithIssues($query)
    {
        return $query->where('has_delivery_issue', true)
            ->orWhere('status', ShipmentStatus::FAILED_DELIVERY);
    }

    public function scopeReturned($query)
    {
        return $query->where('status', ShipmentStatus::RETURNED);
    }

    // ──────────────────────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────────────────────

    public function canTransitionTo(ShipmentStatus $newStatus): bool
    {
        return $this->status->canTransitionTo($newStatus);
    }

    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }

    /**
     * Get the full tracking URL for public display.
     */
    public function getTrackingLink(): ?string
    {
        if ($this->tracking_url) {
            return $this->tracking_url;
        }

        if (!$this->tracking_number || !$this->carrier_name) {
            return null;
        }

        // Common Moroccan and international carrier tracking URL patterns
        return match(strtolower($this->carrier_name)) {
            'amana' => "https://www.amana.ma/fr/suivi-colis?code={$this->tracking_number}",
            'dhl' => "https://www.dhl.com/ma-fr/home/tracking.html?tracking-id={$this->tracking_number}",
            'chronopost' => "https://www.chronopost.fr/tracking-no-cms/suivi-page?listeNumerosLT={$this->tracking_number}",
            'fedex' => "https://www.fedex.com/fedextrack/?trknbr={$this->tracking_number}",
            'ups' => "https://www.ups.com/track?tracknum={$this->tracking_number}",
            default => app(ShippingSettingsService::class)->buildTrackingUrl($this->tracking_number),
        };
    }
}
