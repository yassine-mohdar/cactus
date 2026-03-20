@extends('admin.layouts.app')

@section('header')
<div class="page-header border-b border-slate-200 pb-4">
    <div>
        <h1 class="page-title">Orders</h1>
        <p class="page-subtitle">Review incoming orders, customer context, and fulfillment status.</p>
    </div>

    <x-admin.button href="{{ route('admin.orders.create') }}" variant="primary" class="gap-2">
        <span class="material-symbols-outlined text-sm">add</span>
        New Order
    </x-admin.button>
</div>
@endsection

@section('content')
<div class="filter-toolbar mb-6">
    <form method="GET" class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
        <div class="grid gap-3 sm:grid-cols-2 xl:w-full xl:max-w-3xl">
            <div class="filter-field sm:col-span-1">
                <label class="filter-label" for="orders-search">Search</label>
                <input id="orders-search" type="text" name="search" placeholder="Reference number or customer..." value="{{ request('search') }}" class="input-field" />
            </div>
            <div class="filter-field sm:col-span-1">
                <label class="filter-label" for="orders-status">Status</label>
                <select id="orders-status" name="status" class="input-field min-w-[160px]">
                    <option value="">All statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <x-admin.button type="submit" variant="primary">Apply Filters</x-admin.button>
            @if(request()->filled('search') || request()->filled('status'))
                <a href="{{ route('admin.orders.index') }}" class="btn-secondary">Clear</a>
            @endif
        </div>
    </form>
</div>

<div class="datatable-shell">
    <div class="datatable-header">
        <div>
            <h2 class="datatable-title">Recent Orders</h2>
            <p class="datatable-subtitle">Browse the latest order activity with clearer customer and total details.</p>
        </div>
        <span class="datatable-meta">{{ number_format($orders->total()) }} records</span>
    </div>

    <x-nino.table>
        <thead class="bg-slate-50 border-b border-slate-300 text-slate-500 font-bold uppercase tracking-widest text-[10px]">
            <tr>
                <th class="text-left">Reference</th>
                <th class="text-left">Customer</th>
                <th class="text-left">Status</th>
                <th class="text-right">Total</th>
                <th class="text-right">Date</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 text-sm">
            @forelse ($orders as $order)
                <tr>
                    <td class="text-left font-mono font-medium">
                        <a href="{{ route('admin.orders.show', $order) }}" class="table-link table-mono">
                            {{ $order->reference_number }}
                        </a>
                    </td>
                    <td class="text-left text-slate-900 font-medium">
                        {{ $order->customer ? $order->customer->full_name : 'Guest' }}
                    </td>
                    <td class="text-left">
                        <span class="px-1.5 py-0.5 bg-slate-100 text-slate-600 border border-slate-200 text-[10px] font-bold uppercase tracking-widest rounded-sm">
                            {{ $order->status->label() }}
                        </span>
                    </td>
                    <td class="text-right font-mono font-medium text-slate-900">
                        {{ number_format($order->grand_total, 2) }} <span class="text-[10px] text-slate-500 font-normal uppercase">{{ $order->currency }}</span>
                    </td>
                    <td class="text-right font-mono text-slate-500 text-[11px]">
                        {{ $order->created_at->format('M d, y H:i') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="datatable-empty">
                        <div class="datatable-empty-panel">
                            <p class="text-sm font-semibold text-slate-900">No orders found.</p>
                            <p class="text-sm text-slate-500">Adjust the search or status filters to broaden the results.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-nino.table>

    @if($orders->hasPages())
        <div class="datatable-footer">
            {{ $orders->links() }}
        </div>
    @endif
</div>
@endsection
