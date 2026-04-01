<?php

namespace App\Modules\Shipping\Services;

use App\Modules\Finance\Enums\RefundStatus;
use App\Modules\Finance\Models\RefundRequest;
use App\Modules\Finance\Services\InvoiceService;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Notifications\Services\NotificationTriggerService;
use App\Modules\Orders\Enums\OrderStatus;
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
    public function __construct(
        private readonly ShippingSettingsService $shippingSettings,
        private readonly InventoryService $inventoryService,
        private readonly InvoiceService $invoiceService,
        private readonly NotificationTriggerService $notificationTriggerService,
    ) {}

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
                'carrier_name' => $shippingMethod?->shippingCarrier?->name
                    ?? $shippingMethod?->carrier
                    ?? ($attributes['carrier_name'] ?? null)
                    ?? $this->shippingSettings->defaultCarrierName(),
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

            if ($newStatus === ShipmentStatus::DISPATCHED && $shipment->order) {
                $this->inventoryService->finalizeReservationsForOrder(
                    $shipment->order,
                    reason: 'shipment_dispatched',
                    userId: Auth::id(),
                );
            }

            $this->syncOrderStatusFromShipment($shipment, $newStatus);

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

    public function applyExternalStatus(
        Shipment $shipment,
        ShipmentStatus $newStatus,
        ?string $notes = null,
        array $extraAttributes = [],
    ): Shipment {
        if ($shipment->status === $newStatus) {
            return $shipment;
        }

        if ($shipment->canTransitionTo($newStatus)) {
            return $this->transitionStatus($shipment, $newStatus, $notes, $extraAttributes);
        }

        return DB::transaction(function () use ($shipment, $newStatus, $notes, $extraAttributes) {
            $oldStatus = $shipment->status;
            $update = array_merge(['status' => $newStatus], $extraAttributes);

            match ($newStatus) {
                ShipmentStatus::DELIVERED => $update['delivered_at'] = now(),
                ShipmentStatus::FAILED_DELIVERY => $update = array_merge($update, [
                    'failed_at' => now(),
                    'has_delivery_issue' => true,
                    'failure_reason' => $notes ?? $shipment->failure_reason,
                ]),
                default => null,
            };

            $shipment->update($update);
            $this->recordStatusChange($shipment, $oldStatus, $newStatus, $notes ?? 'External provider sync.');
            $this->syncOrderStatusFromShipment($shipment, $newStatus);

            return $shipment->fresh();
        });
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

    private function syncOrderStatusFromShipment(Shipment $shipment, ShipmentStatus $shipmentStatus): void
    {
        $order = $shipment->order;

        if (! $order) {
            return;
        }

        $currentOrderStatus = $order->status instanceof OrderStatus
            ? $order->status
            : OrderStatus::from((string) $order->status);

        if (in_array($currentOrderStatus, [OrderStatus::DELIVERED, OrderStatus::REFUNDED, OrderStatus::REFUND_PENDING, OrderStatus::CANCELLED], true)) {
            return;
        }

        $nextOrderStatus = match ($shipmentStatus) {
            ShipmentStatus::READY_TO_SHIP,
            ShipmentStatus::PACKED => OrderStatus::PREPARING,
            ShipmentStatus::DISPATCHED,
            ShipmentStatus::IN_TRANSIT => OrderStatus::SHIPPED,
            ShipmentStatus::DELIVERED => OrderStatus::DELIVERED,
            ShipmentStatus::FAILED_DELIVERY => OrderStatus::FAILED,
            ShipmentStatus::CANCELLED => OrderStatus::CANCELLED,
            default => null,
        };

        if ($shipmentStatus === ShipmentStatus::RETURNED) {
            $this->syncReturnedOrderState($order, $currentOrderStatus);

            return;
        }

        if (! $nextOrderStatus || $currentOrderStatus === $nextOrderStatus) {
            return;
        }

        $order->update([
            'status' => $nextOrderStatus,
        ]);

        $freshOrder = $order->fresh();
        $freshShipment = $shipment->fresh(['shippingMethod']);

        if ($shipmentStatus === ShipmentStatus::DISPATCHED) {
            $this->notificationTriggerService->orderShipped($freshOrder, $freshShipment);
        }

        if ($shipmentStatus === ShipmentStatus::DELIVERED) {
            if ($freshOrder->isCodPaymentMethod()) {
                $this->invoiceService->ensureInvoiceForOrder($freshOrder);
            }

            $this->notificationTriggerService->orderDelivered($freshOrder, $freshShipment);
        }

        if ($shipmentStatus === ShipmentStatus::CANCELLED) {
            $this->notificationTriggerService->orderCancelled($freshOrder);
        }
    }

    private function syncReturnedOrderState(Order $order, OrderStatus $currentOrderStatus): void
    {
        $requiresRefundFoundation = ! $order->isCodPaymentMethod();

        if ($requiresRefundFoundation) {
            RefundRequest::query()->firstOrCreate(
                [
                    'order_id' => $order->id,
                    'status' => RefundStatus::REQUESTED,
                ],
                [
                    'customer_id' => $order->customer_id,
                    'amount' => $order->grand_total,
                    'original_order_total' => $order->grand_total,
                    'currency' => $order->currency,
                    'reason' => 'Shipment returned to sender',
                ],
            );
        }

        $nextOrderStatus = $requiresRefundFoundation
            ? OrderStatus::REFUND_PENDING
            : OrderStatus::CANCELLED;

        if ($currentOrderStatus === $nextOrderStatus) {
            return;
        }

        $order->update([
            'status' => $nextOrderStatus,
        ]);
    }
}
