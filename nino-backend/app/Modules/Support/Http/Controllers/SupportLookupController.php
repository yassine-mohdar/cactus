<?php

namespace App\Modules\Support\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Models\Order;
use App\Modules\Support\Models\ActivityTimeline;
use App\Modules\Support\Models\InternalNote;
use App\Modules\Finance\Models\PaymentTransaction;
use Illuminate\Http\Request;

class SupportLookupController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.any:support.viewAny,support.manage_tickets');
    }

    /**
     * Unified support search — searches orders, transactions, and customer data.
     */
    public function index(Request $request)
    {
        $results = [
            'orders' => collect(),
            'transactions' => collect(),
        ];
        $searched = false;

        if ($request->filled('q')) {
            $q = trim((string) $request->string('q'));
            $searched = true;

            // Order search — by reference number, customer email/phone, billing name
            $results['orders'] = Order::query()
                ->with([
                    'customer:id,name,first_name,last_name,email,phone',
                    'billingAddress:id,order_id,first_name,last_name,phone',
                    'shipments:id,order_id,tracking_number,external_reference,status',
                ])
                ->where(function ($query) use ($q) {
                    $query
                        ->where('reference_number', 'like', "%{$q}%")
                        ->orWhereHas('shipments', function ($shipmentQuery) use ($q) {
                            $shipmentQuery
                                ->where('tracking_number', 'like', "%{$q}%")
                                ->orWhere('external_reference', 'like', "%{$q}%");
                        })
                        ->orWhereHas('customer', function ($customerQuery) use ($q) {
                            $customerQuery
                                ->where('email', 'like', "%{$q}%")
                                ->orWhere('phone', 'like', "%{$q}%")
                                ->orWhere('first_name', 'like', "%{$q}%")
                                ->orWhere('last_name', 'like', "%{$q}%")
                                ->orWhere('name', 'like', "%{$q}%");
                        })
                        ->orWhereHas('billingAddress', function ($addressQuery) use ($q) {
                            $addressQuery
                                ->where('first_name', 'like', "%{$q}%")
                                ->orWhere('last_name', 'like', "%{$q}%")
                                ->orWhere('phone', 'like', "%{$q}%");
                        });
                })
                ->orderByDesc('created_at')
                ->limit(20)
                ->get();

            // Transaction search — by reference or gateway ref
            $results['transactions'] = PaymentTransaction::query()
                ->where('reference', 'like', "%{$q}%")
                ->orWhere('gateway_transaction_id', 'like', "%{$q}%")
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        }

        return view('admin.support.lookup.index', compact('results', 'searched'));
    }

    /**
     * Customer/Order timeline view — consolidated view for support agents.
     */
    public function orderTimeline(Order $order)
    {
        $order->load(['lineItems', 'addresses', 'customer']);

        // Notes
        $notes = InternalNote::where('notable_type', Order::class)
            ->where('notable_id', $order->id)
            ->with('author')
            ->orderByDesc('is_pinned')
            ->recent()
            ->get();

        // Activity timeline
        $activities = ActivityTimeline::where('subject_type', Order::class)
            ->where('subject_id', $order->id)
            ->with('performer')
            ->recent()
            ->get();

        // Transactions for this order
        $transactions = PaymentTransaction::where('order_id', $order->id)->get();

        $recentCustomerOrders = collect();
        $customerOrderSummary = [
            'total_orders' => 0,
            'recent_status_counts' => [],
            'last_order_at' => null,
        ];

        if ($order->customer_id) {
            $recentCustomerOrders = Order::query()
                ->where('customer_id', $order->customer_id)
                ->whereKeyNot($order->id)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get([
                    'id',
                    'reference_number',
                    'status',
                    'grand_total',
                    'currency',
                    'created_at',
                ]);

            $customerOrders = Order::query()
                ->where('customer_id', $order->customer_id)
                ->orderByDesc('created_at')
                ->get(['id', 'status', 'created_at']);

            $customerOrderSummary = [
                'total_orders' => $customerOrders->count(),
                'recent_status_counts' => $customerOrders
                    ->take(5)
                    ->groupBy(fn (Order $customerOrder) => $customerOrder->status?->value ?? 'unknown')
                    ->map(fn ($orders, string $status) => [
                        'status' => $status,
                        'label' => $orders->first()?->status?->label() ?? ucfirst(str_replace('_', ' ', $status)),
                        'count' => $orders->count(),
                    ])
                    ->values(),
                'last_order_at' => $customerOrders->first()?->created_at,
            ];
        }

        return view('admin.support.lookup.timeline', compact(
            'order',
            'notes',
            'activities',
            'transactions',
            'recentCustomerOrders',
            'customerOrderSummary',
        ));
    }
}
