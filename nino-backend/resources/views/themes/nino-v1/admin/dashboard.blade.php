@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('header', 'Welcome back, ' . explode(' ', auth()->user()->name)[0])
@section('subheader', 'Operational overview for ' . strtolower($dashboard['range_label']) . '.')

@section('content')
@php
    $topProductChart = [
        'labels' => collect($dashboard['top_products'])->pluck('label')->all(),
        'datasets' => [[
            'label' => 'Revenue',
            'data' => collect($dashboard['top_products'])->pluck('revenue')->map(fn ($value) => round((float) $value, 2))->all(),
            'backgroundColor' => '#4F46E5',
            'borderRadius' => 6,
            'borderSkipped' => false,
            'barThickness' => 14,
        ]],
    ];

    $revenueChartConfig = [
        'type' => 'line',
        'data' => $dashboard['revenue_chart'],
        'options' => [
            'plugins' => ['legend' => ['display' => true]],
            'scales' => [
                'y' => ['title' => ['display' => true, 'text' => 'Revenue']],
                'y1' => ['title' => ['display' => true, 'text' => 'Orders']],
            ],
        ],
    ];

    $topProductChartConfig = [
        'type' => 'bar',
        'data' => $topProductChart,
        'options' => [
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]],
        ],
    ];
@endphp

<x-nino.dashboard-layout>
    <div class="filter-toolbar">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="filter-label">Dashboard window</p>
            <div class="inline-flex flex-wrap items-center gap-2">
                @foreach($dashboard['date_presets'] as $preset)
                    <a
                        href="{{ route('admin.dashboard', ['preset' => $preset['key']]) }}"
                        class="{{ $dashboard['active_preset'] === $preset['key'] ? 'btn-primary' : 'btn-secondary' }} !px-3 !py-2 !text-xs"
                    >
                        {{ $preset['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    @if($dashboard['is_super_admin'])
        <x-nino.dashboard-section
            title="Executive Pulse"
            subtitle="Super Admin widgets focused on topline commerce health and payment risk."
            grid-class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(320px,1fr)]">
            <x-nino.card title="Revenue Trend" subtitle="GMV trend across {{ strtolower($dashboard['range_label']) }}">
                <div class="relative" data-chart-shell data-chart-state="loading">
                    <div class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-slate-300 bg-slate-50/95" data-chart-loading>
                        <span class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Loading chart</span>
                        <div class="flex items-end gap-2" aria-hidden="true">
                            <span class="block h-6 w-2 rounded-full bg-indigo-200"></span>
                            <span class="block h-10 w-2 rounded-full bg-indigo-300"></span>
                            <span class="block h-5 w-2 rounded-full bg-indigo-200"></span>
                        </div>
                    </div>
                    <canvas
                        id="nino-v1-dashboard-executive-revenue-chart"
                        data-chart-height="280"
                        data-chart-config='@json($revenueChartConfig)'></canvas>
                </div>
            </x-nino.card>

            <div class="grid gap-4 sm:grid-cols-3 xl:grid-cols-1">
                @foreach($dashboard['super_admin_widgets'] as $widget)
                    <x-nino.card :title="$widget['title']">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-3xl font-black tracking-tight text-slate-900">{{ $widget['value'] }}</p>
                                <p class="mt-2 text-sm text-slate-500">{{ $widget['subtitle'] }}</p>
                                <p class="mt-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">{{ $widget['change'] }}</p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-md border border-slate-200 bg-slate-50 text-indigo-600">
                                <span class="material-symbols-outlined text-[1.4rem]">{{ $widget['icon'] }}</span>
                            </div>
                        </div>
                    </x-nino.card>
                @endforeach
            </div>
        </x-nino.dashboard-section>

        <x-nino.dashboard-section
            title="Network Health"
            subtitle="Operational widgets covering stock pressure, shipping risk, and network coverage."
            grid-class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-nino.card title="Low Stock Alerts" subtitle="Items closest to breaching inventory thresholds.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['super_admin_health']['low_stock_alerts']['href'] }}" size="sm" variant="secondary">Open inventory</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['super_admin_health']['low_stock_alerts']['items'])->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($dashboard['super_admin_health']['low_stock_alerts']['items'] as $item)
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $item['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500"><span class="font-mono">{{ $item['sku'] }}</span> · {{ $item['branch'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-slate-900">{{ $item['available'] }} available</p>
                                    <p class="mt-1 text-[11px] text-slate-500">Threshold {{ $item['threshold'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">Inventory thresholds are currently healthy across all tracked branches.</div>
                @endif
            </x-nino.card>

            <x-nino.card title="Shipping Exceptions" subtitle="Failed deliveries, returns, and flagged shipment issues.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['super_admin_health']['shipping_exceptions']['href'] }}" size="sm" variant="secondary">Open shipments</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['super_admin_health']['shipping_exceptions']['items'])->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($dashboard['super_admin_health']['shipping_exceptions']['items'] as $shipment)
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-mono text-xs text-slate-500">{{ $shipment['reference'] }}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $shipment['tracking'] }}</p>
                                </div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">{{ $shipment['status'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">Delivery issues, failed drops, and returns will surface here automatically.</div>
                @endif
            </x-nino.card>

            <x-nino.card title="Franchise Performance Summary" subtitle="Coverage and stock-health snapshot by franchise network.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['super_admin_health']['franchise_performance']['href'] }}" size="sm" variant="secondary">Open branches</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['super_admin_health']['franchise_performance']['items'])->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($dashboard['super_admin_health']['franchise_performance']['items'] as $franchise)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $franchise['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ number_format($franchise['branches']) }} branches · {{ number_format($franchise['staff']) }} staff</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-slate-900">{{ number_format($franchise['stock_alerts']) }} alerts</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ number_format($franchise['available_units']) }} available units</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">Franchise-level operational summaries will appear once the network is seeded.</div>
                @endif
            </x-nino.card>

            <x-nino.card title="Branch Performance Summary" subtitle="Branch-level stock pressure and staffing coverage.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['super_admin_health']['branch_performance']['href'] }}" size="sm" variant="secondary">Review branches</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['super_admin_health']['branch_performance']['items'])->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($dashboard['super_admin_health']['branch_performance']['items'] as $branch)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $branch['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $branch['franchise'] }} · {{ number_format($branch['staff']) }} staff · {{ number_format($branch['tracked_skus']) }} SKUs</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-slate-900">{{ number_format($branch['stock_alerts']) }} alerts</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ number_format($branch['available_units']) }} available units</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">Branch-level performance widgets will populate once branch organizations are active.</div>
                @endif
            </x-nino.card>
        </x-nino.dashboard-section>

        <x-nino.dashboard-section
            title="Command Center"
            subtitle="Super Admin widgets for support backlog, finance exposure, and system-health triage."
            grid-class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <x-nino.card title="Support KPI Widget" subtitle="Escalation pressure and unresolved support load.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['super_admin_command']['support_kpi']['href'] }}" size="sm" variant="secondary">Open issues</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Open</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['super_admin_command']['support_kpi']['open']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Urgent</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['super_admin_command']['support_kpi']['urgent']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Unassigned</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['super_admin_command']['support_kpi']['unassigned']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['super_admin_command']['support_kpi']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                        @foreach($dashboard['super_admin_command']['support_kpi']['items'] as $issue)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-mono text-xs text-slate-500">{{ $issue['reference'] }}</p>
                                    <p class="mt-1 truncate text-sm font-semibold text-slate-900">{{ $issue['subject'] }}</p>
                                </div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">{{ $issue['priority'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-nino.card>

            <x-nino.card title="Finance Summary Widget" subtitle="Captured revenue and queues needing finance review.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['super_admin_command']['finance_summary']['href'] }}" size="sm" variant="secondary">Open finance</x-nino.button>
                </x-slot:header>

                <div class="space-y-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Captured Revenue</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ $dashboard['super_admin_command']['finance_summary']['captured'] }}</p>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-md border border-slate-200 bg-slate-50 p-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Payment Review</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $dashboard['super_admin_command']['finance_summary']['payment_review_amount'] }}</p>
                        </div>
                        <p class="text-sm font-semibold text-slate-900">{{ number_format($dashboard['super_admin_command']['finance_summary']['payment_review_count']) }} queued</p>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-md border border-slate-200 bg-slate-50 p-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Refund Queue</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $dashboard['super_admin_command']['finance_summary']['refund_queue_amount'] }}</p>
                        </div>
                        <p class="text-sm font-semibold text-slate-900">{{ number_format($dashboard['super_admin_command']['finance_summary']['refund_queue_count']) }} queued</p>
                    </div>
                </div>
            </x-nino.card>

            <x-nino.card title="System Alerts Widget" subtitle="Latest critical, error, and warning incidents from observability.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['super_admin_command']['system_alerts']['href'] }}" size="sm" variant="secondary">Open observability</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Critical</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['super_admin_command']['system_alerts']['critical']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Warnings</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['super_admin_command']['system_alerts']['warning']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['super_admin_command']['system_alerts']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                        @foreach($dashboard['super_admin_command']['system_alerts']['items'] as $event)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $event['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $event['source'] }} · {{ $event['occurred_at'] }}</p>
                                </div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">{{ $event['severity'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state mt-4">Critical and warning observability incidents will surface here automatically.</div>
                @endif
            </x-nino.card>
        </x-nino.dashboard-section>
    @endif

    @if(! empty($dashboard['primary_metrics']))
        <x-nino.dashboard-section
            title="Platform Overview"
            subtitle="Core business metrics for the selected reporting window."
            grid-class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($dashboard['primary_metrics'] as $metric)
                <x-nino.metric-card
                    :title="$metric['title']"
                    :value="$metric['value']"
                    :icon="$metric['icon']"
                    :subtitle="$metric['subtitle']"
                    :color="$metric['tone']" />
            @endforeach
        </x-nino.dashboard-section>
    @endif

    <x-nino.dashboard-section
        :title="$dashboard['is_super_admin'] ? 'Merchandise Mix' : 'Performance Widgets'"
        :subtitle="$dashboard['is_super_admin']
            ? 'Merchandising and status widgets supporting the executive view.'
            : 'Dashboard foundations shared across themes: layout patterns, reusable sections, loading states, and presets.'"
        :grid-class="$dashboard['is_super_admin']
            ? 'grid grid-cols-1 gap-6 xl:grid-cols-2'
            : 'grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.55fr)_minmax(320px,1fr)]'">
        @unless($dashboard['is_super_admin'])
            <x-nino.card title="Revenue and Order Trend">
                <div class="relative" data-chart-shell data-chart-state="loading">
                    <div class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-slate-300 bg-slate-50/95" data-chart-loading>
                        <span class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Loading chart</span>
                        <div class="flex items-end gap-2" aria-hidden="true">
                            <span class="block h-6 w-2 rounded-full bg-indigo-200"></span>
                            <span class="block h-10 w-2 rounded-full bg-indigo-300"></span>
                            <span class="block h-5 w-2 rounded-full bg-indigo-200"></span>
                        </div>
                    </div>
                    <canvas
                        id="nino-v1-dashboard-revenue-chart"
                        data-chart-height="280"
                        data-chart-config='@json($revenueChartConfig)'></canvas>
                </div>
            </x-nino.card>
        @endunless

        <div class="{{ $dashboard['is_super_admin'] ? '' : 'space-y-6' }}">
            <x-nino.card title="Top Products" subtitle="Revenue contribution by product">
                @if(collect($dashboard['top_products'])->isNotEmpty())
                    <div class="relative" data-chart-shell data-chart-state="loading">
                        <div class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-slate-300 bg-slate-50/95" data-chart-loading>
                            <span class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Loading chart</span>
                            <div class="flex items-end gap-2" aria-hidden="true">
                                <span class="block h-6 w-2 rounded-full bg-indigo-200"></span>
                                <span class="block h-10 w-2 rounded-full bg-indigo-300"></span>
                                <span class="block h-5 w-2 rounded-full bg-indigo-200"></span>
                            </div>
                        </div>
                        <canvas
                            id="nino-v1-dashboard-top-products-chart"
                            data-chart-height="280"
                            data-chart-config='@json($topProductChartConfig)'></canvas>
                    </div>
                @else
                    <div class="empty-state">
                        No product revenue yet for the selected window.
                    </div>
                @endif
            </x-nino.card>

            <x-nino.card title="Order Mix" subtitle="Status distribution across recent orders." class="{{ $dashboard['is_super_admin'] ? 'mt-6' : '' }}">
                @if(collect($dashboard['status_board'])->sum('count') > 0)
                    <div class="space-y-3">
                        @foreach($dashboard['status_board'] as $status)
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">{{ $status['label'] }}</span>
                                <span class="font-mono text-sm font-semibold text-slate-900">{{ number_format($status['count']) }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        No orders have entered this reporting window yet.
                    </div>
                @endif
            </x-nino.card>
        </div>
    </x-nino.dashboard-section>

    @if(collect($dashboard['platform_action_queue'])->isNotEmpty())
        <x-nino.dashboard-section
            title="Platform Action Queue"
            subtitle="Cross-team operational priorities that Platform Admins can dispatch immediately."
            grid-class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach($dashboard['platform_action_queue'] as $item)
                <x-nino.card :title="$item['title']">
                    <div class="space-y-3">
                        <p class="text-3xl font-black tracking-tight text-slate-900">{{ number_format($item['value']) }}</p>
                        <p class="text-sm text-slate-500">{{ $item['meta'] }}</p>
                        <x-nino.button href="{{ $item['href'] }}" size="sm" variant="secondary">{{ $item['cta'] }}</x-nino.button>
                    </div>
                </x-nino.card>
            @endforeach
        </x-nino.dashboard-section>
    @endif

    @if(! empty($dashboard['platform_workspace']))
        <x-nino.dashboard-section
            title="Platform Admin Workspace"
            subtitle="Shared operational widgets for orders, inventory, finance, and content oversight."
            grid-class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-nino.card title="Recent Orders Widget" subtitle="Fresh commerce activity for platform-level monitoring." noPadding>
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['platform_workspace']['recent_orders']['href'] }}" size="sm" variant="secondary">Open orders</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['platform_workspace']['recent_orders']['items'])->isNotEmpty())
                    <div class="divide-y divide-slate-100">
                        @foreach($dashboard['platform_workspace']['recent_orders']['items'] as $order)
                            <a href="{{ $order['url'] }}" wire:navigate class="flex items-center justify-between gap-4 px-5 py-4 transition-colors hover:bg-slate-50">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-3">
                                        <span class="font-mono text-xs text-slate-500">{{ $order['reference'] }}</span>
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $order['customer'] }}</p>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500">{{ $order['date'] }}</p>
                                </div>
                                <span class="font-mono text-sm font-semibold text-slate-900">{{ $order['total'] }}</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="p-5">
                        <div class="empty-state">New orders will appear here as soon as commerce activity starts.</div>
                    </div>
                @endif
            </x-nino.card>

            <x-nino.card title="Stock Alerts Widget" subtitle="Current stock pressure across tracked inventory." noPadding>
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['platform_workspace']['stock_alerts']['href'] }}" size="sm" variant="secondary">Open inventory</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['platform_workspace']['stock_alerts']['items'])->isNotEmpty())
                    <div class="divide-y divide-slate-100">
                        @foreach($dashboard['platform_workspace']['stock_alerts']['items'] as $item)
                            <div class="flex items-center justify-between gap-4 px-5 py-4">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $item['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500"><span class="font-mono">{{ $item['sku'] }}</span> · {{ $item['branch'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-slate-900">{{ $item['available'] }} available</p>
                                    <p class="mt-1 text-[11px] text-slate-500">Threshold {{ $item['threshold'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-5">
                        <div class="empty-state">Low-stock and out-of-stock items will surface here automatically.</div>
                    </div>
                @endif
            </x-nino.card>

            <x-nino.card title="Payment Exceptions Widget" subtitle="Pending and failed payment items that need review." noPadding>
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['platform_workspace']['payment_exceptions']['href'] }}" size="sm" variant="secondary">Open finance</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['platform_workspace']['payment_exceptions']['items'])->isNotEmpty())
                    <div class="divide-y divide-slate-100">
                        @foreach($dashboard['platform_workspace']['payment_exceptions']['items'] as $transaction)
                            <div class="flex items-center justify-between gap-4 px-5 py-4">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-3">
                                        <span class="font-mono text-xs text-slate-500">{{ $transaction['reference'] }}</span>
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $transaction['order_reference'] }}</p>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500">{{ $transaction['amount'] }}</p>
                                </div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">{{ $transaction['status'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-5">
                        <div class="empty-state">Pending and failed transactions will show up here for quick triage.</div>
                    </div>
                @endif
            </x-nino.card>

            <x-nino.card title="Content/Promo Highlights Widget" subtitle="Editorial and promotions signals for platform operators.">
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Draft Posts</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['platform_workspace']['content_promo_highlights']['draft_posts']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Scheduled Posts</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['platform_workspace']['content_promo_highlights']['scheduled_posts']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Active Coupons</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['platform_workspace']['content_promo_highlights']['active_coupons']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Recoverable Carts</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['platform_workspace']['content_promo_highlights']['recoverable_carts']) }}</p>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <x-nino.button href="{{ $dashboard['platform_workspace']['content_promo_highlights']['content_href'] }}" size="sm" variant="secondary">Open content</x-nino.button>
                    <x-nino.button href="{{ $dashboard['platform_workspace']['content_promo_highlights']['promo_href'] }}" size="sm" variant="secondary">Open promotions</x-nino.button>
                </div>
            </x-nino.card>
        </x-nino.dashboard-section>
    @endif

    @if(! empty($dashboard['franchise_workspace']))
        <x-nino.dashboard-section
            title="Franchise Workspace"
            subtitle="Franchise-level branch widgets built from current branch-scoped inventory, staffing, and activity data."
            grid-class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-nino.card title="Sales by Branch Widget" subtitle="Operational demand proxy by branch using recent inventory movement because direct branch revenue attribution is not modeled yet.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['franchise_workspace']['sales_by_branch']['href'] }}" size="sm" variant="secondary">Open branches</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['franchise_workspace']['sales_by_branch']['items'])->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($dashboard['franchise_workspace']['sales_by_branch']['items'] as $branch)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $branch['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $branch['franchise'] }} · {{ number_format($branch['active_staff']) }} staff · {{ number_format($branch['available_units']) }} available units</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-mono text-sm font-semibold text-slate-900">{{ number_format($branch['activity_count']) }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ number_format($branch['outbound_units']) }} outbound units</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">Branch demand proxies will appear once inventory movement starts across the franchise tree.</div>
                @endif
            </x-nino.card>

            <x-nino.card title="Branch Stock Overview Widget" subtitle="Stock pressure, SKU coverage, and available units by branch.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['franchise_workspace']['branch_stock_overview']['href'] }}" size="sm" variant="secondary">Open inventory</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['franchise_workspace']['branch_stock_overview']['items'])->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($dashboard['franchise_workspace']['branch_stock_overview']['items'] as $branch)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $branch['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ number_format($branch['tracked_skus']) }} tracked SKUs</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-slate-900">{{ number_format($branch['stock_alerts']) }} alerts</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ number_format($branch['available_units']) }} available units</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">Branch stock coverage will surface here once inventory is assigned across franchise branches.</div>
                @endif
            </x-nino.card>

            <x-nino.card title="Staff Performance Summary Widget" subtitle="Active staffing and recent operator activity by branch.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['franchise_workspace']['staff_performance_summary']['href'] }}" size="sm" variant="secondary">Open staff</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['franchise_workspace']['staff_performance_summary']['items'])->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($dashboard['franchise_workspace']['staff_performance_summary']['items'] as $branch)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $branch['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ number_format($branch['active_staff']) }} active staff</p>
                                </div>
                                <p class="text-sm font-semibold text-slate-900">{{ number_format($branch['recently_active_staff']) }} active in 7d</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">Recent staff activity will appear here once franchise staff start using the admin.</div>
                @endif
            </x-nino.card>

            <x-nino.card title="Branch Order Metrics Widget" subtitle="Branch order-pressure proxy using reserved stock and recent movement because orders are not directly attached to branches yet.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['franchise_workspace']['branch_order_metrics']['href'] }}" size="sm" variant="secondary">Review branches</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['franchise_workspace']['branch_order_metrics']['items'])->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($dashboard['franchise_workspace']['branch_order_metrics']['items'] as $branch)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $branch['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ number_format($branch['recent_movements']) }} recent movements · {{ number_format($branch['stock_alerts']) }} stock alerts</p>
                                </div>
                                <p class="text-sm font-semibold text-slate-900">{{ number_format($branch['reserved_units']) }} reserved units</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">Reserved stock and movement-based branch order signals will appear here as branches become operational.</div>
                @endif
            </x-nino.card>
        </x-nino.dashboard-section>
    @endif

    @if(! empty($dashboard['branch_workspace']))
        <x-nino.dashboard-section
            title="Branch Workspace"
            subtitle="Branch-level operations for {{ $dashboard['branch_workspace']['branch_name'] }} within {{ $dashboard['branch_workspace']['franchise_name'] }}."
            grid-class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-nino.card title="Orders to Prepare Widget" subtitle="Reserved-stock preparation queue for this branch.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['branch_workspace']['orders_to_prepare']['href'] }}" size="sm" variant="secondary">Open branch</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Reserved Units</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['branch_workspace']['orders_to_prepare']['reserved_units']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Reservation Events</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['branch_workspace']['orders_to_prepare']['reservation_events']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['branch_workspace']['orders_to_prepare']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                        @foreach($dashboard['branch_workspace']['orders_to_prepare']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $item['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500"><span class="font-mono">{{ $item['sku'] }}</span> · {{ $item['date'] }}</p>
                                </div>
                                <p class="max-w-[12rem] text-right text-[11px] text-slate-500">{{ $item['notes'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-nino.card>

            <x-nino.card title="Branch Stock Widgets" subtitle="Tracked SKUs, available units, and low-stock blockers for the branch.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['branch_workspace']['stock_widgets']['href'] }}" size="sm" variant="secondary">Open inventory</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Tracked SKUs</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['branch_workspace']['stock_widgets']['tracked_skus']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Available Units</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['branch_workspace']['stock_widgets']['available_units']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Stock Alerts</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['branch_workspace']['stock_widgets']['stock_alerts']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['branch_workspace']['stock_widgets']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                        @foreach($dashboard['branch_workspace']['stock_widgets']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $item['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500 font-mono">{{ $item['sku'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-slate-900">{{ $item['available'] }} available</p>
                                    <p class="mt-1 text-[11px] text-slate-500">Threshold {{ $item['threshold'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-nino.card>

            <x-nino.card title="Dispatch Queue Widget" subtitle="Outbound branch movement proxy for dispatch pressure because shipments are not branch-owned in the schema yet.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['branch_workspace']['dispatch_queue']['href'] }}" size="sm" variant="secondary">Open movements</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Outbound Events</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['branch_workspace']['dispatch_queue']['outbound_events']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Outbound Units</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['branch_workspace']['dispatch_queue']['outbound_units']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['branch_workspace']['dispatch_queue']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                        @foreach($dashboard['branch_workspace']['dispatch_queue']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $item['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500"><span class="font-mono">{{ $item['sku'] }}</span> · {{ $item['date'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-slate-900">{{ number_format($item['units']) }} units</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $item['reason'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-nino.card>

            <x-nino.card title="Branch Issue Queue Widget" subtitle="Recent branch blockers from stock incidents and adjustment-driven issues.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['branch_workspace']['issue_queue']['href'] }}" size="sm" variant="secondary">Review branch</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Issue Events</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['branch_workspace']['issue_queue']['issue_events']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Stock Alerts</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['branch_workspace']['issue_queue']['stock_alerts']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['branch_workspace']['issue_queue']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                        @foreach($dashboard['branch_workspace']['issue_queue']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $item['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500"><span class="font-mono">{{ $item['sku'] }}</span> · {{ $item['date'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-slate-900">{{ number_format($item['units']) }} units</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $item['reason'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-nino.card>
        </x-nino.dashboard-section>
    @endif

    @if(! empty($dashboard['support_workspace']))
        <x-nino.dashboard-section
            title="Support Workspace"
            subtitle="Fast support tools for order lookup, customer lookup, open-case handling, and recent issue activity."
            grid-class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-nino.card title="Order Lookup Widget" subtitle="Quick access to recent orders and preparation-state lookups.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['support_workspace']['order_lookup']['href'] }}" size="sm" variant="secondary">Open support lookup</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Searchable Orders</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['support_workspace']['order_lookup']['searchable_orders']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Preparing Orders</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['support_workspace']['order_lookup']['preparing_orders']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['support_workspace']['order_lookup']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                        @foreach($dashboard['support_workspace']['order_lookup']['items'] as $item)
                            <a href="{{ $item['href'] }}" class="flex items-start justify-between gap-3 transition-opacity hover:opacity-80">
                                <div class="min-w-0">
                                    <p class="font-mono text-xs text-slate-500">{{ $item['reference'] }}</p>
                                    <p class="mt-1 truncate text-sm font-semibold text-slate-900">{{ $item['customer'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $item['meta'] }}</p>
                                </div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">{{ $item['status'] }}</p>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state mt-4">Recent order references will appear here for fast support lookup.</div>
                @endif
            </x-nino.card>

            <x-nino.card title="Customer Lookup Widget" subtitle="Recent customer identities ready for support search and follow-up.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['support_workspace']['customer_lookup']['href'] }}" size="sm" variant="secondary">Lookup customer</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Active Customers</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['support_workspace']['customer_lookup']['active_customers']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Open Customer Cases</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['support_workspace']['customer_lookup']['open_customer_cases']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['support_workspace']['customer_lookup']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                        @foreach($dashboard['support_workspace']['customer_lookup']['items'] as $item)
                            <a href="{{ $item['href'] }}" class="flex items-start justify-between gap-3 transition-opacity hover:opacity-80">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $item['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $item['contact'] }}</p>
                                </div>
                                <p class="max-w-[11rem] text-right text-[11px] text-slate-500">{{ $item['context'] }}</p>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state mt-4">Customer identities from active support and order activity will appear here.</div>
                @endif
            </x-nino.card>

            <x-nino.card title="Open Cases / Refund Queue Widget" subtitle="Active support workload plus refund exposure needing follow-up.">
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Open Cases</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['support_workspace']['open_cases_refund_queue']['open_cases']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Urgent Cases</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['support_workspace']['open_cases_refund_queue']['urgent_cases']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Refund Queue</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['support_workspace']['open_cases_refund_queue']['refund_queue_count']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Refund Amount</p>
                        <p class="mt-2 text-base font-black text-slate-900">{{ $dashboard['support_workspace']['open_cases_refund_queue']['refund_queue_amount'] }}</p>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 border-t border-slate-200 pt-4 md:grid-cols-2">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Open Cases</p>
                            <x-nino.button href="{{ $dashboard['support_workspace']['open_cases_refund_queue']['issues_href'] }}" size="sm" variant="secondary">Open issues</x-nino.button>
                        </div>
                        @forelse($dashboard['support_workspace']['open_cases_refund_queue']['issues'] as $issue)
                            <a href="{{ $issue['href'] }}" class="flex items-start justify-between gap-3 transition-opacity hover:opacity-80">
                                <div class="min-w-0">
                                    <p class="font-mono text-xs text-slate-500">{{ $issue['reference'] }}</p>
                                    <p class="mt-1 truncate text-sm font-semibold text-slate-900">{{ $issue['subject'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">{{ $issue['status'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $issue['priority'] }}</p>
                                </div>
                            </a>
                        @empty
                            <div class="empty-state">Active support issues will appear here automatically.</div>
                        @endforelse
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Refund Queue</p>
                            <x-nino.button href="{{ $dashboard['support_workspace']['open_cases_refund_queue']['refunds_href'] }}" size="sm" variant="secondary">Open refunds</x-nino.button>
                        </div>
                        @forelse($dashboard['support_workspace']['open_cases_refund_queue']['refunds'] as $refund)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-mono text-xs text-slate-500">{{ $refund['reference'] }}</p>
                                    <p class="mt-1 truncate text-sm font-semibold text-slate-900">{{ $refund['order_reference'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">{{ $refund['status'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $refund['amount'] }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">Refund requests awaiting review will appear here.</div>
                        @endforelse
                    </div>
                </div>
            </x-nino.card>

            <x-nino.card title="Recent Issue Timeline Widget" subtitle="Latest support activity and status movement across current cases.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['support_workspace']['recent_issue_timeline']['href'] }}" size="sm" variant="secondary">Open support issues</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['support_workspace']['recent_issue_timeline']['items'])->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($dashboard['support_workspace']['recent_issue_timeline']['items'] as $item)
                            <a href="{{ $item['href'] }}" class="flex items-start justify-between gap-3 transition-opacity hover:opacity-80">
                                <div class="min-w-0">
                                    <p class="font-mono text-xs text-slate-500">{{ $item['reference'] }} · {{ $item['action'] }}</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ $item['description'] }}</p>
                                </div>
                                <p class="text-[11px] text-slate-500">{{ $item['occurred_at'] }}</p>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">Support timeline events will appear here as cases move through the queue.</div>
                @endif
            </x-nino.card>
        </x-nino.dashboard-section>
    @endif

    @if(! empty($dashboard['shipping_workspace']))
        <x-nino.dashboard-section
            title="Shipping Workspace"
            subtitle="Operator-focused shipment queues for preparation, carrier handoff, delivery exceptions, and returns."
            grid-class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-nino.card title="Ready to Ship Widget" subtitle="Shipments queued for preparation and carrier handoff.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['shipping_workspace']['ready_to_ship']['href'] }}" size="sm" variant="secondary">Open ready queue</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Ready to Ship</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['shipping_workspace']['ready_to_ship']['ready_count']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Packed</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['shipping_workspace']['ready_to_ship']['packed_count']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['shipping_workspace']['ready_to_ship']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                        @foreach($dashboard['shipping_workspace']['ready_to_ship']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-mono text-xs text-slate-500">{{ $item['reference'] }}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $item['tracking'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $item['date'] }}</p>
                                </div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">{{ $item['status'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state mt-4">Shipments waiting for packing or dispatch will appear here.</div>
                @endif
            </x-nino.card>

            <x-nino.card title="Shipped Widget" subtitle="Parcels that have already left the warehouse and are with the carrier.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['shipping_workspace']['shipped']['href'] }}" size="sm" variant="secondary">Open in transit</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Dispatched</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['shipping_workspace']['shipped']['dispatched_count']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">In Transit</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['shipping_workspace']['shipped']['in_transit_count']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['shipping_workspace']['shipped']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                        @foreach($dashboard['shipping_workspace']['shipped']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-mono text-xs text-slate-500">{{ $item['reference'] }}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $item['tracking'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $item['date'] }}</p>
                                </div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">{{ $item['status'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state mt-4">Dispatched and in-transit parcels will appear here for active monitoring.</div>
                @endif
            </x-nino.card>

            <x-nino.card title="Failed Delivery Widget" subtitle="Delivery attempts that failed and still need follow-up.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['shipping_workspace']['failed_delivery']['href'] }}" size="sm" variant="secondary">Open failed deliveries</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Failed Deliveries</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['shipping_workspace']['failed_delivery']['failed_count']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Open Issues</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['shipping_workspace']['failed_delivery']['issue_count']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['shipping_workspace']['failed_delivery']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                        @foreach($dashboard['shipping_workspace']['failed_delivery']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-mono text-xs text-slate-500">{{ $item['reference'] }}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $item['tracking'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $item['reason'] }}</p>
                                </div>
                                <p class="text-[11px] text-slate-500">{{ $item['date'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state mt-4">Failed carrier drop-offs will show up here for quick recovery.</div>
                @endif
            </x-nino.card>

            <x-nino.card title="Returned Parcel Queue Widget" subtitle="Returned shipments waiting for warehouse or customer-service handling.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['shipping_workspace']['returned_parcel_queue']['href'] }}" size="sm" variant="secondary">Open returns</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Returned</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['shipping_workspace']['returned_parcel_queue']['returned_count']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Returned Today</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['shipping_workspace']['returned_parcel_queue']['returned_today']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['shipping_workspace']['returned_parcel_queue']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                        @foreach($dashboard['shipping_workspace']['returned_parcel_queue']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-mono text-xs text-slate-500">{{ $item['reference'] }}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $item['tracking'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $item['carrier'] }}</p>
                                </div>
                                <p class="text-[11px] text-slate-500">{{ $item['date'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state mt-4">Returned shipments will appear here for queue-based processing.</div>
                @endif
            </x-nino.card>

            <x-nino.card title="Tracking Queue Widget" subtitle="Shipments that still need tracking numbers or tracking follow-up before customer visibility is reliable.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['shipping_workspace']['tracking_queue']['href'] }}" size="sm" variant="secondary">Open tracking queue</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Missing Tracking</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['shipping_workspace']['tracking_queue']['missing_tracking_count']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Tracked Active</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['shipping_workspace']['tracking_queue']['tracked_active_count']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['shipping_workspace']['tracking_queue']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                        @foreach($dashboard['shipping_workspace']['tracking_queue']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-mono text-xs text-slate-500">{{ $item['reference'] }}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $item['carrier'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $item['date'] }}</p>
                                </div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">{{ $item['status'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state mt-4">Shipments that still need tracking numbers will appear here automatically.</div>
                @endif
            </x-nino.card>
        </x-nino.dashboard-section>
    @endif

    @if(! empty($dashboard['finance_workspace']))
        <x-nino.dashboard-section
            title="Finance Workspace"
            subtitle="Payment-state visibility for finance operators handling reconciliation, exceptions, and gateway performance."
            grid-class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-nino.card title="Paid / Unpaid Summary Widget" subtitle="Completed payment volume versus unresolved payment exposure.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['finance_workspace']['paid_unpaid_summary']['href'] }}" size="sm" variant="secondary">Open transactions</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Paid Count</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['finance_workspace']['paid_unpaid_summary']['paid_count']) }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $dashboard['finance_workspace']['paid_unpaid_summary']['paid_volume'] }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Unpaid Count</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['finance_workspace']['paid_unpaid_summary']['unpaid_count']) }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $dashboard['finance_workspace']['paid_unpaid_summary']['unpaid_volume'] }}</p>
                    </div>
                </div>
            </x-nino.card>

            <x-nino.card title="Payment Method Summary Widget" subtitle="Payment-method mix by transaction count and processed amount.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['finance_workspace']['payment_method_summary']['href'] }}" size="sm" variant="secondary">Open finance</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['finance_workspace']['payment_method_summary']['items'])->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($dashboard['finance_workspace']['payment_method_summary']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $item['method'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $item['amount'] }}</p>
                                </div>
                                <span class="font-mono text-sm font-semibold text-slate-900">{{ number_format($item['count']) }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">Payment method mix will appear here as transactions are processed.</div>
                @endif
            </x-nino.card>

            <x-nino.card title="Gateway Transactions Summary Widget" subtitle="Gateway throughput and failure pressure across current payment traffic.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['finance_workspace']['gateway_transactions_summary']['href'] }}" size="sm" variant="secondary">Open finance</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['finance_workspace']['gateway_transactions_summary']['items'])->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($dashboard['finance_workspace']['gateway_transactions_summary']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $item['gateway'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $item['amount'] }} · {{ number_format($item['failed_count']) }} failed</p>
                                </div>
                                <span class="font-mono text-sm font-semibold text-slate-900">{{ number_format($item['count']) }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">Gateway-level transaction performance will appear here as payments are processed.</div>
                @endif
            </x-nino.card>

            <x-nino.card title="COD Reconciliation Summary Widget" subtitle="Cash-on-delivery collection and reconciliation pressure.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['finance_workspace']['cod_reconciliation_summary']['href'] }}" size="sm" variant="secondary">Open COD</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Collected</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['finance_workspace']['cod_reconciliation_summary']['collected_count']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Reconciled</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['finance_workspace']['cod_reconciliation_summary']['reconciled_count']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Discrepancies</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['finance_workspace']['cod_reconciliation_summary']['discrepancy_count']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Outstanding</p>
                        <p class="mt-2 text-base font-black text-slate-900">{{ $dashboard['finance_workspace']['cod_reconciliation_summary']['outstanding_amount'] }}</p>
                    </div>
                </div>
            </x-nino.card>

            <x-nino.card title="Refund Summary Widget" subtitle="Queue visibility for requested and approved refunds.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['finance_workspace']['refund_summary']['href'] }}" size="sm" variant="secondary">Open refunds</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Requested</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['finance_workspace']['refund_summary']['requested_count']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Approved</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['finance_workspace']['refund_summary']['approved_count']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Queued Amount</p>
                        <p class="mt-2 text-base font-black text-slate-900">{{ $dashboard['finance_workspace']['refund_summary']['queued_amount'] }}</p>
                    </div>
                </div>
            </x-nino.card>

            <x-nino.card title="Discount / Fee Impact Widget" subtitle="Commercial impact from discounts and payment-processing fees.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['finance_workspace']['discount_fee_impact']['href'] }}" size="sm" variant="secondary">Open transactions</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Discounted Orders</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['finance_workspace']['discount_fee_impact']['discounted_orders']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Discounts</p>
                        <p class="mt-2 text-base font-black text-slate-900">{{ $dashboard['finance_workspace']['discount_fee_impact']['total_discounts'] }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Fees</p>
                        <p class="mt-2 text-base font-black text-slate-900">{{ $dashboard['finance_workspace']['discount_fee_impact']['total_fees'] }}</p>
                    </div>
                </div>
            </x-nino.card>
        </x-nino.dashboard-section>
    @endif

    @if(! empty($dashboard['content_workspace']))
        <x-nino.dashboard-section
            title="Content Workspace"
            subtitle="Editorial queue visibility for content operators and SEO managers."
            grid-class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <x-nino.card title="Drafts Widget" subtitle="Unpublished content waiting for editorial review or SEO completion.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['content_workspace']['drafts']['href'] }}" size="sm" variant="secondary">Open drafts</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Draft Posts</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['content_workspace']['drafts']['draft_count']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Scheduled Posts</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['content_workspace']['drafts']['scheduled_count']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['content_workspace']['drafts']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                        @foreach($dashboard['content_workspace']['drafts']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $item['title'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $item['author'] }}</p>
                                </div>
                                <p class="text-[11px] text-slate-500">{{ $item['updated_at'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state mt-4">Draft blog posts will appear here for editorial follow-up.</div>
                @endif
            </x-nino.card>

            <x-nino.card title="Missing Metadata Widget" subtitle="Published content missing key metadata fields.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['content_workspace']['missing_metadata']['href'] }}" size="sm" variant="secondary">Open content</x-nino.button>
                </x-slot:header>

                <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Items Missing Metadata</p>
                    <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['content_workspace']['missing_metadata']['total_items']) }}</p>
                </div>

                @if(collect($dashboard['content_workspace']['missing_metadata']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                        @foreach($dashboard['content_workspace']['missing_metadata']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $item['title'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $item['type'] }} · {{ $item['missing'] }}</p>
                                </div>
                                <p class="text-[11px] text-slate-500">{{ $item['updated_at'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state mt-4">Missing metadata items will appear here automatically.</div>
                @endif
            </x-nino.card>

            <x-nino.card title="SEO Issue Summary Widget" subtitle="Highest-signal SEO risks across content and catalog surfaces.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['content_workspace']['seo_issue_summary']['href'] }}" size="sm" variant="secondary">Open SEO settings</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Missing Titles</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['content_workspace']['seo_issue_summary']['missing_meta_title']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Missing Descriptions</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['content_workspace']['seo_issue_summary']['missing_meta_description']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Missing Canonicals</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['content_workspace']['seo_issue_summary']['missing_canonical']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Published Noindex</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['content_workspace']['seo_issue_summary']['published_noindex']) }}</p>
                    </div>
                </div>
            </x-nino.card>

            <x-nino.card title="Scheduled Content Widget" subtitle="Upcoming scheduled posts and same-day publishing pressure.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['content_workspace']['scheduled_content']['href'] }}" size="sm" variant="secondary">Open scheduled</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Scheduled Posts</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['content_workspace']['scheduled_content']['scheduled_count']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Publishes Today</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['content_workspace']['scheduled_content']['publishes_today']) }}</p>
                    </div>
                </div>

                <div class="mt-4 rounded-md border border-slate-200 bg-slate-50 p-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Next Publish</p>
                    <p class="mt-2 text-base font-black text-slate-900">{{ $dashboard['content_workspace']['scheduled_content']['next_publish_at'] }}</p>
                </div>

                @if(collect($dashboard['content_workspace']['scheduled_content']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                        @foreach($dashboard['content_workspace']['scheduled_content']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $item['title'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $item['author'] }}</p>
                                </div>
                                <p class="text-[11px] text-slate-500">{{ $item['scheduled_for'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-nino.card>
        </x-nino.dashboard-section>
    @endif

    @if(! empty($dashboard['media_workspace']))
        <x-nino.dashboard-section
            title="Media Buying Workspace"
            subtitle="Ad and tracking integrations visibility for marketing operators."
            grid-class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <x-nino.card title="Integration Health Widget" subtitle="Analytics and pixel integrations currently configured in SEO settings.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['media_workspace']['integration_health']['href'] }}" size="sm" variant="secondary">Open integrations</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Configured</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['media_workspace']['integration_health']['configured_count']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Missing</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['media_workspace']['integration_health']['missing_count']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['media_workspace']['integration_health']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                        @foreach($dashboard['media_workspace']['integration_health']['items'] as $item)
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-slate-900">{{ $item['name'] }}</p>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">{{ $item['status'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-nino.card>

            <x-nino.card title="Campaign Hooks Widget" subtitle="Coupon and recovery hooks currently available to paid campaigns.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['media_workspace']['campaign_hooks']['href'] }}" size="sm" variant="secondary">Open promotions</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Active Coupons</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['media_workspace']['campaign_hooks']['active_coupons']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Coupon Redemptions</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['media_workspace']['campaign_hooks']['coupon_redemptions']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Discount Value</p>
                        <p class="mt-2 text-base font-black text-slate-900">{{ $dashboard['media_workspace']['campaign_hooks']['discount_value'] }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Recoverable Carts</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['media_workspace']['campaign_hooks']['recoverable_carts']) }}</p>
                    </div>
                </div>

                <div class="mt-4 rounded-md border border-slate-200 bg-slate-50 p-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Recoverable Value</p>
                    <p class="mt-2 text-base font-black text-slate-900">{{ $dashboard['media_workspace']['campaign_hooks']['recoverable_value'] }}</p>
                </div>
            </x-nino.card>

            <x-nino.card title="Traffic / Campaign Placeholder Widget" subtitle="Reserved for analytics and paid-media ingestion once attribution is connected.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['media_workspace']['traffic_placeholder']['href'] }}" size="sm" variant="secondary">Open SEO settings</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Configured Sources</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['media_workspace']['traffic_placeholder']['configured_sources']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Awaiting Sources</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['media_workspace']['traffic_placeholder']['awaiting_sources']) }}</p>
                    </div>
                </div>

                <div class="mt-4 rounded-md border border-slate-200 bg-slate-50 p-3">
                    <p class="text-sm text-slate-600">{{ $dashboard['media_workspace']['traffic_placeholder']['status'] }}</p>
                </div>
            </x-nino.card>
        </x-nino.dashboard-section>
    @endif

    @if(! empty($dashboard['sales_workspace']))
        @php
            $salesRevenueChartConfig = [
                'type' => 'line',
                'data' => $dashboard['sales_workspace']['revenue_trend']['chart'],
                'options' => [
                    'plugins' => ['legend' => ['display' => true]],
                    'scales' => [
                        'y' => ['title' => ['display' => true, 'text' => 'Revenue']],
                    ],
                ],
            ];
        @endphp
        <x-nino.dashboard-section
            title="Sales Workspace"
            subtitle="Revenue pacing and order quality for sales operators."
            grid-class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.85fr)]">
            <x-nino.card title="Revenue Trend Widget" subtitle="Revenue trend across {{ strtolower($dashboard['range_label']) }}">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['sales_workspace']['revenue_trend']['href'] }}" size="sm" variant="secondary">Open sales</x-nino.button>
                </x-slot:header>

                <div class="relative" data-chart-shell data-chart-state="loading">
                    <div class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-slate-300 bg-slate-50/95" data-chart-loading>
                        <span class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Loading chart</span>
                        <div class="flex items-end gap-2" aria-hidden="true">
                            <span class="block h-6 w-2 rounded-full bg-emerald-200"></span>
                            <span class="block h-10 w-2 rounded-full bg-emerald-300"></span>
                            <span class="block h-5 w-2 rounded-full bg-emerald-200"></span>
                        </div>
                    </div>
                    <canvas
                        id="nino-v1-dashboard-sales-revenue-chart"
                        data-chart-height="260"
                        data-chart-config='@json($salesRevenueChartConfig)'></canvas>
                </div>
            </x-nino.card>

            <x-nino.card title="AOV Widget" subtitle="Average order value across the active dashboard window.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['sales_workspace']['aov']['href'] }}" size="sm" variant="secondary">Open sales</x-nino.button>
                </x-slot:header>

                <div class="space-y-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Average Order Value</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ $dashboard['sales_workspace']['aov']['value'] }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Orders in Window</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['sales_workspace']['aov']['orders']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Trend</p>
                        <p class="mt-2 text-sm text-slate-600">{{ $dashboard['sales_workspace']['aov']['change'] }}</p>
                    </div>
                </div>
            </x-nino.card>

            <x-nino.card title="Best Sellers Widget" subtitle="Top products by revenue in the active dashboard window.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['sales_workspace']['best_sellers']['href'] }}" size="sm" variant="secondary">Open sales</x-nino.button>
                </x-slot:header>

                @if(collect($dashboard['sales_workspace']['best_sellers']['items'])->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($dashboard['sales_workspace']['best_sellers']['items'] as $item)
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $item['label'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ number_format($item['units']) }} units</p>
                                </div>
                                <p class="font-mono text-sm font-semibold text-slate-900">{{ number_format((float) $item['revenue'], 2) }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">Best-selling products will appear here once orders are flowing.</div>
                @endif
            </x-nino.card>

            <x-nino.card title="Promo Performance Widget" subtitle="Coupon redemptions and recovery opportunity across the active window.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['sales_workspace']['promo_performance']['href'] }}" size="sm" variant="secondary">Open promotions</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Active Coupons</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['sales_workspace']['promo_performance']['active_coupons']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Redemptions</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['sales_workspace']['promo_performance']['redemptions']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Discount Value</p>
                        <p class="mt-2 text-base font-black text-slate-900">{{ $dashboard['sales_workspace']['promo_performance']['discount_value'] }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Recoverable Carts</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['sales_workspace']['promo_performance']['recoverable_carts']) }}</p>
                    </div>
                </div>

                <div class="mt-4 rounded-md border border-slate-200 bg-slate-50 p-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Recoverable Value</p>
                    <p class="mt-2 text-base font-black text-slate-900">{{ $dashboard['sales_workspace']['promo_performance']['recoverable_value'] }}</p>
                </div>
            </x-nino.card>
        </x-nino.dashboard-section>
    @endif

    @if(! empty($dashboard['stock_workspace']))
        <x-nino.dashboard-section
            title="Stock Workspace"
            subtitle="Inventory pressure and damage monitoring for stock operators."
            grid-class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-nino.card title="Low Stock Widget" subtitle="Items currently at low-stock or out-of-stock thresholds.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['stock_workspace']['low_stock']['href'] }}" size="sm" variant="secondary">Open inventory</x-nino.button>
                </x-slot:header>

                <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Stock Alerts</p>
                    <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['stock_workspace']['low_stock']['count']) }}</p>
                </div>

                @if(collect($dashboard['stock_workspace']['low_stock']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                        @foreach($dashboard['stock_workspace']['low_stock']['items'] as $item)
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $item['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $item['sku'] }} · {{ $item['branch'] }}</p>
                                </div>
                                <p class="text-sm font-semibold text-slate-900">{{ $item['available'] }} available</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-nino.card>

            <x-nino.card title="Damaged Stock Widget" subtitle="Recent damaged-item deductions recorded in inventory movements.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['stock_workspace']['damaged_stock']['href'] }}" size="sm" variant="secondary">Open adjustments</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Damage Events</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['stock_workspace']['damaged_stock']['damaged_events']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Damaged Units</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['stock_workspace']['damaged_stock']['damaged_units']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['stock_workspace']['damaged_stock']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                        @foreach($dashboard['stock_workspace']['damaged_stock']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $item['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $item['sku'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-mono text-sm font-semibold text-slate-900">{{ number_format($item['units']) }} units</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $item['date'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-nino.card>

            <x-nino.card title="Adjustment Summary Widget" subtitle="Manual inventory overrides recorded during the active dashboard window.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['stock_workspace']['adjustment_summary']['href'] }}" size="sm" variant="secondary">Open adjustments</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Adjustment Events</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['stock_workspace']['adjustment_summary']['adjustment_events']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Adjusted Units</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['stock_workspace']['adjustment_summary']['adjusted_units']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['stock_workspace']['adjustment_summary']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                        @foreach($dashboard['stock_workspace']['adjustment_summary']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $item['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $item['sku'] }} · {{ $item['direction'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-mono text-sm font-semibold text-slate-900">{{ number_format($item['units']) }} units</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $item['date'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-nino.card>

            <x-nino.card title="Transfer-Ready Metrics Widget" subtitle="Stock transfers currently pending or in flight across the network.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['stock_workspace']['transfer_ready_metrics']['href'] }}" size="sm" variant="secondary">Open inventory</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Pending</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['stock_workspace']['transfer_ready_metrics']['pending_transfers']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Shipped</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['stock_workspace']['transfer_ready_metrics']['shipped_transfers']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">In-Flight Units</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['stock_workspace']['transfer_ready_metrics']['in_flight_units']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['stock_workspace']['transfer_ready_metrics']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                        @foreach($dashboard['stock_workspace']['transfer_ready_metrics']['items'] as $item)
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $item['name'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $item['sku'] }} · {{ $item['status'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-mono text-sm font-semibold text-slate-900">{{ number_format($item['units']) }} units</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $item['date'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-nino.card>
        </x-nino.dashboard-section>
    @endif

    @if(! empty($dashboard['moderator_workspace']))
        <x-nino.dashboard-section
            title="Community Moderator Workspace"
            subtitle="Schema-ready moderation queue visibility while community admin surfaces are still pending."
            grid-class="grid grid-cols-1 gap-6 xl:grid-cols-1">
            <x-nino.card title="Report Queue Placeholder Widget" subtitle="Open moderation load and current report state counts.">
                <x-slot:header>
                    <x-nino.button href="{{ $dashboard['moderator_workspace']['report_queue']['href'] }}" size="sm" variant="secondary">Open dashboard</x-nino.button>
                </x-slot:header>

                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Open Queue</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['moderator_workspace']['report_queue']['open_queue']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Assigned</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['moderator_workspace']['report_queue']['assigned_queue']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">In Review</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['moderator_workspace']['report_queue']['in_review_queue']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Open Reports</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['moderator_workspace']['report_queue']['open_reports']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Action Taken</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['moderator_workspace']['report_queue']['action_taken']) }}</p>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Dismissed</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($dashboard['moderator_workspace']['report_queue']['dismissed_reports']) }}</p>
                    </div>
                </div>

                @if(collect($dashboard['moderator_workspace']['report_queue']['items'])->isNotEmpty())
                    <div class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                        @foreach($dashboard['moderator_workspace']['report_queue']['items'] as $item)
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-900">{{ $item['status'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">Priority {{ number_format($item['priority']) }}</p>
                                </div>
                                <p class="text-[11px] text-slate-500">{{ $item['queued_at'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state mt-4">This placeholder will surface live community moderation work once reports begin flowing.</div>
                @endif
            </x-nino.card>
        </x-nino.dashboard-section>
    @endif

    <x-nino.dashboard-section
        title="Operations"
        subtitle="Queue-style widgets for recent activity and urgent follow-up."
        grid-class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(320px,1fr)]">
        <x-nino.card title="Recent Orders" noPadding>
            @if(collect($dashboard['recent_orders'])->isNotEmpty())
                <div class="divide-y divide-slate-100">
                    @foreach($dashboard['recent_orders'] as $order)
                        <a href="{{ $order['url'] }}" wire:navigate class="flex items-center justify-between gap-4 px-5 py-4 transition-colors hover:bg-slate-50">
                            <div class="min-w-0">
                                <div class="flex items-center gap-3">
                                    <span class="font-mono text-xs text-slate-500">{{ $order['reference'] }}</span>
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $order['customer'] }}</p>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ $order['date'] }}</p>
                            </div>
                            <span class="font-mono text-sm font-semibold text-slate-900">{{ $order['total'] }}</span>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="p-5">
                    <div class="empty-state">No orders yet for the selected window.</div>
                </div>
            @endif
        </x-nino.card>

        <x-nino.card title="Operational Queues">
            @if(collect($dashboard['queue_cards'])->isNotEmpty())
                <div class="space-y-4">
                    @foreach($dashboard['queue_cards'] as $queue)
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">{{ $queue['title'] }}</p>
                                    <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ $queue['value'] }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $queue['subtitle'] }}</p>
                                </div>
                                <span class="material-symbols-outlined text-slate-400">{{ $queue['icon'] }}</span>
                            </div>
                            <div class="mt-4">
                                <x-nino.button href="{{ $queue['href'] }}" size="sm" variant="secondary">{{ $queue['cta'] }}</x-nino.button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    No active operational queues are visible for this role yet.
                </div>
            @endif
        </x-nino.card>
    </x-nino.dashboard-section>
</x-nino.dashboard-layout>

@endsection
