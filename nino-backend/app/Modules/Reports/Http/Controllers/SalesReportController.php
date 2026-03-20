<?php

namespace App\Modules\Reports\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Models\Order;
use App\Modules\Finance\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReportController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());
        $groupBy = $request->input('group_by', 'day'); // day|week|month

        $dateFormat = match($groupBy) {
            'week' => '%x-W%v',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        // Sales over time
        $salesOverTime = Order::query()
            ->toBase()
            ->selectRaw("DATE_FORMAT(created_at, '{$dateFormat}') as period")
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('SUM(grand_total) as revenue')
            ->selectRaw('AVG(grand_total) as avg_order_value')
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        // Summary totals
        $summary = [
            'total_revenue' => Order::whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])->sum('grand_total'),
            'total_orders' => Order::whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])->count(),
            'avg_order_value' => Order::whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])->avg('grand_total') ?? 0,
            'total_transactions' => PaymentTransaction::whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                ->where('status', 'completed')->sum('amount'),
        ];

        // Top products
        $topProducts = DB::table('order_line_items')
            ->join('orders', 'orders.id', '=', 'order_line_items.order_id')
            ->selectRaw('order_line_items.product_name, SUM(order_line_items.quantity) as total_qty, SUM(order_line_items.line_total) as total_revenue')
            ->whereBetween('orders.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->groupBy('order_line_items.product_name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        return view('admin.reports.sales', compact('salesOverTime', 'summary', 'topProducts', 'from', 'to', 'groupBy'));
    }
}
