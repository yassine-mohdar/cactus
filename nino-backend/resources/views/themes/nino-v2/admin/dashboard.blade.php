@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('header')
    <x-nino.page-header
        title="Dashboard"
        subtitle="Control center for orders, inventory, finance, and support across the last 30 days.">
        <x-slot:actions>
            <x-nino.button href="{{ route('admin.reports.sales') }}" variant="secondary" icon="insights">Sales Report</x-nino.button>
            <x-nino.button href="{{ route('admin.support.lookup') }}" variant="primary" icon="search">Operations Lookup</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
@php
    $topProductChart = [
        'labels' => collect($dashboard['top_products'])->pluck('label')->all(),
        'datasets' => [[
            'label' => 'Revenue',
            'data' => collect($dashboard['top_products'])->pluck('revenue')->map(fn ($value) => round((float) $value, 2))->all(),
            'backgroundColor' => '#245848',
            'borderRadius' => 6,
            'borderSkipped' => false,
            'barThickness' => 14,
        ]],
    ];
@endphp

<div class="space-y-6">
    @if(! empty($dashboard['primary_metrics']))
        <div class="metric-grid">
            @foreach($dashboard['primary_metrics'] as $metric)
                <x-nino.metric-card
                    :title="$metric['title']"
                    :value="$metric['value']"
                    :icon="$metric['icon']"
                    :subtitle="$metric['subtitle']"
                    :change="$metric['change']"
                    :change-type="$metric['changeType']"
                    :color="$metric['tone']" />
            @endforeach
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.7fr)_minmax(320px,0.95fr)]">
        <x-nino.chart-card
            title="Revenue and Order Trend"
            subtitle="{{ $dashboard['range_label'] }}"
            type="line"
            :data="$dashboard['revenue_chart']"
            :options="[
                'plugins' => ['legend' => ['display' => true]],
                'scales' => [
                    'y' => ['title' => ['display' => true, 'text' => 'Revenue']],
                    'y1' => ['title' => ['display' => true, 'text' => 'Orders']],
                ],
            ]"
            :height="280" />

        <div class="space-y-6">
            <x-nino.chart-card
                title="Top Products"
                subtitle="Revenue contribution by product"
                type="bar"
                :data="$topProductChart"
                :options="[
                    'indexAxis' => 'y',
                    'plugins' => ['legend' => ['display' => false]],
                ]"
                :height="280" />

            <x-nino.detail-section title="Order Mix" subtitle="Current status distribution across recent orders.">
                <div class="space-y-3">
                    @foreach($dashboard['status_board'] as $status)
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <x-nino.status-badge :tone="$status['tone']" size="sm">{{ $status['label'] }}</x-nino.status-badge>
                            </div>
                            <span class="font-mono text-sm font-semibold text-[#1E2B27]">{{ number_format($status['count']) }}</span>
                        </div>
                    @endforeach
                </div>
            </x-nino.detail-section>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.65fr)_minmax(320px,0.95fr)]">
        <x-nino.detail-section title="Recent Orders" subtitle="Latest customer and fulfillment activity." noPadding>
            @if(collect($dashboard['recent_orders'])->isNotEmpty())
                <div class="queue-list">
                    @foreach($dashboard['recent_orders'] as $order)
                        <a href="{{ $order['url'] }}" wire:navigate class="queue-row">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-3">
                                    <span class="font-mono text-xs text-[#61706B]">{{ $order['reference'] }}</span>
                                    <p class="truncate text-sm font-medium text-[#1E2B27]">{{ $order['customer'] }}</p>
                                </div>
                                <p class="queue-meta mt-1">{{ $order['date'] }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <x-nino.status-badge :tone="$order['status_tone']" size="sm">{{ $order['status'] }}</x-nino.status-badge>
                                <span class="font-mono text-sm font-semibold text-[#1E2B27]">{{ $order['total'] }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="p-5">
                    <x-nino.empty-state
                        title="No orders yet"
                        description="Recent order activity will appear here as soon as commerce starts flowing."
                        icon="shopping_bag" />
                </div>
            @endif
        </x-nino.detail-section>

        <x-nino.detail-section title="Low Stock Health" subtitle="Items closest to breaching inventory thresholds." noPadding>
            @if(collect($dashboard['low_stock'])->isNotEmpty())
                <div class="queue-list">
                    @foreach($dashboard['low_stock'] as $item)
                        <div class="queue-row">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-[#1E2B27]">{{ $item['name'] }}</p>
                                <div class="mt-1 flex items-center gap-2 text-[11px] text-[#7A8681]">
                                    <span class="font-mono">{{ $item['sku'] }}</span>
                                    <span>&middot;</span>
                                    <span>{{ $item['branch'] }}</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <x-nino.status-badge :tone="$item['tone']" size="sm">
                                    {{ $item['available'] }} available
                                </x-nino.status-badge>
                                <p class="mt-1 text-[11px] text-[#7A8681]">Threshold {{ $item['threshold'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-5">
                    <x-nino.empty-state
                        title="Inventory is healthy"
                        description="No low-stock or out-of-stock items are currently blocking fulfillment."
                        icon="inventory_2" />
                </div>
            @endif
        </x-nino.detail-section>
    </div>

    @if(collect($dashboard['queue_cards'])->isNotEmpty() || collect($dashboard['recent_tickets'])->isNotEmpty())
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)]">
            <x-nino.detail-section title="Operational Queues" subtitle="High-priority work queues surfaced by role and team responsibility.">
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach($dashboard['queue_cards'] as $queue)
                        <div class="metric-tile">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="metric-label">{{ $queue['title'] }}</p>
                                    <h3 class="metric-value">{{ $queue['value'] }}</h3>
                                    <p class="metric-subtitle">{{ $queue['subtitle'] }}</p>
                                </div>
                                <div class="flex h-10 w-10 items-center justify-center rounded-[0.875rem] border border-[rgba(120,112,95,0.14)] bg-[#F6F2EC] text-[#245848]">
                                    <span class="material-symbols-outlined text-[1.25rem]">{{ $queue['icon'] }}</span>
                                </div>
                            </div>
                            <div class="mt-4">
                                <x-nino.button href="{{ $queue['href'] }}" size="sm" variant="secondary">{{ $queue['cta'] }}</x-nino.button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-nino.detail-section>

            <x-nino.detail-section title="Support Pulse" subtitle="Most recent tickets that still require active handling." noPadding>
                @if(collect($dashboard['recent_tickets'])->isNotEmpty())
                    <div class="queue-list">
                        @foreach($dashboard['recent_tickets'] as $ticket)
                            <a href="{{ $ticket['url'] }}" wire:navigate class="queue-row">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs text-[#61706B]">{{ $ticket['reference'] }}</span>
                                        <x-nino.status-badge :tone="$ticket['status_tone']" size="sm">{{ $ticket['status'] }}</x-nino.status-badge>
                                    </div>
                                    <p class="mt-2 truncate text-sm font-medium text-[#1E2B27]">{{ $ticket['subject'] }}</p>
                                </div>
                                <span class="material-symbols-outlined text-[#9AA7A1]">arrow_forward</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="p-5">
                        <x-nino.empty-state
                            title="No active support tickets"
                            description="Customer support issues that need attention will surface here automatically."
                            icon="support_agent" />
                    </div>
                @endif
            </x-nino.detail-section>
        </div>
    @endif
</div>
@endsection
