<?php

namespace App\Modules\Shipping\Enums;

/**
 * Represents the lifecycle of a shipment from warehouse to customer doorstep.
 *
 * Flow: PENDING → READY_TO_SHIP → PACKED → DISPATCHED → IN_TRANSIT → DELIVERED
 *       ↘ FAILED_DELIVERY → RETURNED
 *       ↘ CANCELLED (at any point before dispatch)
 */
enum ShipmentStatus: string
{
    case PENDING = 'pending';                  // Shipment created, awaiting preparation
    case READY_TO_SHIP = 'ready_to_ship';      // Stock confirmed, labels ready
    case PACKED = 'packed';                    // Physically packed and sealed
    case DISPATCHED = 'dispatched';            // Handed to carrier
    case IN_TRANSIT = 'in_transit';            // Carrier has it, en route
    case DELIVERED = 'delivered';              // Customer received
    case FAILED_DELIVERY = 'failed_delivery';  // Carrier attempted but failed
    case RETURNED = 'returned';                // Parcel returned to warehouse
    case CANCELLED = 'cancelled';              // Shipment cancelled before dispatch

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::READY_TO_SHIP => 'Ready to Ship',
            self::PACKED => 'Packed',
            self::DISPATCHED => 'Dispatched',
            self::IN_TRANSIT => 'In Transit',
            self::DELIVERED => 'Delivered',
            self::FAILED_DELIVERY => 'Failed Delivery',
            self::RETURNED => 'Returned',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * CSS color class for status badges in admin UI.
     */
    public function badgeColor(): string
    {
        return match($this) {
            self::PENDING => 'bg-yellow-100 text-yellow-800',
            self::READY_TO_SHIP => 'bg-blue-100 text-blue-800',
            self::PACKED => 'bg-indigo-100 text-indigo-800',
            self::DISPATCHED => 'bg-cyan-100 text-cyan-800',
            self::IN_TRANSIT => 'bg-purple-100 text-purple-800',
            self::DELIVERED => 'bg-green-100 text-green-800',
            self::FAILED_DELIVERY => 'bg-red-100 text-red-800',
            self::RETURNED => 'bg-orange-100 text-orange-800',
            self::CANCELLED => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Valid transitions from this status.
     */
    public function allowedTransitions(): array
    {
        return match($this) {
            self::PENDING => [self::READY_TO_SHIP, self::CANCELLED],
            self::READY_TO_SHIP => [self::PACKED, self::CANCELLED],
            self::PACKED => [self::DISPATCHED, self::CANCELLED],
            self::DISPATCHED => [self::IN_TRANSIT, self::DELIVERED, self::FAILED_DELIVERY],
            self::IN_TRANSIT => [self::DELIVERED, self::FAILED_DELIVERY],
            self::DELIVERED => [],
            self::FAILED_DELIVERY => [self::RETURNED, self::DISPATCHED], // Re-dispatch or return
            self::RETURNED => [],
            self::CANCELLED => [],
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return in_array($newStatus, $this->allowedTransitions());
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::DELIVERED, self::RETURNED, self::CANCELLED]);
    }
}
