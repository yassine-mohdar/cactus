@extends('admin.layouts.app')
@section('title', 'Support Lookup')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Support Lookup</h1>
        <p class="page-subtitle">Search orders and transactions by any identifier with a clearer split between result types.</p>
    </div>
</div>

{{-- Search --}}
<form method="GET" class="filter-toolbar mb-6">
    <div class="flex flex-col gap-3 sm:flex-row">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search by order reference, tracking number, email, phone, or customer name..." class="input-field flex-1" autofocus>
        <button type="submit" class="btn-primary px-6">Search</button>
    </div>
</form>

@if($searched)
{{-- Orders --}}
<div class="mb-8">
    <div class="mb-3 flex items-center justify-between gap-3">
        <h2 class="text-lg font-bold text-slate-900">Orders</h2>
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
                    <th>Tracking</th>
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
                    $latestShipment = $order->shipments->sortByDesc('id')->first();
                    $trackingValue = $latestShipment?->tracking_number ?: $latestShipment?->external_reference;
                    $providerReference = $latestShipment?->external_reference && $latestShipment?->external_reference !== $trackingValue
                        ? $latestShipment->external_reference
                        : null;
                @endphp
                <tr>
                    <td class="px-4 py-3 font-mono text-xs font-medium text-slate-900"><a href="{{ route('admin.orders.show', $order) }}" class="table-link table-mono">{{ $order->reference_number }}</a></td>
                    <td class="px-4 py-3 text-sm text-slate-900">{{ $customerName !== '' ? $customerName : '—' }}</td>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ $customerEmail ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ $customerPhone ?? '—' }}</td>
                    <td class="px-4 py-3 font-mono text-xs text-slate-500">
                        <span class="block">{{ $trackingValue ?? '—' }}</span>
                        @if($providerReference)
                            <span class="mt-1 block text-[11px] text-slate-400">Provider: {{ $providerReference }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $order->status?->label() ?? 'N/A' }}</span></td>
                    <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ number_format((float) $order->grand_total, 2) }}</td>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ $order->created_at->format('M d, H:i') }}</td>
                    <td class="px-4 py-3 text-right"><div class="table-actions"><a href="{{ route('admin.support.lookup.timeline', $order) }}" class="table-action-link">Timeline</a></div></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    @else
    <p class="text-sm text-slate-500">No orders found. Try another reference, tracking number, email, phone number, or customer name.</p>
    @endif
</div>

{{-- Transactions --}}
<div>
    <div class="mb-3 flex items-center justify-between gap-3">
        <h2 class="text-lg font-bold text-slate-900">Transactions</h2>
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
                    <td class="px-4 py-3 font-mono text-xs font-medium text-slate-900"><a href="{{ route('admin.finance.transactions.show', $txn) }}" class="table-link table-mono">{{ $txn->reference }}</a></td>
                    <td class="px-4 py-3 text-center"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $txn->type->badgeColor() }}">{{ $txn->type->label() }}</span></td>
                    <td class="px-4 py-3 text-center"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $txn->status->badgeColor() }}">{{ $txn->status->label() }}</span></td>
                    <td class="px-4 py-3 text-right font-semibold">{{ $txn->formattedAmount() }}</td>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ $txn->created_at->format('M d, H:i') }}</td>
                    <td class="px-4 py-3 text-right"><div class="table-actions"><a href="{{ route('admin.finance.transactions.show', $txn) }}" class="table-action-link">Open</a></div></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    @else
    <p class="text-sm text-slate-500">No transactions found.</p>
    @endif
</div>
@else
<div class="text-center py-16">
    <svg class="mx-auto h-12 w-12 text-slate-500/40" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"></path></svg>
    <p class="mt-3 text-sm text-slate-500">Enter an order reference, tracking number, email, phone, name, or transaction reference to search.</p>
</div>
@endif
@endsection
