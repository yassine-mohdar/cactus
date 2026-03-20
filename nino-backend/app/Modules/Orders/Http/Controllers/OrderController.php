<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('customer')->orderByDesc('created_at');
        $summaryBaseQuery = Order::query();

        // Simple Search/Filter Implementation (P7-ADMIN-02 to 06)
        if ($search = $request->input('search')) {
            $query->where('reference_number', 'LIKE', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $orders = $query->paginate(25)->withQueryString();

        $summary = [
            'total_orders' => (clone $summaryBaseQuery)->count(),
            'awaiting_payment' => (clone $summaryBaseQuery)->where('status', OrderStatus::AWAITING_PAYMENT)->count(),
            'preparing' => (clone $summaryBaseQuery)->where('status', OrderStatus::PREPARING)->count(),
            'gross_30_days' => (float) ((clone $summaryBaseQuery)
                ->where('created_at', '>=', now()->subDays(30))
                ->sum('grand_total') ?? 0),
        ];

        $statuses = OrderStatus::cases();

        return view('admin.orders.index', compact('orders', 'statuses', 'summary'));
    }

    public function show(Order $order)
    {
        $order->load(['customer', 'lineItems', 'addresses']);

        $shippingAddress = $order->addresses->where('type', 'shipping')->first();
        $billingAddress = $order->addresses->where('type', 'billing')->first();

        return view('admin.orders.show', compact('order', 'shippingAddress', 'billingAddress'));
    }
}
