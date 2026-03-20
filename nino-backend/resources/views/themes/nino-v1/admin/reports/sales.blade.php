@extends('admin.layouts.app')
@section('title', 'Sales Report')
@section('content')
<div class="page-header">
    <div><h1 class="page-title">Sales Report</h1><p class="page-subtitle">Revenue and sales performance analysis.</p></div>
    <a href="{{ route('admin.reports.export.orders', request()->all()) }}" class="btn-secondary">Export CSV</a>
</div>

{{-- Date Range --}}
<form method="GET" class="filter-toolbar mb-6">
    <div class="flex flex-wrap gap-3 items-end">
    <div class="filter-field"><label class="filter-label" for="sales-from">From</label><input id="sales-from" type="date" name="from" value="{{ $from }}" class="input-field"></div>
    <div class="filter-field"><label class="filter-label" for="sales-to">To</label><input id="sales-to" type="date" name="to" value="{{ $to }}" class="input-field"></div>
    <div class="filter-field"><label class="filter-label" for="sales-group-by">Group By</label>
        <select id="sales-group-by" name="group_by" class="input-field">
            <option value="day" {{ $groupBy === 'day' ? 'selected' : '' }}>Day</option>
            <option value="week" {{ $groupBy === 'week' ? 'selected' : '' }}>Week</option>
            <option value="month" {{ $groupBy === 'month' ? 'selected' : '' }}>Month</option>
        </select>
    </div>
    <x-admin.button type="submit" variant="primary">Apply</x-admin.button>
</div>
</form>

{{-- Summary Cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white border border-slate-200 rounded-md p-4"><p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Revenue</p><p class="text-2xl font-bold text-green-600 mt-1">{{ number_format((float) $summary['total_revenue'], 2) }} MAD</p></div>
    <div class="bg-white border border-slate-200 rounded-md p-4"><p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Orders</p><p class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($summary['total_orders']) }}</p></div>
    <div class="bg-white border border-slate-200 rounded-md p-4"><p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Avg Order Value</p><p class="text-2xl font-bold text-slate-900 mt-1">{{ number_format((float) $summary['avg_order_value'], 2) }} MAD</p></div>
    <div class="bg-white border border-slate-200 rounded-md p-4"><p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Completed Payments</p><p class="text-2xl font-bold text-purple-600 mt-1">{{ number_format((float) $summary['total_transactions'], 2) }} MAD</p></div>
</div>

{{-- Sales Over Time --}}
<div class="page-section p-6 mb-6">
    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Sales Over Time</h2>
    <div class="overflow-x-auto">
        <table class="nino-table">
            <thead><tr><th>Period</th><th class="text-right">Orders</th><th class="text-right">Revenue</th><th class="text-right">Avg Value</th></tr></thead>
            <tbody>
                @foreach($salesOverTime as $row)
                <tr class="hover:bg-white-dim/50"><td class="px-4 py-3 font-medium text-slate-900">{{ $row->period }}</td><td class="px-4 py-3 text-right text-slate-900">{{ number_format($row->order_count) }}</td><td class="px-4 py-3 text-right font-semibold text-green-600">{{ number_format((float) ($row->revenue ?? 0), 2) }}</td><td class="px-4 py-3 text-right text-slate-500">{{ number_format((float) ($row->avg_order_value ?? 0), 2) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Top Products --}}
<div class="page-section p-6">
    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Top 10 Products by Revenue</h2>
    <table class="nino-table">
        <thead><tr><th>#</th><th>Product</th><th class="text-right">Qty Sold</th><th class="text-right">Revenue</th></tr></thead>
        <tbody>
            @foreach($topProducts as $i => $p)
            <tr class="hover:bg-white-dim/50"><td class="px-4 py-3 text-slate-500">{{ $i + 1 }}</td><td class="px-4 py-3 font-medium text-slate-900">{{ $p->product_name }}</td><td class="px-4 py-3 text-right text-slate-900">{{ number_format($p->total_qty) }}</td><td class="px-4 py-3 text-right font-semibold text-green-600">{{ number_format((float) ($p->total_revenue ?? 0), 2) }}</td></tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
