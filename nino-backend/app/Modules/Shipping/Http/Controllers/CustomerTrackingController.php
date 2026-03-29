<?php

namespace App\Modules\Shipping\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Models\Order;
use Illuminate\Support\Facades\Auth;

/**
 * Customer-facing tracking controller.
 * Provides tracking number and shipping timeline for a customer's own orders.
 */
class CustomerTrackingController extends Controller
{
    public function show(Order $order)
    {
        // Ensure the customer can only see their own orders
        if ($order->customer_id !== Auth::id()) {
            abort(403, 'You can only view tracking for your own orders.');
        }

        $shipment = $order->shipment?->load('statusHistory');

        if (!$shipment) {
            return response()->json([
                'message' => 'No shipment has been created for this order yet.',
                'tracking' => null,
            ]);
        }

        return response()->json([
            'tracking_number' => $shipment->tracking_number,
            'tracking_url' => $shipment->getTrackingLink(),
            'carrier' => $shipment->carrier_name,
            'status' => $shipment->status->label(),
            'status_raw' => $shipment->status->value,
            'estimated_delivery' => $shipment->shippingMethod?->estimated_days,
            'timestamps' => [
                'created' => $shipment->created_at?->toIso8601String(),
                'packed' => $shipment->packed_at?->toIso8601String(),
                'dispatched' => $shipment->dispatched_at?->toIso8601String(),
                'delivered' => $shipment->delivered_at?->toIso8601String(),
                'failed' => $shipment->failed_at?->toIso8601String(),
            ],
            'timeline' => $shipment->statusHistory->map(fn($h) => [
                'from' => $h->status_from,
                'to' => $h->status_to,
                'notes' => $h->notes,
                'at' => $h->created_at->toIso8601String(),
            ])->values(),
        ]);
    }
}
