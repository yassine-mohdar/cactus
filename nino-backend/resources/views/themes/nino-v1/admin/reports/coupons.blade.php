@extends('admin.layouts.app')
@section('title', 'Coupon Usage Report')
@section('content')
<div class="mb-6"><h1 class="text-2xl font-bold text-slate-900">Coupon Usage Report</h1><p class="text-sm text-slate-500 mt-1">Coupon performance and redemption analysis.</p></div>

<form method="GET" class="flex gap-3 mb-6 items-end">
    <div><label class="block text-xs font-semibold text-slate-500 mb-1">From</label><input type="date" name="from" value="{{ $from }}" class="rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20"></div>
    <div><label class="block text-xs font-semibold text-slate-500 mb-1">To</label><input type="date" name="to" value="{{ $to }}" class="rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20"></div>
    <button type="submit" class="btn-primary">Apply</button>
</form>

{{-- Summary --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white border border-slate-200 rounded-md p-4"><p class="text-xs font-medium text-slate-500 uppercase">Total Coupons</p><p class="text-2xl font-bold text-slate-900 mt-1">{{ $summary['total_coupons'] }}</p></div>
    <div class="bg-white border border-slate-200 rounded-md p-4"><p class="text-xs font-medium text-slate-500 uppercase">Active</p><p class="text-2xl font-bold text-green-600 mt-1">{{ $summary['active_coupons'] }}</p></div>
    <div class="bg-white border border-slate-200 rounded-md p-4"><p class="text-xs font-medium text-slate-500 uppercase">Total Redemptions</p><p class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($summary['total_redemptions']) }}</p></div>
    <div class="bg-white border border-slate-200 rounded-md p-4"><p class="text-xs font-medium text-slate-500 uppercase">Total Discount</p><p class="text-2xl font-bold text-purple-600 mt-1">{{ number_format($summary['total_discount_given'], 2) }} MAD</p></div>
</div>

{{-- Top Coupons --}}
<div class="bg-white border border-slate-200 rounded-md shadow-sm overflow-hidden">
    <table class="nino-table">
        <thead class="bg-white-dim border-b border-slate-200">
            <tr>
                <th class="px-4 py-3 font-semibold text-slate-900">Code</th>
                <th class="px-4 py-3 font-semibold text-slate-900 text-center">Type</th>
                <th class="px-4 py-3 font-semibold text-slate-900 text-right">Value</th>
                <th class="px-4 py-3 font-semibold text-slate-900 text-center">Status</th>
                <th class="px-4 py-3 font-semibold text-slate-900 text-right">Redemptions</th>
                <th class="px-4 py-3 font-semibold text-slate-900 text-right">Limit</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant">
            @forelse($topCoupons as $coupon)
            <tr class="hover:bg-white-dim/50">
                <td class="px-4 py-3 font-mono text-xs font-bold text-slate-900">{{ $coupon->code }}</td>
                <td class="px-4 py-3 text-center text-xs">{{ $coupon->type ?? '—' }}</td>
                <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ $coupon->value ? number_format($coupon->value, 2) : '—' }}</td>
                <td class="px-4 py-3 text-center"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $coupon->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">{{ $coupon->is_active ? 'Active' : 'Inactive' }}</span></td>
                <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ $coupon->usages_count ?? 0 }}</td>
                <td class="px-4 py-3 text-right text-slate-500">{{ $coupon->usage_limit ?? '∞' }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="p-8 text-center text-slate-500">
                        <div class="bg-slate-50 border border-dashed border-slate-300 rounded-md py-6 text-[11px] uppercase tracking-widest font-bold">
                            No coupons found.
                        </div>
                    </td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
