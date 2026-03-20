@extends('admin.layouts.app')
@section('title', 'Finance Summary Report')
@section('content')
<div class="page-header">
    <div><h1 class="page-title">Finance Summary</h1><p class="page-subtitle">Revenue, payment methods, and gateway performance.</p></div>
    <a href="{{ route('admin.reports.export.transactions', request()->all()) }}" class="btn-secondary">Export Transactions</a>
</div>

<form method="GET" class="filter-toolbar mb-6">
    <div class="flex flex-wrap gap-3 items-end">
    <div class="filter-field"><label class="filter-label" for="finance-from">From</label><input id="finance-from" type="date" name="from" value="{{ $from }}" class="input-field"></div>
    <div class="filter-field"><label class="filter-label" for="finance-to">To</label><input id="finance-to" type="date" name="to" value="{{ $to }}" class="input-field"></div>
    <x-admin.button type="submit" variant="primary">Apply</x-admin.button>
</div>
</form>

{{-- Revenue --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white border border-slate-200 rounded-md p-4"><p class="text-xs font-medium text-slate-500 uppercase">Gross Revenue</p><p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($revenue['gross'], 2) }}</p></div>
    <div class="bg-white border border-slate-200 rounded-md p-4"><p class="text-xs font-medium text-slate-500 uppercase">Fees</p><p class="text-2xl font-bold text-red-500 mt-1">-{{ number_format($revenue['fees'], 2) }}</p></div>
    <div class="bg-white border border-slate-200 rounded-md p-4"><p class="text-xs font-medium text-slate-500 uppercase">Refunds</p><p class="text-2xl font-bold text-orange-500 mt-1">-{{ number_format($revenue['refunds'], 2) }}</p></div>
    <div class="bg-white border border-slate-200 rounded-md p-4"><p class="text-xs font-medium text-slate-500 uppercase">Net Revenue</p><p class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($revenue['net'], 2) }}</p></div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    {{-- Payment Methods --}}
    <div class="page-section p-6">
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Payment Methods</h2>
        <table class="nino-table">
            <thead><tr><th>Method</th><th class="text-right">#</th><th class="text-right">Volume</th></tr></thead>
            <tbody>
                @foreach($methodBreakdown as $m)
                <tr><td class="px-4 py-3 font-medium text-slate-900 capitalize">{{ str_replace('_', ' ', $m->payment_method) }}</td><td class="px-4 py-3 text-right text-slate-900">{{ $m->count }}</td><td class="px-4 py-3 text-right font-semibold text-slate-900">{{ number_format($m->total, 2) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Gateway Performance --}}
    <div class="page-section p-6">
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Gateway Performance</h2>
        <table class="nino-table">
            <thead><tr><th>Gateway</th><th class="text-right">Total</th><th class="text-right">Success</th><th class="text-right">Rate</th></tr></thead>
            <tbody>
                @foreach($gatewayStats as $g)
                <tr><td class="px-4 py-3 font-medium text-slate-900 capitalize">{{ $g->gateway }}</td><td class="px-4 py-3 text-right text-slate-900">{{ $g->total }}</td><td class="px-4 py-3 text-right text-green-600">{{ $g->success }}</td><td class="px-4 py-3 text-right font-semibold {{ $g->total > 0 ? ($g->success/$g->total >= 0.95 ? 'text-green-600' : 'text-yellow-600') : '' }}">{{ $g->total > 0 ? number_format(($g->success/$g->total)*100, 1) : 0 }}%</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Daily Revenue --}}
<div class="page-section p-6">
    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Daily Revenue</h2>
    <div class="max-h-72 overflow-y-auto">
        <table class="nino-table">
            <thead class="sticky top-0"><tr><th>Date</th><th class="text-right">Revenue</th><th class="text-right">Fees</th><th class="text-right">Net</th></tr></thead>
            <tbody>
                @foreach($dailyRevenue as $d)
                <tr><td class="px-4 py-3 text-slate-900">{{ $d->date }}</td><td class="px-4 py-3 text-right font-semibold text-green-600">{{ number_format($d->revenue, 2) }}</td><td class="px-4 py-3 text-right text-red-500">-{{ number_format($d->fees, 2) }}</td><td class="px-4 py-3 text-right font-semibold text-slate-900">{{ number_format($d->revenue - $d->fees, 2) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
