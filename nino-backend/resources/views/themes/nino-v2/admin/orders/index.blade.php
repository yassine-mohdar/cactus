@extends('admin.layouts.app')

@section('title', 'Orders')

@section('header')
    <x-nino.page-header
        title="Orders"
        subtitle="Review incoming orders, payment readiness, and fulfillment status from one operational queue.">
        <x-slot:actions>
            <x-nino.button href="{{ route('admin.orders.create') }}" variant="primary" icon="add">New Order</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="metric-grid">
            <div class="metric-tile">
                <p class="metric-label">Total Orders</p>
                <h3 class="metric-value">{{ number_format($summary['total_orders']) }}</h3>
                <p class="metric-subtitle">All orders currently in the system</p>
            </div>
            <div class="metric-tile">
                <p class="metric-label">Awaiting Payment</p>
                <h3 class="metric-value">{{ number_format($summary['awaiting_payment']) }}</h3>
                <p class="metric-subtitle">Orders blocked before payment confirmation</p>
            </div>
            <div class="metric-tile">
                <p class="metric-label">Preparing</p>
                <h3 class="metric-value">{{ number_format($summary['preparing']) }}</h3>
                <p class="metric-subtitle">Orders currently in warehouse preparation</p>
            </div>
            <div class="metric-tile">
                <p class="metric-label">30-Day Revenue</p>
                <h3 class="metric-value">{{ number_format($summary['gross_30_days'], 2) }}</h3>
                <p class="metric-subtitle">Gross order value captured in the last 30 days</p>
            </div>
        </div>

        <div class="filter-toolbar">
            <form method="GET" class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                <div class="grid gap-3 sm:grid-cols-2 xl:w-full xl:max-w-3xl">
                    <div class="filter-field">
                        <label class="filter-label" for="orders-search">Search</label>
                        <input id="orders-search" type="text" name="search" placeholder="Reference number or customer..." value="{{ request('search') }}" class="input-field" />
                    </div>
                    <div class="filter-field">
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
                    <x-nino.button type="submit" variant="primary">Apply Filters</x-nino.button>
                    @if(request()->filled('search') || request()->filled('status'))
                        <x-nino.button href="{{ route('admin.orders.index') }}" variant="outline">Clear</x-nino.button>
                    @endif
                </div>
            </form>
        </div>

        <div class="datatable-shell">
            <div class="datatable-header">
                <div>
                    <h2 class="datatable-title">Recent Orders</h2>
                    <p class="datatable-subtitle">Browse the latest order activity with clearer customer context and financial totals.</p>
                </div>
                <span class="datatable-meta">{{ number_format($orders->total()) }} records</span>
            </div>

            <x-nino.table>
                <x-slot name="head">
                    <th class="text-left">Reference</th>
                    <th class="text-left">Customer</th>
                    <th class="text-left">Status</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Date</th>
                </x-slot>
                <x-slot name="body">
                    @forelse ($orders as $order)
                        @php
                            $statusTone = match ($order->status->value) {
                                'delivered' => 'success',
                                'shipped', 'paid' => 'info',
                                'failed', 'cancelled', 'refunded' => 'danger',
                                'preparing', 'pending', 'awaiting_payment' => 'warning',
                                default => 'neutral',
                            };
                        @endphp
                        <tr>
                            <td class="text-left">
                                <a href="{{ route('admin.orders.show', $order) }}" class="table-link table-mono">
                                    {{ $order->reference_number }}
                                </a>
                            </td>
                            <td class="text-left">
                                <div class="font-semibold text-[#1E2B27]">{{ $order->customer ? $order->customer->full_name : 'Guest' }}</div>
                                <div class="table-muted">{{ $order->customer?->email ?? 'Guest checkout' }}</div>
                            </td>
                            <td class="text-left">
                                <x-nino.status-badge :tone="$statusTone" size="sm">{{ $order->status->label() }}</x-nino.status-badge>
                            </td>
                            <td class="text-right font-mono font-semibold text-[#1E2B27]">
                                {{ number_format($order->grand_total, 2) }} <span class="text-[10px] font-semibold uppercase tracking-[0.16em] text-[#7A8681]">{{ $order->currency }}</span>
                            </td>
                            <td class="text-right font-mono text-xs text-[#61706B]">
                                {{ $order->created_at->format('M d, y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="datatable-empty">
                                <x-nino.empty-state
                                    title="No orders found"
                                    description="We couldn't find any orders matching the current filters. Adjust the search criteria or clear the filters to broaden the queue."
                                    icon="shopping_bag">
                                    @if(request()->filled('search') || request()->filled('status'))
                                        <x-nino.button href="{{ route('admin.orders.index') }}" variant="outline">Clear All Filters</x-nino.button>
                                    @endif
                                </x-nino.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </x-slot>
            </x-nino.table>

            @if($orders->hasPages())
                <div class="datatable-footer">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
