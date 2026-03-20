<?php

namespace App\Modules\Shipping\Services;

use App\Modules\Orders\Models\Order;
use App\Modules\Shipping\Enums\ShipmentStatus;
use App\Modules\Shipping\Models\Shipment;
use App\Modules\Shipping\Models\ShipmentStatusHistory;
use App\Modules\Shipping\Models\ShippingMethod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Centralized Shipment Service
 *
 * Manages the full lifecycle of shipments: creation, status transitions,
 * tracking updates, and issue flagging. All mutations are wrapped in
 * transactions with audit trail entries.
 */
class ShipmentService
{
    /**
     * Create a shipment for an order.
     */
    public function createShipment(Order $order, ?int $shippingMethodId = null, array $attributes = []): Shipment
    {
        return DB::transaction(function () use ($order, $shippingMethodId, $attributes) {
            $shippingMethod = $shippingMethodId
                ? ShippingMethod::find($shippingMethodId)
                : null;

            $shipment = Shipment::create(array_merge([
                'order_id' => $order->id,
                'shipping_method_id' => $shippingMethodId,
                'status' => ShipmentStatus::PENDING,
                'carrier_name' => $shippingMethod?->carrier ?? ($attributes['carrier_name'] ?? null),
                'carrier_service' => $shippingMethod?->name ?? ($attributes['carrier_service'] ?? null),
            ], $attributes));

            $this->recordStatusChange($shipment, null, ShipmentStatus::PENDING, 'Shipment created.');

            return $shipment;
        });
    }

    /**
     * Transition a shipment to a new status with full audit trail.
     *
     * @throws \InvalidArgumentException if the transition is not valid
     */
    public function transitionStatus(
        Shipment $shipment,
        ShipmentStatus $newStatus,
        ?string $notes = null,
        array $extraAttributes = [],
    ): Shipment {
        if (!$shipment->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition shipment #{$shipment->id} from '{$shipment->status->label()}' to '{$newStatus->label()}'."
            );
        }

        return DB::transaction(function () use ($shipment, $newStatus, $notes, $extraAttributes) {
            $oldStatus = $shipment->status;

            // Build update payload with status-specific timestamps
            $update = array_merge(['status' => $newStatus], $extraAttributes);

            match ($newStatus) {
                ShipmentStatus::PACKED => $update['packed_at'] = now(),
                ShipmentStatus::DISPATCHED => $update = array_merge($update, [
                    'dispatched_at' => now(),
                    'dispatched_by' => Auth::id(),
                ]),
                ShipmentStatus::DELIVERED => $update['delivered_at'] = now(),
                ShipmentStatus::FAILED_DELIVERY => $update = array_merge($update, [
                    'failed_at' => now(),
                    'has_delivery_issue' => true,
                    'failure_reason' => $notes ?? $shipment->failure_reason,
                ]),
                ShipmentStatus::RETURNED => $update['returned_at'] = now(),
                default => null,
            };

            // Mark packed_by if transitioning to PACKED
            if ($newStatus === ShipmentStatus::PACKED && !$shipment->packed_by) {
                $update['packed_by'] = Auth::id();
            }

            $shipment->update($update);
            $this->recordStatusChange($shipment, $oldStatus, $newStatus, $notes);

            return $shipment->fresh();
        });
    }

    /**
     * Update tracking information on a shipment.
     */
    public function updateTracking(
        Shipment $shipment,
        string $trackingNumber,
        ?string $trackingUrl = null,
        ?string $carrierName = null,
    ): Shipment {
        $attributes = ['tracking_number' => $trackingNumber];

        if ($trackingUrl) {
            $attributes['tracking_url'] = $trackingUrl;
        }
        if ($carrierName) {
            $attributes['carrier_name'] = $carrierName;
        }

        $shipment->update($attributes);

        $this->recordStatusChange(
            $shipment,
            $shipment->status,
            $shipment->status,
            "Tracking updated: {$trackingNumber}"
        );

        return $shipment->fresh();
    }

    /**
     * Flag a delivery issue on a shipment.
     */
    public function flagDeliveryIssue(Shipment $shipment, string $issueNotes, ?string $failureReason = null): Shipment
    {
        $shipment->update([
            'has_delivery_issue' => true,
            'delivery_issue_notes' => $issueNotes,
            'failure_reason' => $failureReason ?? $shipment->failure_reason,
        ]);

        $this->recordStatusChange(
            $shipment,
            $shipment->status,
            $shipment->status,
            "Delivery issue flagged: {$issueNotes}"
        );

        return $shipment->fresh();
    }

    /**
     * Resolve a delivery issue (clear the flag).
     */
    public function resolveDeliveryIssue(Shipment $shipment, ?string $notes = null): Shipment
    {
        $shipment->update([
            'has_delivery_issue' => false,
            'delivery_issue_notes' => null,
        ]);

        $this->recordStatusChange(
            $shipment,
            $shipment->status,
            $shipment->status,
            'Delivery issue resolved.' . ($notes ? " Note: {$notes}" : '')
        );

        return $shipment->fresh();
    }

    /**
     * Create an immutable status history record.
     */
    private function recordStatusChange(
        Shipment $shipment,
        ?ShipmentStatus $from,
        ShipmentStatus $to,
        ?string $notes = null,
    ): ShipmentStatusHistory {
        $user = Auth::user();

        return ShipmentStatusHistory::create([
            'shipment_id' => $shipment->id,
            'status_from' => $from?->value,
            'status_to' => $to->value,
            'notes' => $notes,
            'changed_by' => $user?->id,
            'changed_by_name' => $user ? ($user->first_name . ' ' . $user->last_name) : 'System',
            'ip_address' => request()?->ip(),
        ]);
    }
}
