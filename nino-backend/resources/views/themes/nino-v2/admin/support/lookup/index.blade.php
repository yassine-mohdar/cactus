@extends('admin.layouts.app')
@section('title', 'Support Lookup')
@section('content')
<x-nino.page-header
    title="Support Lookup"
    subtitle="Search orders and transactions by any identifier with a clearer split between result types." />

{{-- Search --}}
<form method="GET" class="filter-toolbar mb-6">
    <div class="flex flex-col gap-3 sm:flex-row">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search by reference, email, phone, or customer name..." class="input-field flex-1" autofocus>
        <x-nino.button type="submit" variant="primary">Search</x-nino.button>
    </div>
</form>

@if($searched)
{{-- Orders --}}
<div class="mb-8">
    <div class="mb-3 flex items-center justify-between gap-3">
        <h2 class="text-lg font-bold text-[#1E2B27]">Orders</h2>
        <span class="datatable-meta">{{ $results['orders']->count() }} found</span>
    </div>
    @if($results['orders']->count())
    <div class="datatable-shell">
        <div class="datatable-scroll">
        <table class="nino-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th class="text-center">Status</th>
                    <th class="text-right">Total</th>
                    <th>Date</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($results['orders'] as $order)
                @php
                    $billingAddress = $order->billingAddress;
                    $customer = $order->customer;
                    $customerName = $billingAddress
                        ? trim($billingAddress->first_name . ' ' . $billingAddress->last_name)
                        : trim($customer?->full_name ?: ($customer?->name ?? ''));
                    $customerEmail = $customer?->email;
                    $customerPhone = $billingAddress?->phone ?? $customer?->phone;
                    $orderTone = match ($order->status?->value) {
                        'delivered' => 'success',
                        'shipped', 'paid' => 'info',
                        'failed', 'cancelled', 'refunded' => 'danger',
                        default => 'warning',
                    };
                @endphp
                <tr>
                    <td class="px-4 py-3 font-mono text-xs font-medium text-[#1E2B27]"><a href="{{ route('admin.orders.show', $order) }}" class="table-link table-mono">{{ $order->reference_number }}</a></td>
                    <td class="px-4 py-3 text-sm text-[#1E2B27]">{{ $customerName !== '' ? $customerName : '—' }}</td>
                    <td class="px-4 py-3 text-xs text-[#61706B]">{{ $customerEmail ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs text-[#61706B]">{{ $customerPhone ?? '—' }}</td>
                    <td class="px-4 py-3 text-center"><x-nino.status-badge :tone="$orderTone" size="sm">{{ $order->status?->label() ?? 'N/A' }}</x-nino.status-badge></td>
                    <td class="px-4 py-3 text-right font-semibold text-[#1E2B27]">{{ number_format((float) $order->grand_total, 2) }}</td>
                    <td class="px-4 py-3 text-xs text-[#61706B]">{{ $order->created_at->format('M d, H:i') }}</td>
                    <td class="px-4 py-3 text-right"><div class="table-actions"><a href="{{ route('admin.support.lookup.timeline', $order) }}" class="table-action-link">Timeline</a></div></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    @else
    <x-nino.empty-state title="No orders found" description="Try another reference, email, phone number, or customer name." icon="shopping_bag" />
    @endif
</div>

{{-- Transactions --}}
<div>
    <div class="mb-3 flex items-center justify-between gap-3">
        <h2 class="text-lg font-bold text-[#1E2B27]">Transactions</h2>
        <span class="datatable-meta">{{ $results['transactions']->count() }} found</span>
    </div>
    @if($results['transactions']->count())
    <div class="datatable-shell">
        <div class="datatable-scroll">
        <table class="nino-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th class="text-center">Type</th>
                    <th class="text-center">Status</th>
                    <th class="text-right">Amount</th>
                    <th>Date</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($results['transactions'] as $txn)
                <tr>
                    <td class="px-4 py-3 font-mono text-xs font-medium text-[#1E2B27]"><a href="{{ route('admin.finance.transactions.show', $txn) }}" class="table-link table-mono">{{ $txn->reference }}</a></td>
                    <td class="px-4 py-3 text-center"><span class="inline-flex items-center rounded-md border border-[rgba(120,112,95,0.14)] bg-[#FBFAF7] px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#61706B]">{{ $txn->type->label() }}</span></td>
                    <td class="px-4 py-3 text-center"><span class="inline-flex items-center rounded-md border border-[rgba(120,112,95,0.14)] bg-[#FBFAF7] px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#61706B]">{{ $txn->status->label() }}</span></td>
                    <td class="px-4 py-3 text-right font-semibold text-[#1E2B27]">{{ $txn->formattedAmount() }}</td>
                    <td class="px-4 py-3 text-xs text-[#61706B]">{{ $txn->created_at->format('M d, H:i') }}</td>
                    <td class="px-4 py-3 text-right"><div class="table-actions"><a href="{{ route('admin.finance.transactions.show', $txn) }}" class="table-action-link">Open</a></div></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    @else
    <x-nino.empty-state title="No transactions found" description="Try another transaction reference or gateway identifier." icon="payments" />
    @endif
</div>
@else
<x-nino.empty-state
    title="Search support records"
    description="Enter an order reference, email, phone number, customer name, or transaction reference to search."
    icon="search" />
@endif
@endsection
