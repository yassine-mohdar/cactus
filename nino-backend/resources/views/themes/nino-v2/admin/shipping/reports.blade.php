@extends('admin.layouts.app')

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

@section('title', 'Shipping Reports')

@section('header')
    <x-nino.page-header
        title="Shipping Reports"
        subtitle="Shipment history, status transitions, and exception queues for fulfillment operations.">
        <x-slot:actions>
            <div class="segmented-tabs">
                <a href="{{ route('admin.shipping.reports', ['tab' => 'history']) }}" class="segmented-tab {{ $tab === 'history' ? 'segmented-tab-active' : '' }}">History</a>
                <a href="{{ route('admin.shipping.reports', ['tab' => 'timeline']) }}" class="segmented-tab {{ $tab === 'timeline' ? 'segmented-tab-active' : '' }}">Status Log</a>
                <a href="{{ route('admin.shipping.reports', ['tab' => 'failed']) }}" class="segmented-tab {{ $tab === 'failed' ? 'segmented-tab-active' : '' }}">Exceptions</a>
            </div>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
@if($tab === 'timeline')
    <div class="datatable-shell">
        <div class="datatable-header">
            <div>
                <h2 class="datatable-title">Status Changes Log</h2>
                <p class="datatable-subtitle">Chronological shipment transitions recorded by staff and automation.</p>
            </div>
            <span class="datatable-meta">{{ number_format($history->total()) }} events</span>
        </div>

        <x-nino.table>
            <x-slot name="head">
                <th class="text-left">Time</th>
                <th class="text-left">Shipment</th>
                <th class="text-left">Order</th>
                <th class="text-left">Transition</th>
                <th class="text-left">Notes</th>
                <th class="text-left">By</th>
            </x-slot>

            <x-slot name="body">
                @forelse($history as $entry)
                    <tr>
                        <td class="text-left font-mono text-xs text-[#61706B]">{{ $entry->created_at->format('M d, H:i:s') }}</td>
                        <td class="text-left">
                            <a href="{{ route('admin.shipping.shipments.show', $entry->shipment_id) }}" class="table-link table-mono">#{{ $entry->shipment_id }}</a>
                        </td>
                        <td class="text-left text-sm text-[#1E2B27]">{{ $entry->shipment?->order?->reference_number ?? '—' }}</td>
                        <td class="text-left text-sm text-[#61706B]">
                            {{ $entry->status_from ? ucfirst(str_replace('_', ' ', $entry->status_from)) : '—' }}
                            <span class="mx-1 text-[#A0ACA7]">→</span>
                            <span class="font-semibold text-[#1E2B27]">{{ ucfirst(str_replace('_', ' ', $entry->status_to)) }}</span>
                        </td>
                        <td class="text-left text-sm text-[#61706B]">{{ $entry->notes ?: '—' }}</td>
                        <td class="text-left text-sm text-[#1E2B27]">{{ $entry->changed_by_name ?? 'System' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="datatable-empty">
                            <x-nino.empty-state
                                title="No status changes recorded"
                                description="Shipment transition history will appear here as soon as fulfillment activity is logged."
                                icon="timeline" />
                        </td>
                    </tr>
                @endforelse
            </x-slot>
        </x-nino.table>

        <div class="datatable-footer">
            {{ $history->links() }}
        </div>
    </div>
@elseif($tab === 'failed')
    <div class="datatable-shell">
        <div class="datatable-header">
            <div>
                <h2 class="datatable-title">Failed / Returned Queue</h2>
                <p class="datatable-subtitle">Shipments that need intervention due to delivery failure or return processing.</p>
            </div>
            <span class="datatable-meta">{{ number_format($shipments->total()) }} shipments</span>
        </div>

        <x-nino.table>
            <x-slot name="head">
                <th class="text-left">Order</th>
                <th class="text-left">Customer</th>
                <th class="text-left">Carrier</th>
                <th class="text-left">Status</th>
                <th class="text-left">Reason</th>
                <th class="text-left">Updated</th>
                <th class="text-right">Actions</th>
            </x-slot>

            <x-slot name="body">
                @forelse($shipments as $shipment)
                    <tr>
                        <td class="text-left">
                            <a href="{{ route('admin.orders.show', $shipment->order_id) }}" class="table-link table-mono">{{ $shipment->order?->reference_number }}</a>
                        </td>
                        <td class="text-left text-sm text-[#61706B]">{{ $shipment->order?->customer?->first_name ?? 'Guest' }}</td>
                        <td class="text-left text-sm text-[#1E2B27]">{{ $shipment->carrier_name ?? '—' }}</td>
                        <td class="text-left">
                            <x-nino.status-badge :tone="$shipmentTone($shipment->status)" size="sm">{{ $shipment->status->label() }}</x-nino.status-badge>
                        </td>
                        <td class="text-left text-sm text-[#61706B]">{{ $shipment->failure_reason ?? $shipment->delivery_issue_notes ?? '—' }}</td>
                        <td class="text-left font-mono text-xs text-[#61706B]">{{ $shipment->updated_at->format('M d, H:i') }}</td>
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
                                title="No failed or returned shipments"
                                description="Exceptions will surface here whenever a carrier or warehouse workflow requires intervention."
                                icon="inventory" />
                        </td>
                    </tr>
                @endforelse
            </x-slot>
        </x-nino.table>

        <div class="datatable-footer">
            {{ $shipments->links() }}
        </div>
    </div>
@else
    <div class="datatable-shell">
        <div class="datatable-header">
            <div>
                <h2 class="datatable-title">Shipment History</h2>
                <p class="datatable-subtitle">All shipment records with carrier, tracking, and fulfillment state in one operational table.</p>
            </div>
            <span class="datatable-meta">{{ number_format($shipments->total()) }} shipments</span>
        </div>

        <x-nino.table>
            <x-slot name="head">
                <th class="text-left">Order</th>
                <th class="text-left">Customer</th>
                <th class="text-left">Carrier</th>
                <th class="text-left">Tracking</th>
                <th class="text-left">Status</th>
                <th class="text-left">Created</th>
                <th class="text-right">Actions</th>
            </x-slot>

            <x-slot name="body">
                @forelse($shipments as $shipment)
                    <tr>
                        <td class="text-left">
                            <a href="{{ route('admin.orders.show', $shipment->order_id) }}" class="table-link table-mono">{{ $shipment->order?->reference_number }}</a>
                        </td>
                        <td class="text-left text-sm text-[#61706B]">{{ $shipment->order?->customer?->first_name ?? 'Guest' }}</td>
                        <td class="text-left text-sm text-[#1E2B27]">{{ $shipment->carrier_name ?? '—' }}</td>
                        <td class="text-left font-mono text-xs text-[#61706B]">{{ $shipment->tracking_number ?? '—' }}</td>
                        <td class="text-left">
                            <x-nino.status-badge :tone="$shipmentTone($shipment->status)" size="sm">{{ $shipment->status->label() }}</x-nino.status-badge>
                        </td>
                        <td class="text-left font-mono text-xs text-[#61706B]">{{ $shipment->created_at->format('M d, H:i') }}</td>
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
                                description="Shipment history will populate here once orders move into fulfillment."
                                icon="local_shipping" />
                        </td>
                    </tr>
                @endforelse
            </x-slot>
        </x-nino.table>

        <div class="datatable-footer">
            {{ $shipments->links() }}
        </div>
    </div>
@endif
@endsection
