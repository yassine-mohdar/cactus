@extends('admin.layouts.app')
@section('title', 'Payment Transactions')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Payment Transactions</h1>
        <p class="page-subtitle">Review payment activity, gateway state, and linked orders with clearer row-level actions.</p>
    </div>
</div>
@if(session('success'))<div class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm border border-green-200">{{ session('success') }}</div>@endif

{{-- Stats --}}
<div class="stats-grid mb-6">
    <div class="stat-card">
        <p class="stat-label">Total Volume</p>
        <p class="stat-value text-green-600">{{ number_format($stats['total_volume'], 2) }} <span class="text-xs font-normal">MAD</span></p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Total Fees</p>
        <p class="stat-value text-orange-600">{{ number_format($stats['total_fees'], 2) }} <span class="text-xs font-normal">MAD</span></p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Total Refunds</p>
        <p class="stat-value text-red-600">{{ number_format($stats['total_refunds'], 2) }} <span class="text-xs font-normal">MAD</span></p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Pending</p>
        <p class="stat-value text-yellow-600">{{ $stats['pending_count'] }}</p>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="filter-toolbar mb-6">
    <div class="filter-grid xl:grid-cols-6">
        <div class="filter-field xl:col-span-2"><label class="filter-label" for="txn-search">Search</label><input id="txn-search" type="text" name="search" value="{{ request('search') }}" placeholder="Reference or order..." class="input-field"></div>
        <div class="filter-field"><label class="filter-label" for="txn-status">Status</label>
        <select id="txn-status" name="status" class="input-field">
            <option value="">All</option>
            @foreach(\App\Modules\Finance\Enums\TransactionStatus::cases() as $s)<option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>@endforeach
        </select>
    </div>
    <div class="filter-field"><label class="filter-label" for="txn-type">Type</label>
        <select id="txn-type" name="type" class="input-field">
            <option value="">All</option>
            @foreach(\App\Modules\Finance\Enums\TransactionType::cases() as $t)<option value="{{ $t->value }}" {{ request('type') === $t->value ? 'selected' : '' }}>{{ $t->label() }}</option>@endforeach
        </select>
    </div>
    <div class="filter-field"><label class="filter-label" for="txn-method">Method</label>
        <select id="txn-method" name="method" class="input-field">
            <option value="">All</option>
            @foreach(\App\Modules\Finance\Enums\PaymentMethod::cases() as $m)<option value="{{ $m->value }}" {{ request('method') === $m->value ? 'selected' : '' }}>{{ $m->label() }}</option>@endforeach
        </select>
    </div>
    <div class="filter-field"><label class="filter-label" for="txn-from">From</label><input id="txn-from" type="date" name="date_from" value="{{ request('date_from') }}" class="input-field"></div>
    <div class="filter-field"><label class="filter-label" for="txn-to">To</label><input id="txn-to" type="date" name="date_to" value="{{ request('date_to') }}" class="input-field"></div>
</div>
<div class="mt-3 flex items-center gap-3">
    <x-admin.button type="submit" variant="primary">Apply Filters</x-admin.button>
    @if(request()->hasAny(['search','status','type','method','date_from','date_to']))<a href="{{ route('admin.finance.transactions.index') }}" class="btn-secondary">Clear</a>@endif
</div>
</form>

{{-- Table --}}
<div class="datatable-shell">
    <div class="datatable-header">
        <div>
            <h2 class="datatable-title">Transaction Feed</h2>
            <p class="datatable-subtitle">Real row actions replace click-anywhere behavior, making the table easier to navigate and scan.</p>
        </div>
        <span class="datatable-meta">{{ number_format($transactions->total()) }} transactions</span>
    </div>

    <div class="datatable-scroll">
    <table class="nino-table">
        <thead>
            <tr>
                <th>Reference</th>
                <th>Order</th>
                <th class="text-center">Type</th>
                <th class="text-center">Method</th>
                <th class="text-center">Status</th>
                <th class="text-right">Amount</th>
                <th class="text-right">Fee</th>
                <th>Date</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $txn)
            <tr>
                <td class="px-4 py-3 font-mono text-xs text-slate-900 font-medium"><a href="{{ route('admin.finance.transactions.show', $txn) }}" class="table-link table-mono">{{ $txn->reference }}</a></td>
                <td class="px-4 py-3 text-xs text-slate-500">
                    @if($txn->order)
                        <a href="{{ route('admin.orders.show', $txn->order) }}" class="table-link">{{ $txn->order->reference_number }}</a>
                    @else
                        —
                    @endif
                </td>
                <td class="px-4 py-3 text-center"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $txn->type->badgeColor() }}">{{ $txn->type->label() }}</span></td>
                <td class="px-4 py-3 text-center text-xs">{{ $txn->resolvedPaymentMethodIcon() }} {{ $txn->resolvedPaymentMethodLabel() }}</td>
                <td class="px-4 py-3 text-center"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $txn->status->badgeColor() }}">{{ $txn->status->label() }}</span></td>
                <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ $txn->formattedAmount() }}</td>
                <td class="px-4 py-3 text-right text-xs text-slate-500">{{ $txn->fee_amount > 0 ? number_format($txn->fee_amount, 2) : '—' }}</td>
                <td class="px-4 py-3 text-xs text-slate-500">{{ $txn->created_at->format('M d, H:i') }}</td>
                <td class="px-4 py-3 text-right"><div class="table-actions"><a href="{{ route('admin.finance.transactions.show', $txn) }}" class="table-action-link">Open</a></div></td>
            </tr>
            @empty
            <tr><td colspan="9" class="datatable-empty">
                        <div class="datatable-empty-panel">
                            <p class="text-sm font-semibold text-slate-900">No transactions found.</p>
                            <p class="text-sm text-slate-500">Try broader filters or a different reference search.</p>
                        </div>
                    </td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
<div class="mt-4">{{ $transactions->links() }}</div>
@endsection
