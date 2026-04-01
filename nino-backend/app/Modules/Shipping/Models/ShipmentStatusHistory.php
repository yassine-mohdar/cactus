<?php

namespace App\Modules\Shipping\Models;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentStatusHistory extends Model
{
    protected $table = 'shipment_status_history';

    protected $fillable = [
        'shipment_id',
        'status_from',
        'status_to',
        'notes',
        'changed_by',
        'changed_by_name',
        'ip_address',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function isStateChange(): bool
    {
        return $this->status_from === null || $this->status_from !== $this->status_to;
    }

    public function toLabel(): string
    {
        return Str::of((string) $this->status_to)->replace('_', ' ')->title()->value();
    }

    public function fromLabel(): ?string
    {
        if ($this->status_from === null) {
            return null;
        }

        return Str::of((string) $this->status_from)->replace('_', ' ')->title()->value();
    }

    public function eventTitle(): string
    {
        if ($this->status_from === null) {
            return 'Shipment created';
        }

        if ($this->isStateChange()) {
            return 'Status updated';
        }

        $notes = Str::lower((string) $this->notes);

        return match (true) {
            Str::startsWith($notes, 'tracking updated:') => 'Tracking updated',
            Str::startsWith($notes, 'delivery issue flagged:') => 'Delivery issue flagged',
            Str::startsWith($notes, 'delivery issue resolved') => 'Delivery issue resolved',
            default => 'Shipment activity',
        };
    }

    public function eventCopy(): string
    {
        if ($this->isStateChange()) {
            if ($this->notes) {
                return (string) $this->notes;
            }

            $from = $this->fromLabel();

            return trim(($from ? $from.' -> ' : '').$this->toLabel());
        }

        return (string) ($this->notes ?: $this->toLabel());
    }
}
