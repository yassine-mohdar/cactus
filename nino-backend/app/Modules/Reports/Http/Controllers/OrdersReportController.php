<?php

namespace App\Modules\Reports\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdersReportController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());

        $range = [$from . ' 00:00:00', $to . ' 23:59:59'];

        // Status breakdown
        $statusBreakdown = Order::query()
            ->toBase()
            ->selectRaw('status, COUNT(*) as count, SUM(grand_total) as total')
            ->whereBetween('created_at', $range)
            ->groupBy('status')
            ->orderByDesc('count')
            ->get();

        // Daily order volume
        $dailyVolume = Order::query()
            ->toBase()
            ->selectRaw("DATE(created_at) as date, COUNT(*) as count, SUM(grand_total) as total")
            ->whereBetween('created_at', $range)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Fulfillment stats
        $fulfillment = [
            'total' => Order::whereBetween('created_at', $range)->count(),
            'shipped' => Order::whereBetween('created_at', $range)->where('status', 'shipped')->count(),
            'delivered' => Order::whereBetween('created_at', $range)->where('status', 'delivered')->count(),
            'cancelled' => Order::whereBetween('created_at', $range)->where('status', 'cancelled')->count(),
            'pending' => Order::whereBetween('created_at', $range)->where('status', 'pending')->count(),
        ];

        // Average items per order
        $avgItems = DB::table('order_line_items')
            ->join('orders', 'orders.id', '=', 'order_line_items.order_id')
            ->whereBetween('orders.created_at', $range)
            ->selectRaw('AVG(order_line_items.quantity) as avg_qty')
            ->value('avg_qty') ?? 0;

        return view('admin.reports.orders', compact('statusBreakdown', 'dailyVolume', 'fulfillment', 'avgItems', 'from', 'to'));
    }
}
