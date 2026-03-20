<?php

namespace App\Modules\Reports\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Promotions\Models\Coupon;
use Illuminate\Http\Request;

class CouponReportController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());

        // Top coupons by usage
        $topCoupons = Coupon::query()
            ->select('coupons.*')
            ->withCount(['usages' => function($q) use ($from, $to) {
                $q->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
            }])
            ->orderByDesc('usages_count')
            ->limit(20)
            ->get();

        // Summary
        $summary = [
            'total_coupons' => Coupon::count(),
            'active_coupons' => Coupon::where('is_active', true)->count(),
            'total_redemptions' => Coupon::withCount('usages')->get()->sum('usages_count'),
            'total_discount_given' => Coupon::withSum('usages', 'discount_amount')->get()->sum('usages_sum_discount_amount') ?? 0,
        ];

        return view('admin.reports.coupons', compact('topCoupons', 'summary', 'from', 'to'));
    }
}
