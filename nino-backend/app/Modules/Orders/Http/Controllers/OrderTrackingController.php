<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Models\Order;
use Illuminate\Http\Request;

class OrderTrackingController extends Controller
{
    /**
     * P7-THANKYOU-* & P7-PAYERR-*
     * Fetch a safe summary of an order to render on the receipt/status page natively 
     * without requiring an active user session (so guests can see it).
     */
    public function show(string $referenceNumber)
    {
        $order = Order::with(['lineItems', 'addresses'])
            ->where('reference_number', $referenceNumber)
            ->firstOrFail();

        // Deliberately strip out 'admin_notes' and internal ID structures
        $shippingAddress = $order->addresses->where('type', 'shipping')->first();
        $billingAddress = $order->addresses->where('type', 'billing')->first();

        return response()->json([
            'order' => [
                'reference_number' => $order->reference_number,
                'status' => $order->status->value, // PENDING, PAID, FAILED, etc.
                'status_label' => $order->status->label(),
                'currency' => $order->currency,
                'totals' => [
                    'subtotal' => $order->subtotal,
                    'tax_total' => $order->tax_total,
                    'shipping_total' => $order->shipping_total,
                    'discount_total' => $order->discount_total,
                    'grand_total' => $order->grand_total,
                ],
                'payment_method' => $order->payment_method,
                'shipping_method' => $order->shipping_method,
                'customer_notes' => $order->customer_notes,
                'created_at' => $order->created_at->toIso8601String(),
                'line_items' => $order->lineItems->map(fn($item) => [
                    'name' => $item->product_name,
                    'variant_name' => $item->variant_name,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'line_total' => $item->line_total,
                ]),
                'shipping_address' => $shippingAddress ? [
                    'first_name' => $shippingAddress->first_name,
                    'last_name' => $shippingAddress->last_name,
                    'city' => $shippingAddress->city,
                ] : null,
                // Don't leak full addresses to unauthorized clients if not strictly needed
            ]
        ]);
    }
}
