@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('header')
    <x-nino.page-header
        title="Dashboard"
        subtitle="Control center for orders, inventory, finance, and support across {{ strtolower($dashboard['range_label']) }}.">
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

<x-nino.dashboard-layout>
    <div class="filter-toolbar">
        <x-nino.tab-strip label="Dashboard window" class="items-center">
            @foreach($dashboard['date_presets'] as $preset)
                <a
                    href="{{ route('admin.dashboard', ['preset' => $preset['key']]) }}"
                    class="tab-pill {{ $dashboard['active_preset'] === $preset['key'] ? 'tab-pill-active' : '' }}"
                >
                    {{ $preset['label'] }}
                </a>
            @endforeach
        </x-nino.tab-strip>
    </div>

    @if($dashboard['is_super_admin'])
        <x-nino.dashboard-section
            title="Executive Pulse"
            subtitle="Super Admin widgets focused on topline commerce health and payment risk."
            grid-class="grid gap-6 xl:grid-cols-[minmax(0,1.55fr)_minmax(320px,0.95fr)]">
            <x-nino.chart-card
                title="Revenue Trend"
                subtitle="GMV trend across {{ strtolower($dashboard['range_label']) }}"
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

            <div class="grid gap-4 sm:grid-cols-3 xl:grid-cols-1">
                @foreach($dashboard['super_admin_widgets'] as $metric)
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
        </x-nino.dashboard-section>

        <x-nino.dashboard-section
            title="Network Health"
            subtitle="Operational widgets covering stock pressure, shipping risk, and network coverage."
            grid-class="grid gap-6 xl:grid-cols-2">
            <x-nino.detail-section title="Low Stock Alerts" subtitle="Items closest to breaching inventory thresholds.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['super_admin_health']['low_stock_alerts']['href'] }}" size="sm" variant="secondary">Open inventory</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['super_admin_health']['low_stock_alerts']['items'])->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($dashboard['super_admin_health']['low_stock_alerts']['items'] as $item)
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-[#1E2B27]">{{ $item['name'] }}</p>
                                    <div class="mt-1 flex items-center gap-2 text-[11px] text-[#7A8681]">
                                        <span class="font-mono">{{ $item['sku'] }}</span>
                                        <span>&middot;</span>
                                        <span>{{ $item['branch'] }}</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <x-nino.status-badge :tone="$item['tone']" size="sm">{{ $item['available'] }} available</x-nino.status-badge>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">Threshold {{ $item['threshold'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-nino.empty-state
                        title="No stock alerts"
                        description="Inventory thresholds are currently healthy across all tracked branches."
                        icon="inventory_2" />
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Shipping Exceptions" subtitle="Failed deliveries, returns, and flagged shipment issues.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['super_admin_health']['shipping_exceptions']['href'] }}" size="sm" variant="secondary">Open shipments</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['super_admin_health']['shipping_exceptions']['items'])->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($dashboard['super_admin_health']['shipping_exceptions']['items'] as $shipment)
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-mono text-xs text-[#61706B]">{{ $shipment['reference'] }}</p>
                                    <p class="mt-1 text-sm font-medium text-[#1E2B27]">{{ $shipment['tracking'] }}</p>
                                </div>
                                <x-nino.status-badge :tone="$shipment['tone']" size="sm">{{ $shipment['status'] }}</x-nino.status-badge>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-nino.empty-state
                        title="No shipping exceptions"
                        description="Delivery issues, failed drops, and returns will surface here automatically."
                        icon="local_shipping" />
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Franchise Performance Summary" subtitle="Coverage and stock-health snapshot by franchise network.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['super_admin_health']['franchise_performance']['href'] }}" size="sm" variant="secondary">Open branches</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['super_admin_health']['franchise_performance']['items'])->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($dashboard['super_admin_health']['franchise_performance']['items'] as $franchise)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-[#1E2B27]">{{ $franchise['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ number_format($franchise['branches']) }} branches · {{ number_format($franchise['staff']) }} staff</p>
                                </div>
                                <div class="text-right">
                                    <x-nino.status-badge :tone="$franchise['tone']" size="sm">{{ number_format($franchise['stock_alerts']) }} alerts</x-nino.status-badge>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ number_format($franchise['available_units']) }} available units</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-nino.empty-state
                        title="No franchise network yet"
                        description="Franchise-level operational summaries will appear once the network is seeded."
                        icon="hub" />
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Branch Performance Summary" subtitle="Branch-level stock pressure and staffing coverage.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['super_admin_health']['branch_performance']['href'] }}" size="sm" variant="secondary">Review branches</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['super_admin_health']['branch_performance']['items'])->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($dashboard['super_admin_health']['branch_performance']['items'] as $branch)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-[#1E2B27]">{{ $branch['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ $branch['franchise'] }} · {{ number_format($branch['staff']) }} staff · {{ number_format($branch['tracked_skus']) }} SKUs</p>
                                </div>
                                <div class="text-right">
                                    <x-nino.status-badge :tone="$branch['tone']" size="sm">{{ number_format($branch['stock_alerts']) }} alerts</x-nino.status-badge>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ number_format($branch['available_units']) }} available units</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-nino.empty-state
                        title="No branches yet"
                        description="Branch-level performance widgets will populate once branch organizations are active."
                        icon="storefront" />
                @endif
            </x-nino.detail-section>
        </x-nino.dashboard-section>

        <x-nino.dashboard-section
            title="Command Center"
            subtitle="Super Admin widgets for support backlog, finance exposure, and system-health triage."
            grid-class="grid gap-6 xl:grid-cols-3">
            <x-nino.detail-section title="Support KPI Widget" subtitle="Escalation pressure and unresolved support load.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['super_admin_command']['support_kpi']['href'] }}" size="sm" variant="secondary">Open issues</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-3 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Open</p>
                        <p class="metric-value">{{ number_format($dashboard['super_admin_command']['support_kpi']['open']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Urgent</p>
                        <p class="metric-value">{{ number_format($dashboard['super_admin_command']['support_kpi']['urgent']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Unassigned</p>
                        <p class="metric-value">{{ number_format($dashboard['super_admin_command']['support_kpi']['unassigned']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['super_admin_command']['support_kpi']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-[rgba(120,112,95,0.14)] pt-4">
                        @foreach($dashboard['super_admin_command']['support_kpi']['items'] as $issue)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-mono text-xs text-[#61706B]">{{ $issue['reference'] }}</p>
                                    <p class="mt-1 truncate text-sm font-medium text-[#1E2B27]">{{ $issue['subject'] }}</p>
                                </div>
                                <x-nino.status-badge :tone="$issue['tone']" size="sm">{{ $issue['priority'] }}</x-nino.status-badge>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Finance Summary Widget" subtitle="Captured revenue and queues needing finance review.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['super_admin_command']['finance_summary']['href'] }}" size="sm" variant="secondary">Open finance</x-nino.button>
                </x-slot:header>

                <div class="space-y-3">
                    <div class="metric-tile">
                        <p class="metric-label">Captured Revenue</p>
                        <p class="metric-value">{{ $dashboard['super_admin_command']['finance_summary']['captured'] }}</p>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-[0.875rem] border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] px-4 py-3">
                        <div>
                            <p class="metric-label">Payment Review</p>
                            <p class="metric-subtitle">{{ $dashboard['super_admin_command']['finance_summary']['payment_review_amount'] }}</p>
                        </div>
                        <x-nino.status-badge tone="warning" size="sm">{{ number_format($dashboard['super_admin_command']['finance_summary']['payment_review_count']) }} queued</x-nino.status-badge>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-[0.875rem] border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] px-4 py-3">
                        <div>
                            <p class="metric-label">Refund Queue</p>
                            <p class="metric-subtitle">{{ $dashboard['super_admin_command']['finance_summary']['refund_queue_amount'] }}</p>
                        </div>
                        <x-nino.status-badge tone="info" size="sm">{{ number_format($dashboard['super_admin_command']['finance_summary']['refund_queue_count']) }} queued</x-nino.status-badge>
                    </div>
                </div>
            </x-nino.detail-section>

            <x-nino.detail-section title="System Alerts Widget" subtitle="Latest critical, error, and warning incidents from observability.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['super_admin_command']['system_alerts']['href'] }}" size="sm" variant="secondary">Open observability</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Critical</p>
                        <p class="metric-value">{{ number_format($dashboard['super_admin_command']['system_alerts']['critical']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Warnings</p>
                        <p class="metric-value">{{ number_format($dashboard['super_admin_command']['system_alerts']['warning']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['super_admin_command']['system_alerts']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-[rgba(120,112,95,0.14)] pt-4">
                        @foreach($dashboard['super_admin_command']['system_alerts']['items'] as $event)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-[#1E2B27]">{{ $event['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ $event['source'] }} · {{ $event['occurred_at'] }}</p>
                                </div>
                                <x-nino.status-badge :tone="$event['tone']" size="sm">{{ $event['severity'] }}</x-nino.status-badge>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-4">
                        <x-nino.empty-state
                            title="No system alerts"
                            description="Critical and warning observability incidents will surface here automatically."
                            icon="monitor_heart" />
                    </div>
                @endif
            </x-nino.detail-section>
        </x-nino.dashboard-section>
    @endif

    @if(! empty($dashboard['primary_metrics']))
        <x-nino.dashboard-section
            title="Overview"
            subtitle="High-level operational signals for the selected reporting window."
            grid-class="metric-grid">
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
        </x-nino.dashboard-section>
    @endif

    <x-nino.dashboard-section
        :title="$dashboard['is_super_admin'] ? 'Merchandise Mix' : 'Performance'"
        :subtitle="$dashboard['is_super_admin']
            ? 'Merchandising and status widgets supporting the executive view.'
            : 'Reusable analytics widgets tuned for the selected time window.'"
        :grid-class="$dashboard['is_super_admin']
            ? 'grid gap-6 xl:grid-cols-2'
            : 'grid gap-6 xl:grid-cols-[minmax(0,1.7fr)_minmax(320px,0.95fr)]'">
        @unless($dashboard['is_super_admin'])
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
        @endunless

        <div class="{{ $dashboard['is_super_admin'] ? '' : 'space-y-6' }}">
            <x-nino.chart-card
                title="Top Products"
                subtitle="Revenue contribution by product"
                type="bar"
                :data="$topProductChart"
                :options="[
                    'indexAxis' => 'y',
                    'plugins' => ['legend' => ['display' => false]],
                ]"
                :height="280"
                empty-title="No product revenue yet"
                empty-description="Products with sales in this window will appear here automatically." />

            <x-nino.detail-section title="Order Mix" subtitle="Current status distribution across recent orders." class="{{ $dashboard['is_super_admin'] ? 'mt-6' : '' }}">
                @if(collect($dashboard['status_board'])->sum('count') > 0)
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
                @else
                    <x-nino.empty-state
                        title="No order mix yet"
                        description="Status distribution will appear once orders are created in the selected range."
                        icon="stacked_bar_chart" />
                @endif
            </x-nino.detail-section>
        </div>
    </x-nino.dashboard-section>

    @if(collect($dashboard['platform_action_queue'])->isNotEmpty())
        <x-nino.dashboard-section
            title="Platform Action Queue"
            subtitle="Cross-team operational priorities that Platform Admins can dispatch immediately."
            grid-class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach($dashboard['platform_action_queue'] as $item)
                <div class="metric-tile">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="metric-label">{{ $item['title'] }}</p>
                            <h3 class="metric-value">{{ number_format($item['value']) }}</h3>
                            <p class="metric-subtitle">{{ $item['meta'] }}</p>
                        </div>
                        <x-nino.status-badge :tone="$item['tone']" size="sm">{{ ucfirst($item['tone']) }}</x-nino.status-badge>
                    </div>
                    <div class="mt-4">
                        <x-nino.button href="{{ $item['href'] }}" size="sm" variant="secondary">{{ $item['cta'] }}</x-nino.button>
                    </div>
                </div>
            @endforeach
        </x-nino.dashboard-section>
    @endif

    @if(! empty($dashboard['platform_workspace']))
        <x-nino.dashboard-section
            title="Platform Admin Workspace"
            subtitle="Shared operational widgets for orders, inventory, finance, and content oversight."
            grid-class="grid gap-6 xl:grid-cols-2">
            <x-nino.detail-section title="Recent Orders Widget" subtitle="Fresh commerce activity for platform-level monitoring." noPadding>
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['platform_workspace']['recent_orders']['href'] }}" size="sm" variant="secondary">Open orders</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['platform_workspace']['recent_orders']['items'])->isNotEmpty())
                    <div class="queue-list">
                        @foreach($dashboard['platform_workspace']['recent_orders']['items'] as $order)
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
                            title="No recent orders"
                            description="New orders will appear here as soon as commerce activity starts."
                            icon="shopping_bag" />
                    </div>
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Stock Alerts Widget" subtitle="Current stock pressure across tracked inventory." noPadding>
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['platform_workspace']['stock_alerts']['href'] }}" size="sm" variant="secondary">Open inventory</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['platform_workspace']['stock_alerts']['items'])->isNotEmpty())
                    <div class="queue-list">
                        @foreach($dashboard['platform_workspace']['stock_alerts']['items'] as $item)
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
                                    <x-nino.status-badge :tone="$item['tone']" size="sm">{{ $item['available'] }} available</x-nino.status-badge>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">Threshold {{ $item['threshold'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-5">
                        <x-nino.empty-state
                            title="No stock alerts"
                            description="Low-stock and out-of-stock items will surface here automatically."
                            icon="inventory_2" />
                    </div>
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Payment Exceptions Widget" subtitle="Pending and failed payment items that need review." noPadding>
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['platform_workspace']['payment_exceptions']['href'] }}" size="sm" variant="secondary">Open finance</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['platform_workspace']['payment_exceptions']['items'])->isNotEmpty())
                    <div class="queue-list">
                        @foreach($dashboard['platform_workspace']['payment_exceptions']['items'] as $transaction)
                            <div class="queue-row">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-3">
                                        <span class="font-mono text-xs text-[#61706B]">{{ $transaction['reference'] }}</span>
                                        <p class="truncate text-sm font-medium text-[#1E2B27]">{{ $transaction['order_reference'] }}</p>
                                    </div>
                                    <p class="queue-meta mt-1">{{ $transaction['amount'] }}</p>
                                </div>
                                <x-nino.status-badge :tone="$transaction['tone']" size="sm">{{ $transaction['status'] }}</x-nino.status-badge>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-5">
                        <x-nino.empty-state
                            title="No payment exceptions"
                            description="Pending and failed transactions will show up here for quick triage."
                            icon="credit_card_off" />
                    </div>
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Content/Promo Highlights Widget" subtitle="Editorial and promotions signals for platform operators.">
                <div class="grid grid-cols-2 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Draft Posts</p>
                        <p class="metric-value">{{ number_format($dashboard['platform_workspace']['content_promo_highlights']['draft_posts']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Scheduled Posts</p>
                        <p class="metric-value">{{ number_format($dashboard['platform_workspace']['content_promo_highlights']['scheduled_posts']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Active Coupons</p>
                        <p class="metric-value">{{ number_format($dashboard['platform_workspace']['content_promo_highlights']['active_coupons']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Recoverable Carts</p>
                        <p class="metric-value">{{ number_format($dashboard['platform_workspace']['content_promo_highlights']['recoverable_carts']) }}</p>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <x-nino.button href="{{ $dashboard['platform_workspace']['content_promo_highlights']['content_href'] }}" size="sm" variant="secondary">Open content</x-nino.button>
                    <x-nino.button href="{{ $dashboard['platform_workspace']['content_promo_highlights']['promo_href'] }}" size="sm" variant="secondary">Open promotions</x-nino.button>
                </div>
            </x-nino.detail-section>
        </x-nino.dashboard-section>
    @endif

    @if(! empty($dashboard['franchise_workspace']))
        <x-nino.dashboard-section
            title="Franchise Workspace"
            subtitle="Franchise-level branch widgets built from current branch-scoped inventory, staffing, and activity data."
            grid-class="grid gap-6 xl:grid-cols-2">
            <x-nino.detail-section title="Sales by Branch Widget" subtitle="Operational demand proxy by branch using recent inventory movement because direct branch revenue attribution is not modeled yet.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['franchise_workspace']['sales_by_branch']['href'] }}" size="sm" variant="secondary">Open branches</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['franchise_workspace']['sales_by_branch']['items'])->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($dashboard['franchise_workspace']['sales_by_branch']['items'] as $branch)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-[#1E2B27]">{{ $branch['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ $branch['franchise'] }} · {{ number_format($branch['active_staff']) }} staff · {{ number_format($branch['available_units']) }} available units</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-mono text-sm font-semibold text-[#1E2B27]">{{ number_format($branch['activity_count']) }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ number_format($branch['outbound_units']) }} outbound units</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-nino.empty-state
                        title="No branch activity yet"
                        description="Branch demand proxies will appear once inventory movement starts across the franchise tree."
                        icon="monitoring" />
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Branch Stock Overview Widget" subtitle="Stock pressure, SKU coverage, and available units by branch.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['franchise_workspace']['branch_stock_overview']['href'] }}" size="sm" variant="secondary">Open inventory</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['franchise_workspace']['branch_stock_overview']['items'])->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($dashboard['franchise_workspace']['branch_stock_overview']['items'] as $branch)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-[#1E2B27]">{{ $branch['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ number_format($branch['tracked_skus']) }} tracked SKUs</p>
                                </div>
                                <div class="text-right">
                                    <x-nino.status-badge :tone="$branch['tone']" size="sm">{{ number_format($branch['stock_alerts']) }} alerts</x-nino.status-badge>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ number_format($branch['available_units']) }} available units</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-nino.empty-state
                        title="No branch stock data yet"
                        description="Branch stock coverage will surface here once inventory is assigned across franchise branches."
                        icon="inventory_2" />
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Staff Performance Summary Widget" subtitle="Active staffing and recent operator activity by branch.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['franchise_workspace']['staff_performance_summary']['href'] }}" size="sm" variant="secondary">Open staff</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['franchise_workspace']['staff_performance_summary']['items'])->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($dashboard['franchise_workspace']['staff_performance_summary']['items'] as $branch)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-[#1E2B27]">{{ $branch['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ number_format($branch['active_staff']) }} active staff</p>
                                </div>
                                <x-nino.status-badge :tone="$branch['tone']" size="sm">{{ number_format($branch['recently_active_staff']) }} active in 7d</x-nino.status-badge>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-nino.empty-state
                        title="No branch staff activity yet"
                        description="Recent staff activity will appear here once franchise staff start using the admin."
                        icon="group" />
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Branch Order Metrics Widget" subtitle="Branch order-pressure proxy using reserved stock and recent movement because orders are not directly attached to branches yet.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['franchise_workspace']['branch_order_metrics']['href'] }}" size="sm" variant="secondary">Review branches</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['franchise_workspace']['branch_order_metrics']['items'])->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($dashboard['franchise_workspace']['branch_order_metrics']['items'] as $branch)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-[#1E2B27]">{{ $branch['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ number_format($branch['recent_movements']) }} recent movements · {{ number_format($branch['stock_alerts']) }} stock alerts</p>
                                </div>
                                <x-nino.status-badge :tone="$branch['tone']" size="sm">{{ number_format($branch['reserved_units']) }} reserved units</x-nino.status-badge>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-nino.empty-state
                        title="No branch order pressure yet"
                        description="Reserved stock and movement-based branch order signals will appear here as branches become operational."
                        icon="receipt_long" />
                @endif
            </x-nino.detail-section>
        </x-nino.dashboard-section>
    @endif

    @if(! empty($dashboard['branch_workspace']))
        <x-nino.dashboard-section
            title="Branch Workspace"
            subtitle="Branch-level operations for {{ $dashboard['branch_workspace']['branch_name'] }} within {{ $dashboard['branch_workspace']['franchise_name'] }}."
            grid-class="grid gap-6 xl:grid-cols-2">
            <x-nino.detail-section title="Orders to Prepare Widget" subtitle="Reserved-stock preparation queue for this branch.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['branch_workspace']['orders_to_prepare']['href'] }}" size="sm" variant="secondary">Open branch</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Reserved Units</p>
                        <p class="metric-value">{{ number_format($dashboard['branch_workspace']['orders_to_prepare']['reserved_units']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Reservation Events</p>
                        <p class="metric-value">{{ number_format($dashboard['branch_workspace']['orders_to_prepare']['reservation_events']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['branch_workspace']['orders_to_prepare']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-[rgba(120,112,95,0.14)] pt-4">
                        @foreach($dashboard['branch_workspace']['orders_to_prepare']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-[#1E2B27]">{{ $item['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]"><span class="font-mono">{{ $item['sku'] }}</span> · {{ $item['date'] }}</p>
                                </div>
                                <p class="max-w-[12rem] text-right text-[11px] text-[#7A8681]">{{ $item['notes'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Branch Stock Widgets" subtitle="Tracked SKUs, available units, and low-stock blockers for the branch.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['branch_workspace']['stock_widgets']['href'] }}" size="sm" variant="secondary">Open inventory</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-3 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Tracked SKUs</p>
                        <p class="metric-value">{{ number_format($dashboard['branch_workspace']['stock_widgets']['tracked_skus']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Available Units</p>
                        <p class="metric-value">{{ number_format($dashboard['branch_workspace']['stock_widgets']['available_units']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Stock Alerts</p>
                        <p class="metric-value">{{ number_format($dashboard['branch_workspace']['stock_widgets']['stock_alerts']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['branch_workspace']['stock_widgets']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-[rgba(120,112,95,0.14)] pt-4">
                        @foreach($dashboard['branch_workspace']['stock_widgets']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-[#1E2B27]">{{ $item['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681] font-mono">{{ $item['sku'] }}</p>
                                </div>
                                <div class="text-right">
                                    <x-nino.status-badge :tone="$item['tone']" size="sm">{{ $item['available'] }} available</x-nino.status-badge>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">Threshold {{ $item['threshold'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Dispatch Queue Widget" subtitle="Outbound branch movement proxy for dispatch pressure because shipments are not branch-owned in the schema yet.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['branch_workspace']['dispatch_queue']['href'] }}" size="sm" variant="secondary">Open movements</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Outbound Events</p>
                        <p class="metric-value">{{ number_format($dashboard['branch_workspace']['dispatch_queue']['outbound_events']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Outbound Units</p>
                        <p class="metric-value">{{ number_format($dashboard['branch_workspace']['dispatch_queue']['outbound_units']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['branch_workspace']['dispatch_queue']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-[rgba(120,112,95,0.14)] pt-4">
                        @foreach($dashboard['branch_workspace']['dispatch_queue']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-[#1E2B27]">{{ $item['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]"><span class="font-mono">{{ $item['sku'] }}</span> · {{ $item['date'] }}</p>
                                </div>
                                <div class="text-right">
                                    <x-nino.status-badge tone="info" size="sm">{{ number_format($item['units']) }} units</x-nino.status-badge>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ $item['reason'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Branch Issue Queue Widget" subtitle="Recent branch blockers from stock incidents and adjustment-driven issues.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['branch_workspace']['issue_queue']['href'] }}" size="sm" variant="secondary">Review branch</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Issue Events</p>
                        <p class="metric-value">{{ number_format($dashboard['branch_workspace']['issue_queue']['issue_events']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Stock Alerts</p>
                        <p class="metric-value">{{ number_format($dashboard['branch_workspace']['issue_queue']['stock_alerts']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['branch_workspace']['issue_queue']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-[rgba(120,112,95,0.14)] pt-4">
                        @foreach($dashboard['branch_workspace']['issue_queue']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-[#1E2B27]">{{ $item['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]"><span class="font-mono">{{ $item['sku'] }}</span> · {{ $item['date'] }}</p>
                                </div>
                                <div class="text-right">
                                    <x-nino.status-badge tone="warning" size="sm">{{ number_format($item['units']) }} units</x-nino.status-badge>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ $item['reason'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-nino.detail-section>
        </x-nino.dashboard-section>
    @endif

    @if(! empty($dashboard['support_workspace']))
        <x-nino.dashboard-section
            title="Support Workspace"
            subtitle="Fast support tools for order lookup, customer lookup, open-case handling, and recent issue activity."
            grid-class="grid gap-6 xl:grid-cols-2">
            <x-nino.detail-section title="Order Lookup Widget" subtitle="Quick access to recent orders and preparation-state lookups.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['support_workspace']['order_lookup']['href'] }}" size="sm" variant="secondary">Open support lookup</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Searchable Orders</p>
                        <p class="metric-value">{{ number_format($dashboard['support_workspace']['order_lookup']['searchable_orders']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Preparing Orders</p>
                        <p class="metric-value">{{ number_format($dashboard['support_workspace']['order_lookup']['preparing_orders']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['support_workspace']['order_lookup']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-[rgba(120,112,95,0.14)] pt-4">
                        @foreach($dashboard['support_workspace']['order_lookup']['items'] as $item)
                            <a href="{{ $item['href'] }}" class="flex items-start justify-between gap-3 transition-opacity hover:opacity-80">
                                <div class="min-w-0">
                                    <p class="font-mono text-xs text-[#61706B]">{{ $item['reference'] }}</p>
                                    <p class="mt-1 truncate text-sm font-medium text-[#1E2B27]">{{ $item['customer'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ $item['meta'] }}</p>
                                </div>
                                <x-nino.status-badge :tone="$item['status_tone']" size="sm">{{ $item['status'] }}</x-nino.status-badge>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="mt-4">
                        <x-nino.empty-state
                            title="No recent orders to triage"
                            description="Recent order references will appear here for fast support lookup."
                            icon="shopping_bag" />
                    </div>
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Customer Lookup Widget" subtitle="Recent customer identities ready for support search and follow-up.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['support_workspace']['customer_lookup']['href'] }}" size="sm" variant="secondary">Lookup customer</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Active Customers</p>
                        <p class="metric-value">{{ number_format($dashboard['support_workspace']['customer_lookup']['active_customers']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Open Customer Cases</p>
                        <p class="metric-value">{{ number_format($dashboard['support_workspace']['customer_lookup']['open_customer_cases']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['support_workspace']['customer_lookup']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-[rgba(120,112,95,0.14)] pt-4">
                        @foreach($dashboard['support_workspace']['customer_lookup']['items'] as $item)
                            <a href="{{ $item['href'] }}" class="flex items-start justify-between gap-3 transition-opacity hover:opacity-80">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-[#1E2B27]">{{ $item['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ $item['contact'] }}</p>
                                </div>
                                <p class="max-w-[11rem] text-right text-[11px] text-[#7A8681]">{{ $item['context'] }}</p>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="mt-4">
                        <x-nino.empty-state
                            title="No customer contacts yet"
                            description="Customer identities from active support and order activity will appear here."
                            icon="group" />
                    </div>
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Open Cases / Refund Queue Widget" subtitle="Active support workload plus refund exposure needing follow-up.">
                <div class="grid grid-cols-2 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Open Cases</p>
                        <p class="metric-value">{{ number_format($dashboard['support_workspace']['open_cases_refund_queue']['open_cases']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Urgent Cases</p>
                        <p class="metric-value">{{ number_format($dashboard['support_workspace']['open_cases_refund_queue']['urgent_cases']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Refund Queue</p>
                        <p class="metric-value">{{ number_format($dashboard['support_workspace']['open_cases_refund_queue']['refund_queue_count']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Refund Amount</p>
                        <p class="metric-value text-base">{{ $dashboard['support_workspace']['open_cases_refund_queue']['refund_queue_amount'] }}</p>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 border-t border-[rgba(120,112,95,0.14)] pt-4 md:grid-cols-2">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="metric-label">Open Cases</p>
                            <x-nino.button href="{{ $dashboard['support_workspace']['open_cases_refund_queue']['issues_href'] }}" size="sm" variant="secondary">Open issues</x-nino.button>
                        </div>
                        @forelse($dashboard['support_workspace']['open_cases_refund_queue']['issues'] as $issue)
                            <a href="{{ $issue['href'] }}" class="flex items-start justify-between gap-3 transition-opacity hover:opacity-80">
                                <div class="min-w-0">
                                    <p class="font-mono text-xs text-[#61706B]">{{ $issue['reference'] }}</p>
                                    <p class="mt-1 truncate text-sm font-medium text-[#1E2B27]">{{ $issue['subject'] }}</p>
                                </div>
                                <div class="flex flex-col items-end gap-1">
                                    <x-nino.status-badge :tone="$issue['status_tone']" size="sm">{{ $issue['status'] }}</x-nino.status-badge>
                                    <x-nino.status-badge :tone="$issue['priority_tone']" size="sm">{{ $issue['priority'] }}</x-nino.status-badge>
                                </div>
                            </a>
                        @empty
                            <x-nino.empty-state
                                title="No open cases"
                                description="Active support issues will appear here automatically."
                                icon="support_agent" />
                        @endforelse
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="metric-label">Refund Queue</p>
                            <x-nino.button href="{{ $dashboard['support_workspace']['open_cases_refund_queue']['refunds_href'] }}" size="sm" variant="secondary">Open refunds</x-nino.button>
                        </div>
                        @forelse($dashboard['support_workspace']['open_cases_refund_queue']['refunds'] as $refund)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-mono text-xs text-[#61706B]">{{ $refund['reference'] }}</p>
                                    <p class="mt-1 truncate text-sm font-medium text-[#1E2B27]">{{ $refund['order_reference'] }}</p>
                                </div>
                                <div class="flex flex-col items-end gap-1">
                                    <x-nino.status-badge :tone="$refund['tone']" size="sm">{{ $refund['status'] }}</x-nino.status-badge>
                                    <p class="font-mono text-xs text-[#61706B]">{{ $refund['amount'] }}</p>
                                </div>
                            </div>
                        @empty
                            <x-nino.empty-state
                                title="No queued refunds"
                                description="Refund requests awaiting review will appear here."
                                icon="receipt_long" />
                        @endforelse
                    </div>
                </div>
            </x-nino.detail-section>

            <x-nino.detail-section title="Recent Issue Timeline Widget" subtitle="Latest support activity and status movement across current cases.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['support_workspace']['recent_issue_timeline']['href'] }}" size="sm" variant="secondary">Open support issues</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['support_workspace']['recent_issue_timeline']['items'])->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($dashboard['support_workspace']['recent_issue_timeline']['items'] as $item)
                            <a href="{{ $item['href'] }}" class="flex items-start justify-between gap-3 transition-opacity hover:opacity-80">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs text-[#61706B]">{{ $item['reference'] }}</span>
                                        <span class="text-[11px] uppercase tracking-[0.18em] text-[#9AA7A1]">{{ $item['action'] }}</span>
                                    </div>
                                    <p class="mt-2 text-sm font-medium text-[#1E2B27]">{{ $item['description'] }}</p>
                                </div>
                                <p class="text-[11px] text-[#7A8681]">{{ $item['occurred_at'] }}</p>
                            </a>
                        @endforeach
                    </div>
                @else
                    <x-nino.empty-state
                        title="No issue activity yet"
                        description="Support timeline events will appear here as cases move through the queue."
                        icon="schedule" />
                @endif
            </x-nino.detail-section>
        </x-nino.dashboard-section>
    @endif

    @if(! empty($dashboard['shipping_workspace']))
        <x-nino.dashboard-section
            title="Shipping Workspace"
            subtitle="Operator-focused shipment queues for preparation, carrier handoff, delivery exceptions, and returns."
            grid-class="grid gap-6 xl:grid-cols-2">
            <x-nino.detail-section title="Ready to Ship Widget" subtitle="Shipments queued for preparation and carrier handoff.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['shipping_workspace']['ready_to_ship']['href'] }}" size="sm" variant="secondary">Open ready queue</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Ready to Ship</p>
                        <p class="metric-value">{{ number_format($dashboard['shipping_workspace']['ready_to_ship']['ready_count']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Packed</p>
                        <p class="metric-value">{{ number_format($dashboard['shipping_workspace']['ready_to_ship']['packed_count']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['shipping_workspace']['ready_to_ship']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-[rgba(120,112,95,0.14)] pt-4">
                        @foreach($dashboard['shipping_workspace']['ready_to_ship']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-mono text-xs text-[#61706B]">{{ $item['reference'] }}</p>
                                    <p class="mt-1 text-sm font-medium text-[#1E2B27]">{{ $item['tracking'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ $item['date'] }}</p>
                                </div>
                                <x-nino.status-badge :tone="$item['tone']" size="sm">{{ $item['status'] }}</x-nino.status-badge>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-4">
                        <x-nino.empty-state
                            title="No ready shipments"
                            description="Shipments waiting for packing or dispatch will appear here."
                            icon="local_shipping" />
                    </div>
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Shipped Widget" subtitle="Parcels that have already left the warehouse and are with the carrier.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['shipping_workspace']['shipped']['href'] }}" size="sm" variant="secondary">Open in transit</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Dispatched</p>
                        <p class="metric-value">{{ number_format($dashboard['shipping_workspace']['shipped']['dispatched_count']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">In Transit</p>
                        <p class="metric-value">{{ number_format($dashboard['shipping_workspace']['shipped']['in_transit_count']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['shipping_workspace']['shipped']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-[rgba(120,112,95,0.14)] pt-4">
                        @foreach($dashboard['shipping_workspace']['shipped']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-mono text-xs text-[#61706B]">{{ $item['reference'] }}</p>
                                    <p class="mt-1 text-sm font-medium text-[#1E2B27]">{{ $item['tracking'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ $item['date'] }}</p>
                                </div>
                                <x-nino.status-badge :tone="$item['tone']" size="sm">{{ $item['status'] }}</x-nino.status-badge>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-4">
                        <x-nino.empty-state
                            title="No shipped parcels"
                            description="Dispatched and in-transit parcels will appear here for active monitoring."
                            icon="route" />
                    </div>
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Failed Delivery Widget" subtitle="Delivery attempts that failed and still need follow-up.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['shipping_workspace']['failed_delivery']['href'] }}" size="sm" variant="secondary">Open failed deliveries</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Failed Deliveries</p>
                        <p class="metric-value">{{ number_format($dashboard['shipping_workspace']['failed_delivery']['failed_count']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Open Issues</p>
                        <p class="metric-value">{{ number_format($dashboard['shipping_workspace']['failed_delivery']['issue_count']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['shipping_workspace']['failed_delivery']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-[rgba(120,112,95,0.14)] pt-4">
                        @foreach($dashboard['shipping_workspace']['failed_delivery']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-mono text-xs text-[#61706B]">{{ $item['reference'] }}</p>
                                    <p class="mt-1 text-sm font-medium text-[#1E2B27]">{{ $item['tracking'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ $item['reason'] }}</p>
                                </div>
                                <p class="text-[11px] text-[#7A8681]">{{ $item['date'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-4">
                        <x-nino.empty-state
                            title="No failed deliveries"
                            description="Failed carrier drop-offs will show up here for quick recovery."
                            icon="warning" />
                    </div>
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Returned Parcel Queue Widget" subtitle="Returned shipments waiting for warehouse or customer-service handling.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['shipping_workspace']['returned_parcel_queue']['href'] }}" size="sm" variant="secondary">Open returns</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Returned</p>
                        <p class="metric-value">{{ number_format($dashboard['shipping_workspace']['returned_parcel_queue']['returned_count']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Returned Today</p>
                        <p class="metric-value">{{ number_format($dashboard['shipping_workspace']['returned_parcel_queue']['returned_today']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['shipping_workspace']['returned_parcel_queue']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-[rgba(120,112,95,0.14)] pt-4">
                        @foreach($dashboard['shipping_workspace']['returned_parcel_queue']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-mono text-xs text-[#61706B]">{{ $item['reference'] }}</p>
                                    <p class="mt-1 text-sm font-medium text-[#1E2B27]">{{ $item['tracking'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ $item['carrier'] }}</p>
                                </div>
                                <p class="text-[11px] text-[#7A8681]">{{ $item['date'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-4">
                        <x-nino.empty-state
                            title="No returned parcels"
                            description="Returned shipments will appear here for queue-based processing."
                            icon="assignment_return" />
                    </div>
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Tracking Queue Widget" subtitle="Shipments that still need tracking numbers or tracking follow-up before customer visibility is reliable.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['shipping_workspace']['tracking_queue']['href'] }}" size="sm" variant="secondary">Open tracking queue</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Missing Tracking</p>
                        <p class="metric-value">{{ number_format($dashboard['shipping_workspace']['tracking_queue']['missing_tracking_count']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Tracked Active</p>
                        <p class="metric-value">{{ number_format($dashboard['shipping_workspace']['tracking_queue']['tracked_active_count']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['shipping_workspace']['tracking_queue']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-[rgba(120,112,95,0.14)] pt-4">
                        @foreach($dashboard['shipping_workspace']['tracking_queue']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-mono text-xs text-[#61706B]">{{ $item['reference'] }}</p>
                                    <p class="mt-1 text-sm font-medium text-[#1E2B27]">{{ $item['carrier'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ $item['date'] }}</p>
                                </div>
                                <x-nino.status-badge :tone="$item['tone']" size="sm">{{ $item['status'] }}</x-nino.status-badge>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-4">
                        <x-nino.empty-state
                            title="Tracking queue is clear"
                            description="Shipments that still need tracking numbers will appear here automatically."
                            icon="pin_drop" />
                    </div>
                @endif
            </x-nino.detail-section>
        </x-nino.dashboard-section>
    @endif

    @if(! empty($dashboard['finance_workspace']))
        <x-nino.dashboard-section
            title="Finance Workspace"
            subtitle="Payment-state visibility for finance operators handling reconciliation, exceptions, and gateway performance."
            grid-class="grid gap-6 xl:grid-cols-2">
            <x-nino.detail-section title="Paid / Unpaid Summary Widget" subtitle="Completed payment volume versus unresolved payment exposure.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['finance_workspace']['paid_unpaid_summary']['href'] }}" size="sm" variant="secondary">Open transactions</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Paid Count</p>
                        <p class="metric-value">{{ number_format($dashboard['finance_workspace']['paid_unpaid_summary']['paid_count']) }}</p>
                        <p class="metric-subtitle">{{ $dashboard['finance_workspace']['paid_unpaid_summary']['paid_volume'] }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Unpaid Count</p>
                        <p class="metric-value">{{ number_format($dashboard['finance_workspace']['paid_unpaid_summary']['unpaid_count']) }}</p>
                        <p class="metric-subtitle">{{ $dashboard['finance_workspace']['paid_unpaid_summary']['unpaid_volume'] }}</p>
                    </div>
                </div>
            </x-nino.detail-section>

            <x-nino.detail-section title="Payment Method Summary Widget" subtitle="Payment-method mix by transaction count and processed amount.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['finance_workspace']['payment_method_summary']['href'] }}" size="sm" variant="secondary">Open finance</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['finance_workspace']['payment_method_summary']['items'])->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($dashboard['finance_workspace']['payment_method_summary']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-[#1E2B27]">{{ $item['method'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ $item['amount'] }}</p>
                                </div>
                                <span class="font-mono text-sm font-semibold text-[#1E2B27]">{{ number_format($item['count']) }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-nino.empty-state
                        title="No payment-method activity"
                        description="Payment method mix will appear here as transactions are processed."
                        icon="credit_card" />
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Gateway Transactions Summary Widget" subtitle="Gateway throughput and failure pressure across current payment traffic.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['finance_workspace']['gateway_transactions_summary']['href'] }}" size="sm" variant="secondary">Open finance</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['finance_workspace']['gateway_transactions_summary']['items'])->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($dashboard['finance_workspace']['gateway_transactions_summary']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-[#1E2B27]">{{ $item['gateway'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ $item['amount'] }} · {{ number_format($item['failed_count']) }} failed</p>
                                </div>
                                <span class="font-mono text-sm font-semibold text-[#1E2B27]">{{ number_format($item['count']) }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-nino.empty-state
                        title="No gateway traffic yet"
                        description="Gateway-level transaction performance will appear here as payments are processed."
                        icon="payments" />
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="COD Reconciliation Summary Widget" subtitle="Cash-on-delivery collection and reconciliation pressure.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['finance_workspace']['cod_reconciliation_summary']['href'] }}" size="sm" variant="secondary">Open COD</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Collected</p>
                        <p class="metric-value">{{ number_format($dashboard['finance_workspace']['cod_reconciliation_summary']['collected_count']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Reconciled</p>
                        <p class="metric-value">{{ number_format($dashboard['finance_workspace']['cod_reconciliation_summary']['reconciled_count']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Discrepancies</p>
                        <p class="metric-value">{{ number_format($dashboard['finance_workspace']['cod_reconciliation_summary']['discrepancy_count']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Outstanding</p>
                        <p class="metric-value text-base">{{ $dashboard['finance_workspace']['cod_reconciliation_summary']['outstanding_amount'] }}</p>
                    </div>
                </div>
            </x-nino.detail-section>

            <x-nino.detail-section title="Refund Summary Widget" subtitle="Queue visibility for requested and approved refunds.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['finance_workspace']['refund_summary']['href'] }}" size="sm" variant="secondary">Open refunds</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-3 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Requested</p>
                        <p class="metric-value">{{ number_format($dashboard['finance_workspace']['refund_summary']['requested_count']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Approved</p>
                        <p class="metric-value">{{ number_format($dashboard['finance_workspace']['refund_summary']['approved_count']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Queued Amount</p>
                        <p class="metric-value text-base">{{ $dashboard['finance_workspace']['refund_summary']['queued_amount'] }}</p>
                    </div>
                </div>
            </x-nino.detail-section>

            <x-nino.detail-section title="Discount / Fee Impact Widget" subtitle="Commercial impact from discounts and payment-processing fees.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['finance_workspace']['discount_fee_impact']['href'] }}" size="sm" variant="secondary">Open transactions</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-3 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Discounted Orders</p>
                        <p class="metric-value">{{ number_format($dashboard['finance_workspace']['discount_fee_impact']['discounted_orders']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Discounts</p>
                        <p class="metric-value text-base">{{ $dashboard['finance_workspace']['discount_fee_impact']['total_discounts'] }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Fees</p>
                        <p class="metric-value text-base">{{ $dashboard['finance_workspace']['discount_fee_impact']['total_fees'] }}</p>
                    </div>
                </div>
            </x-nino.detail-section>
        </x-nino.dashboard-section>
    @endif

    @if(! empty($dashboard['content_workspace']))
        <x-nino.dashboard-section
            title="Content Workspace"
            subtitle="Editorial queue visibility for content operators and SEO managers."
            grid-class="grid gap-6 xl:grid-cols-3">
            <x-nino.detail-section title="Drafts Widget" subtitle="Unpublished content waiting for editorial review or SEO completion.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['content_workspace']['drafts']['href'] }}" size="sm" variant="secondary">Open drafts</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Draft Posts</p>
                        <p class="metric-value">{{ number_format($dashboard['content_workspace']['drafts']['draft_count']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Scheduled Posts</p>
                        <p class="metric-value">{{ number_format($dashboard['content_workspace']['drafts']['scheduled_count']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['content_workspace']['drafts']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-[rgba(120,112,95,0.14)] pt-4">
                        @foreach($dashboard['content_workspace']['drafts']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-[#1E2B27]">{{ $item['title'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ $item['author'] }}</p>
                                </div>
                                <p class="text-[11px] text-[#7A8681]">{{ $item['updated_at'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-4">
                        <x-nino.empty-state
                            title="No drafts waiting"
                            description="Draft blog posts will appear here for editorial follow-up."
                            icon="edit_note" />
                    </div>
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Missing Metadata Widget" subtitle="Published content missing key metadata fields.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['content_workspace']['missing_metadata']['href'] }}" size="sm" variant="secondary">Open content</x-nino.button>
                </x-slot:header>

                <div class="metric-tile">
                    <p class="metric-label">Items Missing Metadata</p>
                    <p class="metric-value">{{ number_format($dashboard['content_workspace']['missing_metadata']['total_items']) }}</p>
                </div>

                @if(collect($dashboard['content_workspace']['missing_metadata']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-[rgba(120,112,95,0.14)] pt-4">
                        @foreach($dashboard['content_workspace']['missing_metadata']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-[#1E2B27]">{{ $item['title'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ $item['type'] }} · {{ $item['missing'] }}</p>
                                </div>
                                <p class="text-[11px] text-[#7A8681]">{{ $item['updated_at'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-4">
                        <x-nino.empty-state
                            title="Metadata is covered"
                            description="Missing meta titles and descriptions will surface here automatically."
                            icon="description" />
                    </div>
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="SEO Issue Summary Widget" subtitle="Highest-signal SEO risks across content and catalog surfaces.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['content_workspace']['seo_issue_summary']['href'] }}" size="sm" variant="secondary">Open SEO settings</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Missing Titles</p>
                        <p class="metric-value">{{ number_format($dashboard['content_workspace']['seo_issue_summary']['missing_meta_title']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Missing Descriptions</p>
                        <p class="metric-value">{{ number_format($dashboard['content_workspace']['seo_issue_summary']['missing_meta_description']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Missing Canonicals</p>
                        <p class="metric-value">{{ number_format($dashboard['content_workspace']['seo_issue_summary']['missing_canonical']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Published Noindex</p>
                        <p class="metric-value">{{ number_format($dashboard['content_workspace']['seo_issue_summary']['published_noindex']) }}</p>
                    </div>
                </div>
            </x-nino.detail-section>

            <x-nino.detail-section title="Scheduled Content Widget" subtitle="Upcoming scheduled posts and same-day publishing pressure.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['content_workspace']['scheduled_content']['href'] }}" size="sm" variant="secondary">Open scheduled</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Scheduled Posts</p>
                        <p class="metric-value">{{ number_format($dashboard['content_workspace']['scheduled_content']['scheduled_count']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Publishes Today</p>
                        <p class="metric-value">{{ number_format($dashboard['content_workspace']['scheduled_content']['publishes_today']) }}</p>
                    </div>
                </div>

                <div class="mt-4 rounded-[0.875rem] border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] px-4 py-3">
                    <p class="metric-label">Next Publish</p>
                    <p class="metric-value text-base">{{ $dashboard['content_workspace']['scheduled_content']['next_publish_at'] }}</p>
                </div>

                @if(collect($dashboard['content_workspace']['scheduled_content']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-[rgba(120,112,95,0.14)] pt-4">
                        @foreach($dashboard['content_workspace']['scheduled_content']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-[#1E2B27]">{{ $item['title'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ $item['author'] }}</p>
                                </div>
                                <p class="text-[11px] text-[#7A8681]">{{ $item['scheduled_for'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-nino.detail-section>
        </x-nino.dashboard-section>
    @endif

    @if(! empty($dashboard['media_workspace']))
        <x-nino.dashboard-section
            title="Media Buying Workspace"
            subtitle="Ad and tracking integrations visibility for marketing operators."
            grid-class="grid gap-6 xl:grid-cols-3">
            <x-nino.detail-section title="Integration Health Widget" subtitle="Analytics and pixel integrations currently configured in SEO settings.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['media_workspace']['integration_health']['href'] }}" size="sm" variant="secondary">Open integrations</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Configured</p>
                        <p class="metric-value">{{ number_format($dashboard['media_workspace']['integration_health']['configured_count']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Missing</p>
                        <p class="metric-value">{{ number_format($dashboard['media_workspace']['integration_health']['missing_count']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['media_workspace']['integration_health']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-[rgba(120,112,95,0.14)] pt-4">
                        @foreach($dashboard['media_workspace']['integration_health']['items'] as $item)
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-medium text-[#1E2B27]">{{ $item['name'] }}</p>
                                <x-nino.status-badge :tone="$item['tone']" size="sm">{{ $item['status'] }}</x-nino.status-badge>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Campaign Hooks Widget" subtitle="Coupon and recovery hooks currently available to paid campaigns.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['media_workspace']['campaign_hooks']['href'] }}" size="sm" variant="secondary">Open promotions</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Active Coupons</p>
                        <p class="metric-value">{{ number_format($dashboard['media_workspace']['campaign_hooks']['active_coupons']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Coupon Redemptions</p>
                        <p class="metric-value">{{ number_format($dashboard['media_workspace']['campaign_hooks']['coupon_redemptions']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Discount Value</p>
                        <p class="metric-value text-base">{{ $dashboard['media_workspace']['campaign_hooks']['discount_value'] }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Recoverable Carts</p>
                        <p class="metric-value">{{ number_format($dashboard['media_workspace']['campaign_hooks']['recoverable_carts']) }}</p>
                    </div>
                </div>

                <div class="mt-4 rounded-[0.875rem] border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] px-4 py-3">
                    <p class="metric-label">Recoverable Value</p>
                    <p class="metric-value text-base">{{ $dashboard['media_workspace']['campaign_hooks']['recoverable_value'] }}</p>
                </div>
            </x-nino.detail-section>

            <x-nino.detail-section title="Traffic / Campaign Placeholder Widget" subtitle="Reserved for analytics and paid-media ingestion once attribution is connected.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['media_workspace']['traffic_placeholder']['href'] }}" size="sm" variant="secondary">Open SEO settings</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Configured Sources</p>
                        <p class="metric-value">{{ number_format($dashboard['media_workspace']['traffic_placeholder']['configured_sources']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Awaiting Sources</p>
                        <p class="metric-value">{{ number_format($dashboard['media_workspace']['traffic_placeholder']['awaiting_sources']) }}</p>
                    </div>
                </div>

                <div class="mt-4">
                    <x-nino.inline-alert tone="info">
                        {{ $dashboard['media_workspace']['traffic_placeholder']['status'] }}
                    </x-nino.inline-alert>
                </div>
            </x-nino.detail-section>
        </x-nino.dashboard-section>
    @endif

    @if(! empty($dashboard['sales_workspace']))
        <x-nino.dashboard-section
            title="Sales Workspace"
            subtitle="Revenue pacing and order quality for sales operators."
            grid-class="grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(320px,0.85fr)]">
            <x-nino.chart-card
                title="Revenue Trend Widget"
                subtitle="Revenue trend across {{ strtolower($dashboard['range_label']) }}"
                type="line"
                :data="$dashboard['sales_workspace']['revenue_trend']['chart']"
                :options="[
                    'plugins' => ['legend' => ['display' => true]],
                    'scales' => [
                        'y' => ['title' => ['display' => true, 'text' => 'Revenue']],
                    ],
                ]"
                :height="260">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['sales_workspace']['revenue_trend']['href'] }}" size="sm" variant="secondary">Open sales</x-nino.button>
                </x-slot:header>
            </x-nino.chart-card>

            <x-nino.detail-section title="AOV Widget" subtitle="Average order value across the active dashboard window.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['sales_workspace']['aov']['href'] }}" size="sm" variant="secondary">Open sales</x-nino.button>
                </x-slot:header>

                <div class="space-y-3">
                    <div class="metric-tile">
                        <p class="metric-label">Average Order Value</p>
                        <p class="metric-value">{{ $dashboard['sales_workspace']['aov']['value'] }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Orders in Window</p>
                        <p class="metric-value">{{ number_format($dashboard['sales_workspace']['aov']['orders']) }}</p>
                    </div>
                    <div class="rounded-[0.875rem] border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] px-4 py-3">
                        <p class="metric-label">Trend</p>
                        <p class="metric-subtitle">{{ $dashboard['sales_workspace']['aov']['change'] }}</p>
                    </div>
                </div>
            </x-nino.detail-section>

            <x-nino.detail-section title="Best Sellers Widget" subtitle="Top products by revenue in the active dashboard window.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['sales_workspace']['best_sellers']['href'] }}" size="sm" variant="secondary">Open sales</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['sales_workspace']['best_sellers']['items'])->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($dashboard['sales_workspace']['best_sellers']['items'] as $item)
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-[#1E2B27]">{{ $item['label'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ number_format($item['units']) }} units</p>
                                </div>
                                <p class="font-mono text-sm font-semibold text-[#1E2B27]">{{ number_format((float) $item['revenue'], 2) }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-nino.empty-state
                        title="No best sellers yet"
                        description="Best-selling products will appear here once orders are flowing."
                        icon="sell" />
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Promo Performance Widget" subtitle="Coupon redemptions and recovery opportunity across the active window.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['sales_workspace']['promo_performance']['href'] }}" size="sm" variant="secondary">Open promotions</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Active Coupons</p>
                        <p class="metric-value">{{ number_format($dashboard['sales_workspace']['promo_performance']['active_coupons']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Redemptions</p>
                        <p class="metric-value">{{ number_format($dashboard['sales_workspace']['promo_performance']['redemptions']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Discount Value</p>
                        <p class="metric-value text-base">{{ $dashboard['sales_workspace']['promo_performance']['discount_value'] }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Recoverable Carts</p>
                        <p class="metric-value">{{ number_format($dashboard['sales_workspace']['promo_performance']['recoverable_carts']) }}</p>
                    </div>
                </div>

                <div class="mt-4 rounded-[0.875rem] border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] px-4 py-3">
                    <p class="metric-label">Recoverable Value</p>
                    <p class="metric-value text-base">{{ $dashboard['sales_workspace']['promo_performance']['recoverable_value'] }}</p>
                </div>
            </x-nino.detail-section>
        </x-nino.dashboard-section>
    @endif

    @if(! empty($dashboard['stock_workspace']))
        <x-nino.dashboard-section
            title="Stock Workspace"
            subtitle="Inventory pressure and damage monitoring for stock operators."
            grid-class="grid gap-6 xl:grid-cols-2">
            <x-nino.detail-section title="Low Stock Widget" subtitle="Items currently at low-stock or out-of-stock thresholds.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['stock_workspace']['low_stock']['href'] }}" size="sm" variant="secondary">Open inventory</x-nino.button>
                </x-slot:header>

                <div class="metric-tile">
                    <p class="metric-label">Stock Alerts</p>
                    <p class="metric-value">{{ number_format($dashboard['stock_workspace']['low_stock']['count']) }}</p>
                </div>

                @if(collect($dashboard['stock_workspace']['low_stock']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-[rgba(120,112,95,0.14)] pt-4">
                        @foreach($dashboard['stock_workspace']['low_stock']['items'] as $item)
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-[#1E2B27]">{{ $item['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ $item['sku'] }} · {{ $item['branch'] }}</p>
                                </div>
                                <x-nino.status-badge :tone="$item['tone']" size="sm">{{ $item['available'] }} available</x-nino.status-badge>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Damaged Stock Widget" subtitle="Recent damaged-item deductions recorded in inventory movements.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['stock_workspace']['damaged_stock']['href'] }}" size="sm" variant="secondary">Open adjustments</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Damage Events</p>
                        <p class="metric-value">{{ number_format($dashboard['stock_workspace']['damaged_stock']['damaged_events']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Damaged Units</p>
                        <p class="metric-value">{{ number_format($dashboard['stock_workspace']['damaged_stock']['damaged_units']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['stock_workspace']['damaged_stock']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-[rgba(120,112,95,0.14)] pt-4">
                        @foreach($dashboard['stock_workspace']['damaged_stock']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-[#1E2B27]">{{ $item['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ $item['sku'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-mono text-sm font-semibold text-[#1E2B27]">{{ number_format($item['units']) }} units</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ $item['date'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Adjustment Summary Widget" subtitle="Manual inventory overrides recorded during the active dashboard window.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['stock_workspace']['adjustment_summary']['href'] }}" size="sm" variant="secondary">Open adjustments</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Adjustment Events</p>
                        <p class="metric-value">{{ number_format($dashboard['stock_workspace']['adjustment_summary']['adjustment_events']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Adjusted Units</p>
                        <p class="metric-value">{{ number_format($dashboard['stock_workspace']['adjustment_summary']['adjusted_units']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['stock_workspace']['adjustment_summary']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-[rgba(120,112,95,0.14)] pt-4">
                        @foreach($dashboard['stock_workspace']['adjustment_summary']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-[#1E2B27]">{{ $item['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ $item['sku'] }} · {{ $item['direction'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-mono text-sm font-semibold text-[#1E2B27]">{{ number_format($item['units']) }} units</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ $item['date'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Transfer-Ready Metrics Widget" subtitle="Stock transfers currently pending or in flight across the network.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['stock_workspace']['transfer_ready_metrics']['href'] }}" size="sm" variant="secondary">Open inventory</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-3 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Pending</p>
                        <p class="metric-value">{{ number_format($dashboard['stock_workspace']['transfer_ready_metrics']['pending_transfers']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Shipped</p>
                        <p class="metric-value">{{ number_format($dashboard['stock_workspace']['transfer_ready_metrics']['shipped_transfers']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">In-Flight Units</p>
                        <p class="metric-value">{{ number_format($dashboard['stock_workspace']['transfer_ready_metrics']['in_flight_units']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['stock_workspace']['transfer_ready_metrics']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-[rgba(120,112,95,0.14)] pt-4">
                        @foreach($dashboard['stock_workspace']['transfer_ready_metrics']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-[#1E2B27]">{{ $item['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ $item['sku'] }} · {{ $item['status'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-mono text-sm font-semibold text-[#1E2B27]">{{ number_format($item['units']) }} units</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">{{ $item['date'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-nino.detail-section>
        </x-nino.dashboard-section>
    @endif

    @if(! empty($dashboard['moderator_workspace']))
        <x-nino.dashboard-section
            title="Community Moderator Workspace"
            subtitle="Schema-ready moderation queue visibility while community admin surfaces are still pending."
            grid-class="grid gap-6 xl:grid-cols-1">
            <x-nino.detail-section title="Report Queue Placeholder Widget" subtitle="Open moderation load and current report state counts.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['moderator_workspace']['report_queue']['href'] }}" size="sm" variant="secondary">Open dashboard</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-3 gap-3">
                    <div class="metric-tile">
                        <p class="metric-label">Open Queue</p>
                        <p class="metric-value">{{ number_format($dashboard['moderator_workspace']['report_queue']['open_queue']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Assigned</p>
                        <p class="metric-value">{{ number_format($dashboard['moderator_workspace']['report_queue']['assigned_queue']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">In Review</p>
                        <p class="metric-value">{{ number_format($dashboard['moderator_workspace']['report_queue']['in_review_queue']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Open Reports</p>
                        <p class="metric-value">{{ number_format($dashboard['moderator_workspace']['report_queue']['open_reports']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Action Taken</p>
                        <p class="metric-value">{{ number_format($dashboard['moderator_workspace']['report_queue']['action_taken']) }}</p>
                    </div>
                    <div class="metric-tile">
                        <p class="metric-label">Dismissed</p>
                        <p class="metric-value">{{ number_format($dashboard['moderator_workspace']['report_queue']['dismissed_reports']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['moderator_workspace']['report_queue']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-[rgba(120,112,95,0.14)] pt-4">
                        @foreach($dashboard['moderator_workspace']['report_queue']['items'] as $item)
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-[#1E2B27]">{{ $item['status'] }}</p>
                                    <p class="mt-1 text-[11px] text-[#7A8681]">Priority {{ number_format($item['priority']) }}</p>
                                </div>
                                <p class="text-[11px] text-[#7A8681]">{{ $item['queued_at'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-4">
                        <x-nino.empty-state
                            title="No moderation queue yet"
                            description="This placeholder will surface live community moderation work once reports begin flowing."
                            icon="gavel" />
                    </div>
                @endif
            </x-nino.detail-section>
        </x-nino.dashboard-section>
    @endif

    <x-nino.dashboard-section
        title="Live Operations"
        subtitle="Widget-based queue views for commerce and fulfillment teams."
        grid-class="grid gap-6 xl:grid-cols-[minmax(0,1.65fr)_minmax(320px,0.95fr)]">
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
    </x-nino.dashboard-section>

    @if(collect($dashboard['queue_cards'])->isNotEmpty() || collect($dashboard['recent_tickets'])->isNotEmpty())
        <x-nino.dashboard-section
            title="Action Queues"
            subtitle="Shared queue widgets that later role-specific dashboards can remix."
            grid-class="grid gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)]">
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
        </x-nino.dashboard-section>
    @endif
</x-nino.dashboard-layout>
@endsection
