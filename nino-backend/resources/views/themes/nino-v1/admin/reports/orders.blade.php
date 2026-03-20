@extends('admin.layouts.app')
@section('title', 'Orders Report')
@section('content')
<div class="page-header">
    <div><h1 class="page-title">Orders Report</h1><p class="page-subtitle">Order volume and fulfillment analysis.</p></div>
    <a href="{{ route('admin.reports.export.orders', request()->all()) }}" class="btn-secondary">Export CSV</a>
</div>

<form method="GET" class="filter-toolbar mb-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="filter-field"><label class="filter-label" for="orders-report-from">From</label><input id="orders-report-from" type="date" name="from" value="{{ $from }}" class="input-field"></div>
        <div class="filter-field"><label class="filter-label" for="orders-report-to">To</label><input id="orders-report-to" type="date" name="to" value="{{ $to }}" class="input-field"></div>
        <x-admin.button type="submit" variant="primary">Apply</x-admin.button>
    </div>
</form>

{{-- Fulfillment Stats --}}
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-white border border-slate-200 rounded-md p-4"><p class="text-xs font-medium text-slate-500 uppercase">Total</p><p class="text-2xl font-bold text-slate-900 mt-1">{{ $fulfillment['total'] }}</p></div>
    <div class="bg-white border border-slate-200 rounded-md p-4"><p class="text-xs font-medium text-slate-500 uppercase">Pending</p><p class="text-2xl font-bold text-yellow-600 mt-1">{{ $fulfillment['pending'] }}</p></div>
    <div class="bg-white border border-slate-200 rounded-md p-4"><p class="text-xs font-medium text-slate-500 uppercase">Shipped</p><p class="text-2xl font-bold text-blue-600 mt-1">{{ $fulfillment['shipped'] }}</p></div>
    <div class="bg-white border border-slate-200 rounded-md p-4"><p class="text-xs font-medium text-slate-500 uppercase">Delivered</p><p class="text-2xl font-bold text-green-600 mt-1">{{ $fulfillment['delivered'] }}</p></div>
    <div class="bg-white border border-slate-200 rounded-md p-4"><p class="text-xs font-medium text-slate-500 uppercase">Cancelled</p><p class="text-2xl font-bold text-red-600 mt-1">{{ $fulfillment['cancelled'] }}</p></div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Status Breakdown --}}
    <div class="page-section p-6">
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Status Breakdown</h2>
        <table class="nino-table">
            <thead><tr><th>Status</th><th class="text-right">Orders</th><th class="text-right">Revenue</th></tr></thead>
            <tbody>
                @foreach($statusBreakdown as $row)
                <tr><td class="px-4 py-3"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $row->status ? ucwords(str_replace('_', ' ', $row->status)) : 'N/A' }}</span></td><td class="px-4 py-3 text-right text-slate-900">{{ $row->count }}</td><td class="px-4 py-3 text-right font-semibold text-slate-900">{{ number_format((float) ($row->total ?? 0), 2) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Daily Volume --}}
    <div class="page-section p-6">
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Daily Volume</h2>
        <div class="max-h-72 overflow-y-auto">
            <table class="nino-table">
                <thead class="sticky top-0"><tr><th>Date</th><th class="text-right">Orders</th><th class="text-right">Revenue</th></tr></thead>
                <tbody>
                    @foreach($dailyVolume as $row)
                    <tr><td class="px-4 py-3 text-slate-900">{{ $row->date }}</td><td class="px-4 py-3 text-right text-slate-900">{{ $row->count }}</td><td class="px-4 py-3 text-right font-semibold text-slate-900">{{ number_format((float) ($row->total ?? 0), 2) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-4 text-sm text-slate-500">Avg items per order: <span class="font-semibold text-slate-900">{{ number_format($avgItems, 1) }}</span></div>
@endsection
