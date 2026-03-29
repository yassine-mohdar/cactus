@extends('admin.layouts.app')

@section('title', 'Shipments')

@php
    $shipmentTone = static function ($status): string {
        $value = $status?->value ?? (string) $status;

        return match ($value) {
            'delivered' => 'success',
            'dispatched', 'in_transit' => 'info',
            'failed_delivery', 'returned', 'cancelled' => 'danger',
            default => 'warning',
        };
    };
@endphp

@section('header')
    <x-nino.page-header
        title="Shipments"
        subtitle="Fulfillment queue, carrier health, and exception handling in one operational view.">
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-2">
                <span class="datatable-meta">{{ number_format($shipments->total()) }} visible</span>
                <x-nino.button href="{{ route('admin.shipping.reports') }}" variant="secondary" icon="insights">Reports</x-nino.button>
            </div>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
@if(session('success'))
    <x-nino.inline-alert tone="success" title="Shipment updated" class="mb-6">
        {{ session('success') }}
    </x-nino.inline-alert>
@endif
@if(session('error'))
    <x-nino.inline-alert tone="danger" title="Shipment update failed" class="mb-6">
        {{ session('error') }}
    </x-nino.inline-alert>
@endif

    <div class="stats-grid mb-6">
        <a href="{{ route('admin.shipping.shipments.index') }}" class="stat-card">
            <p class="stat-label">Total Shipments</p>
            <p class="stat-value">{{ number_format($stats['total']) }}</p>
        </a>
        <a href="{{ route('admin.shipping.shipments.index', ['status' => 'ready_to_ship']) }}" class="stat-card">
            <p class="stat-label">Ready to Ship</p>
            <p class="stat-value text-[#B8802F]">{{ number_format($stats['ready_to_ship']) }}</p>
        </a>
        <a href="{{ route('admin.shipping.shipments.index', ['status' => 'packed']) }}" class="stat-card">
            <p class="stat-label">Packed</p>
            <p class="stat-value text-[#235B8C]">{{ number_format($stats['packed']) }}</p>
        </a>
        <a href="{{ route('admin.shipping.shipments.index', ['status' => 'dispatched']) }}" class="stat-card">
            <p class="stat-label">In Transit</p>
            <p class="stat-value text-[#3B6F95]">{{ number_format($stats['in_transit']) }}</p>
        </a>
        <a href="{{ route('admin.shipping.shipments.index', ['issues' => '1']) }}" class="stat-card">
            <p class="stat-label">Attention Needed</p>
            <p class="stat-value text-[#C45143]">{{ number_format($stats['attention']) }}</p>
        </a>
        <div class="stat-card">
            <p class="stat-label">Delivered Today</p>
            <p class="stat-value text-[#1F7A4E]">{{ number_format($stats['delivered_today']) }}</p>
        </div>
        <a href="{{ route('admin.shipping.shipments.index', ['issues' => '1']) }}" class="stat-card">
            <p class="stat-label">Open Issues</p>
            <p class="stat-value text-[#C45143]">{{ number_format($issueBreakdown['open']) }}</p>
        </a>
        <a href="{{ route('admin.shipping.shipments.index', ['status' => 'returned']) }}" class="stat-card">
            <p class="stat-label">Returned</p>
            <p class="stat-value text-[#B8802F]">{{ number_format($stats['returned']) }}</p>
        </a>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(320px,0.9fr)]">
        <div class="space-y-6">
            <form method="GET" class="filter-toolbar">
                <div class="filter-grid xl:grid-cols-5">
                    <div class="filter-field">
                        <label class="filter-label" for="shipment-status">Status</label>
                        <select id="shipment-status" name="status" class="input-field">
                            <option value="">All statuses</option>
                            @foreach(\App\Modules\Shipping\Enums\ShipmentStatus::cases() as $s)
                                <option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-field">
                        <label class="filter-label" for="shipment-issues">Issue Queue</label>
                        <select id="shipment-issues" name="issues" class="input-field">
                            <option value="">All shipments</option>
                            <option value="1" {{ request('issues') === '1' ? 'selected' : '' }}>Only issues</option>
                        </select>
                    </div>
                    <div class="filter-field xl:col-span-2">
                        <label class="filter-label" for="shipment-search">Search</label>
                        <input id="shipment-search" type="text" name="search" value="{{ request('search') }}" placeholder="Tracking number or order reference..." class="input-field">
                    </div>
                    <div class="filter-field xl:items-end">
                        <div class="flex flex-wrap gap-3">
                            <x-nino.button type="submit" variant="primary">Apply Filters</x-nino.button>
                            <x-nino.button href="{{ route('admin.shipping.shipments.index') }}" variant="outline">Reset</x-nino.button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="datatable-shell">
                <div class="datatable-header">
                    <div>
                        <h2 class="datatable-title">Fulfillment Queue</h2>
                        <p class="datatable-subtitle">Operational shipment table with carrier, tracking, exception state, and queue timing.</p>
                    </div>
                    <span class="datatable-meta">{{ number_format($shipments->total()) }} results</span>
                </div>

                <x-nino.table>
                    <x-slot:head>
                        <th class="text-left">Order</th>
                        <th class="text-left">Customer</th>
                        <th class="text-left">Carrier</th>
                        <th class="text-left">Tracking</th>
                        <th class="text-left">Status</th>
                        <th class="text-left">Updated</th>
                        <th class="text-right">Actions</th>
                    </x-slot:head>

                    <x-slot:body>
                        @forelse($shipments as $shipment)
                            <tr class="{{ $shipment->has_delivery_issue ? 'bg-[#FCEDEA]/55' : '' }}">
                                <td class="text-left">
                                    <a href="{{ route('admin.orders.show', $shipment->order_id) }}" class="table-link table-mono">
                                        {{ $shipment->order?->reference_number ?? '#'.$shipment->order_id }}
                                    </a>
                                </td>
                                <td class="text-left">
                                    <p class="text-sm font-semibold text-[#1E2B27]">{{ trim(($shipment->order?->customer?->first_name ?? 'Guest').' '.($shipment->order?->customer?->last_name ?? '')) }}</p>
                                    @if($shipment->shippingMethod?->name)
                                        <p class="mt-1 text-xs text-[#7A8681]">{{ $shipment->shippingMethod->name }}</p>
                                    @endif
                                </td>
                                <td class="text-left">
                                    <p class="text-sm text-[#1E2B27]">{{ $shipment->carrier_name ?: 'Unassigned' }}</p>
                                    @if($shipment->carrier_service)
                                        <p class="mt-1 text-xs text-[#7A8681]">{{ $shipment->carrier_service }}</p>
                                    @endif
                                </td>
                                <td class="text-left">
                                    @if($shipment->tracking_number)
                                        @if($link = $shipment->getTrackingLink())
                                            <a href="{{ $link }}" target="_blank" rel="noreferrer" class="table-link table-mono">{{ $shipment->tracking_number }}</a>
                                        @else
                                            <span class="table-mono text-[#1E2B27]">{{ $shipment->tracking_number }}</span>
                                        @endif
                                    @else
                                        <span class="table-muted">Not assigned</span>
                                    @endif
                                </td>
                                <td class="text-left">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-nino.status-badge :tone="$shipmentTone($shipment->status)" size="sm">{{ $shipment->status->label() }}</x-nino.status-badge>
                                        @if($shipment->has_delivery_issue)
                                            <x-nino.status-badge tone="danger" size="sm">Issue</x-nino.status-badge>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-left">
                                    <p class="table-mono text-[#61706B]">{{ $shipment->updated_at->format('M d, H:i') }}</p>
                                    <p class="mt-1 text-xs text-[#7A8681]">{{ $shipment->updated_at->diffForHumans() }}</p>
                                </td>
                                <td class="text-right">
                                    <div class="table-actions">
                                        <a href="{{ route('admin.shipping.shipments.show', $shipment) }}" class="table-action-link">View</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="datatable-empty">
                                    <x-nino.empty-state
                                        title="No shipments found"
                                        description="Try adjusting the filters or wait for the next fulfillment batch to reach this queue."
                                        icon="local_shipping" />
                                </td>
                            </tr>
                        @endforelse
                    </x-slot:body>
                </x-nino.table>

                <div class="datatable-footer">
                    {{ $shipments->links() }}
                </div>
            </div>
        </div>

        <div class="detail-stack">
            <x-nino.detail-section title="Status Mix" subtitle="Current queue distribution across the shipment lifecycle.">
                <div class="space-y-3">
                    @foreach($statusBreakdown as $row)
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <x-nino.status-badge :tone="$shipmentTone($row['status'])" size="sm">{{ $row['status']->label() }}</x-nino.status-badge>
                            </div>
                            <span class="font-mono text-sm font-semibold text-[#1E2B27]">{{ number_format($row['count']) }}</span>
                        </div>
                    @endforeach
                </div>
            </x-nino.detail-section>

            <x-nino.detail-section title="Carrier Load" subtitle="Top carriers currently represented in the shipment queue.">
                <div class="space-y-3">
                    @forelse($carrierBreakdown as $carrier)
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-semibold text-[#1E2B27]">{{ $carrier->carrier_label }}</span>
                            <span class="font-mono text-sm text-[#61706B]">{{ number_format($carrier->aggregate) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-[#61706B]">Carrier allocation will appear as soon as shipment routing starts.</p>
                    @endforelse
                </div>
            </x-nino.detail-section>

            <x-nino.detail-section title="Aged Work" subtitle="Queues that have been sitting longer than the normal operational target.">
                <x-nino.entity-detail-grid>
                    <div>
                        <p class="detail-kicker">Ready > 24h</p>
                        <p class="detail-value-mono">{{ number_format($agedCounts['ready_over_24h']) }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Transit > 72h</p>
                        <p class="detail-value-mono">{{ number_format($agedCounts['transit_over_72h']) }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Issues > 48h</p>
                        <p class="detail-value-mono">{{ number_format($agedCounts['issues_over_48h']) }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Failed Delivery</p>
                        <p class="detail-value-mono">{{ number_format($issueBreakdown['failed_delivery']) }}</p>
                    </div>
                </x-nino.entity-detail-grid>
            </x-nino.detail-section>
        </div>
    </div>
@endsection
