@extends('admin.layouts.app')
@section('title', 'Refund Requests')
@section('content')
<x-nino.page-header
    title="Refund Requests"
    subtitle="Review and manage refund requests with clearer order references, statuses, and operational actions." />
@if(session('success'))
    <x-nino.inline-alert tone="success" title="Refund workflow updated" class="mb-4">
        {{ session('success') }}
    </x-nino.inline-alert>
@endif

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
    <x-nino.button type="submit" variant="primary">Apply Filters</x-nino.button>
    @if(request()->filled('search') || request()->filled('status'))
        <x-nino.button href="{{ route('admin.finance.refunds.index') }}" variant="outline">Clear</x-nino.button>
    @endif
</div>
</form>

{{-- Table --}}
<div class="datatable-shell">
    <div class="datatable-header">
        <div>
            <h2 class="datatable-title">Refund Queue</h2>
            <p class="datatable-subtitle">Review request status, financial impact, and the next action required from finance.</p>
        </div>
        <span class="datatable-meta">{{ number_format($refunds->total()) }} requests</span>
    </div>
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
            @php
                $statusTone = match ($refund->status->value) {
                    'completed' => 'success',
                    'approved', 'processing' => 'info',
                    'rejected' => 'danger',
                    default => 'warning',
                };
            @endphp
            <tr>
                <td class="px-4 py-3 font-mono text-xs font-medium text-[#1E2B27]">{{ $refund->reference }}</td>
                <td class="px-4 py-3 text-xs text-[#61706B]">{{ $refund->order?->reference_number ?? '—' }}</td>
                <td class="px-4 py-3 text-center"><x-nino.status-badge :tone="$statusTone" size="sm">{{ $refund->status->label() }}</x-nino.status-badge></td>
                <td class="px-4 py-3 text-right font-semibold text-[#1E2B27]">{{ $refund->formattedAmount() }}<span class="block text-xs text-[#61706B]">{{ $refund->refundPercentage() }}% of order</span></td>
                <td class="px-4 py-3 max-w-[150px] truncate text-xs text-[#61706B]">{{ $refund->reason ?? '—' }}</td>
                <td class="px-4 py-3 text-xs text-[#61706B]">{{ $refund->created_at->format('M d, H:i') }}</td>
                <td class="px-4 py-3 text-right">
                    <div class="table-actions">
                    @if($refund->status === \App\Modules\Finance\Enums\RefundStatus::REQUESTED)
                        <form action="{{ route('admin.finance.refunds.approve', $refund) }}" method="POST" class="inline">@csrf<button type="submit" class="text-green-600 hover:text-green-700 text-xs font-medium">Approve</button></form>
                        <form action="{{ route('admin.finance.refunds.reject', $refund) }}" method="POST" class="inline">@csrf<button type="submit" class="text-red-600 hover:text-red-700 text-xs font-medium">Reject</button></form>
                    @elseif($refund->status === \App\Modules\Finance\Enums\RefundStatus::APPROVED)
                        <form action="{{ route('admin.finance.refunds.complete', $refund) }}" method="POST" class="inline">@csrf<button type="submit" class="text-xs font-medium text-[#1E2B27] transition-colors hover:text-[#17302A]">Complete</button></form>
                    @else
                        <span class="text-xs text-[#61706B]">—</span>
                    @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="datatable-empty">
                        <x-nino.empty-state title="No refund requests" description="Refund requests will appear here once customers start filing them." icon="undo" />
                    </td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
<div class="mt-4">{{ $refunds->links() }}</div>
@endsection
