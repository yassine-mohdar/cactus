@extends('admin.layouts.app')
@section('title', 'Refund Requests')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Refund Requests</h1>
        <p class="page-subtitle">Review and manage refund requests with clearer order references and actions.</p>
    </div>
</div>
@if(session('success'))<div class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm border border-green-200">{{ session('success') }}</div>@endif

{{-- Stats --}}
<div class="stats-grid mb-6">
    <div class="stat-card">
        <p class="stat-label">Total Requests</p>
        <p class="stat-value">{{ $stats['total_requests'] }}</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Pending</p>
        <p class="stat-value text-yellow-600">{{ $stats['pending'] }}</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Approved</p>
        <p class="stat-value text-blue-600">{{ $stats['approved'] }}</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Total Refunded</p>
        <p class="stat-value text-red-600">{{ number_format($stats['total_refunded'], 2) }} <span class="text-xs font-normal">MAD</span></p>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="filter-toolbar mb-6">
    <div class="flex flex-wrap gap-3 items-end">
    <div class="filter-field"><label class="filter-label" for="refund-search">Search</label><input id="refund-search" type="text" name="search" value="{{ request('search') }}" placeholder="Reference..." class="input-field"></div>
    <div class="filter-field"><label class="filter-label" for="refund-status">Status</label>
        <select id="refund-status" name="status" class="input-field">
            <option value="">All</option>
            @foreach(\App\Modules\Finance\Enums\RefundStatus::cases() as $s)<option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>@endforeach
        </select>
    </div>
    <x-admin.button type="submit" variant="primary">Apply Filters</x-admin.button>
</div>
</form>

{{-- Table --}}
<div class="datatable-shell">
    <div class="datatable-scroll">
    <table class="nino-table">
        <thead>
            <tr>
                <th>Reference</th>
                <th>Order</th>
                <th class="text-center">Status</th>
                <th class="text-right">Amount</th>
                <th>Reason</th>
                <th>Date</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($refunds as $refund)
            <tr class="hover:bg-white-dim/50 transition-colors">
                <td class="px-4 py-3 font-mono text-xs text-slate-900 font-medium">{{ $refund->reference }}</td>
                <td class="px-4 py-3 text-xs text-slate-500">{{ $refund->order?->reference_number ?? '—' }}</td>
                <td class="px-4 py-3 text-center"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $refund->status->badgeColor() }}">{{ $refund->status->label() }}</span></td>
                <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ $refund->formattedAmount() }}<span class="text-xs text-slate-500 block">{{ $refund->refundPercentage() }}% of order</span></td>
                <td class="px-4 py-3 text-xs text-slate-500 max-w-[150px] truncate">{{ $refund->reason ?? '—' }}</td>
                <td class="px-4 py-3 text-xs text-slate-500">{{ $refund->created_at->format('M d, H:i') }}</td>
                <td class="px-4 py-3 text-right space-x-1">
                    @if($refund->status === \App\Modules\Finance\Enums\RefundStatus::REQUESTED)
                        <form action="{{ route('admin.finance.refunds.approve', $refund) }}" method="POST" class="inline">@csrf<button type="submit" class="text-green-600 hover:text-green-700 text-xs font-medium">Approve</button></form>
                        <form action="{{ route('admin.finance.refunds.reject', $refund) }}" method="POST" class="inline">@csrf<button type="submit" class="text-red-600 hover:text-red-700 text-xs font-medium">Reject</button></form>
                    @elseif($refund->status === \App\Modules\Finance\Enums\RefundStatus::APPROVED)
                        <form action="{{ route('admin.finance.refunds.complete', $refund) }}" method="POST" class="inline">@csrf<button type="submit" class="text-slate-900 hover:text-slate-900-dim text-xs font-medium">Complete</button></form>
                    @else
                        <span class="text-xs text-slate-500">—</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="datatable-empty">
                        <div class="datatable-empty-panel">
                            <p class="text-sm font-semibold text-slate-900">No refund requests.</p>
                            <p class="text-sm text-slate-500">Refund requests will appear here once customers start filing them.</p>
                        </div>
                    </td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
<div class="mt-4">{{ $refunds->links() }}</div>
@endsection
