<?php

namespace App\Modules\Reports\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\PaymentTransaction;
use App\Modules\Finance\Models\RefundRequest;
use Illuminate\Http\Request;

class FinanceSummaryReportController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());
        $range = [$from . ' 00:00:00', $to . ' 23:59:59'];

        // Revenue summary
        $revenue = [
            'gross' => PaymentTransaction::whereBetween('created_at', $range)->where('type', 'payment')->where('status', 'completed')->sum('amount'),
            'fees' => PaymentTransaction::whereBetween('created_at', $range)->where('type', 'payment')->where('status', 'completed')->sum('fee_amount'),
            'refunds' => RefundRequest::whereBetween('created_at', $range)->where('status', 'completed')->sum('amount'),
            'net' => 0,
        ];
        $revenue['net'] = $revenue['gross'] - $revenue['fees'] - $revenue['refunds'];

        // Payment method breakdown
        $methodBreakdown = PaymentTransaction::query()
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
            ->whereBetween('created_at', $range)
            ->where('type', 'payment')
            ->where('status', 'completed')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        // Daily revenue
        $dailyRevenue = PaymentTransaction::query()
            ->selectRaw('DATE(created_at) as date, SUM(amount) as revenue, SUM(fee_amount) as fees')
            ->whereBetween('created_at', $range)
            ->where('type', 'payment')
            ->where('status', 'completed')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Gateway performance
        $gatewayStats = PaymentTransaction::query()
            ->selectRaw("gateway, COUNT(*) as total, SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as success, SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) as failed")
            ->whereBetween('created_at', $range)
            ->where('type', 'payment')
            ->whereNotNull('gateway')
            ->groupBy('gateway')
            ->get();

        return view('admin.reports.finance', compact('revenue', 'methodBreakdown', 'dailyRevenue', 'gatewayStats', 'from', 'to'));
    }
}
