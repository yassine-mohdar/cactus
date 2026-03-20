@extends('admin.layouts.app')

@section('title', 'Shipping Reports')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Shipping Reports</h1>
    <p class="text-sm text-slate-500 mt-1">Shipment history, status timeline, and failed/returned queues.</p>
</div>

{{-- Tabs --}}
<div class="flex gap-1 mb-6 border-b border-slate-200">
    <a href="{{ route('admin.shipping.reports', ['tab' => 'history']) }}" class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors {{ $tab === 'history' ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-900' }}">
        Shipment History
    </a>
    <a href="{{ route('admin.shipping.reports', ['tab' => 'timeline']) }}" class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors {{ $tab === 'timeline' ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-900' }}">
        Status Changes Log
    </a>
    <a href="{{ route('admin.shipping.reports', ['tab' => 'failed']) }}" class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors {{ $tab === 'failed' ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-900' }}">
        Failed / Returned
    </a>
</div>

{{-- Tab Content --}}
@if($tab === 'timeline')
    {{-- Status Changes Log --}}
    <div class="bg-white border border-slate-200 rounded-md shadow-sm overflow-hidden">
        <table class="nino-table">
            <thead class="bg-white-dim border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 font-semibold text-slate-900">Time</th>
                    <th class="px-4 py-3 font-semibold text-slate-900">Shipment</th>
                    <th class="px-4 py-3 font-semibold text-slate-900">Order</th>
                    <th class="px-4 py-3 font-semibold text-slate-900">Transition</th>
                    <th class="px-4 py-3 font-semibold text-slate-900">Notes</th>
                    <th class="px-4 py-3 font-semibold text-slate-900">By</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                @forelse($history as $entry)
                <tr class="hover:bg-white-dim/50 transition-colors">
                    <td class="px-4 py-3 text-xs text-slate-500">{{ $entry->created_at->format('M d, H:i:s') }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.shipping.shipments.show', $entry->shipment_id) }}" class="text-slate-900 text-xs font-medium">#{{ $entry->shipment_id }}</a>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-900">{{ $entry->shipment?->order?->reference_number ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs">
                        <span class="text-slate-500">{{ $entry->status_from ? ucfirst(str_replace('_', ' ', $entry->status_from)) : '—' }}</span>
                        <span class="text-slate-900 mx-1">→</span>
                        <span class="font-medium text-slate-900">{{ ucfirst(str_replace('_', ' ', $entry->status_to)) }}</span>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-500 max-w-xs truncate">{{ $entry->notes ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs text-slate-900">{{ $entry->changed_by_name ?? 'System' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="p-8 text-center text-slate-500">
                        <div class="bg-slate-50 border border-dashed border-slate-300 rounded-md py-6 text-[11px] uppercase tracking-widest font-bold">
                            No status changes recorded yet.
                        </div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $history->links() }}</div>

@elseif($tab === 'failed')
    {{-- Failed / Returned Queue --}}
    <div class="bg-white border border-slate-200 rounded-md shadow-sm overflow-hidden">
        <table class="nino-table">
            <thead class="bg-white-dim border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 font-semibold text-slate-900">Order</th>
                    <th class="px-4 py-3 font-semibold text-slate-900">Customer</th>
                    <th class="px-4 py-3 font-semibold text-slate-900">Carrier</th>
                    <th class="px-4 py-3 font-semibold text-slate-900 text-center">Status</th>
                    <th class="px-4 py-3 font-semibold text-slate-900">Reason</th>
                    <th class="px-4 py-3 font-semibold text-slate-900">Updated</th>
                    <th class="px-4 py-3 font-semibold text-slate-900 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                @forelse($shipments as $shipment)
                <tr class="hover:bg-white-dim/50 transition-colors">
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.orders.show', $shipment->order_id) }}" class="text-slate-900 font-medium text-xs">{{ $shipment->order?->reference_number }}</a>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ $shipment->order?->customer?->first_name ?? 'Guest' }}</td>
                    <td class="px-4 py-3 text-xs text-slate-900">{{ $shipment->carrier_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $shipment->status->badgeColor() }}">{{ $shipment->status->label() }}</span>
                    </td>
                    <td class="px-4 py-3 text-xs text-red-600 max-w-xs truncate">{{ $shipment->failure_reason ?? $shipment->delivery_issue_notes ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ $shipment->updated_at->format('M d, H:i') }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.shipping.shipments.show', $shipment) }}" class="text-slate-900 hover:text-slate-900-dim text-sm font-medium">View</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="p-8 text-center text-slate-500">
                        <div class="bg-slate-50 border border-dashed border-slate-300 rounded-md py-6 text-[11px] uppercase tracking-widest font-bold">
                            No failed or returned shipments.
                        </div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $shipments->links() }}</div>

@else
    {{-- Full History --}}
    <div class="bg-white border border-slate-200 rounded-md shadow-sm overflow-hidden">
        <table class="nino-table">
            <thead class="bg-white-dim border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 font-semibold text-slate-900">Order</th>
                    <th class="px-4 py-3 font-semibold text-slate-900">Customer</th>
                    <th class="px-4 py-3 font-semibold text-slate-900">Carrier</th>
                    <th class="px-4 py-3 font-semibold text-slate-900">Tracking</th>
                    <th class="px-4 py-3 font-semibold text-slate-900 text-center">Status</th>
                    <th class="px-4 py-3 font-semibold text-slate-900">Created</th>
                    <th class="px-4 py-3 font-semibold text-slate-900 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                @forelse($shipments as $shipment)
                <tr class="hover:bg-white-dim/50 transition-colors">
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.orders.show', $shipment->order_id) }}" class="text-slate-900 font-medium text-xs">{{ $shipment->order?->reference_number }}</a>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ $shipment->order?->customer?->first_name ?? 'Guest' }}</td>
                    <td class="px-4 py-3 text-xs text-slate-900">{{ $shipment->carrier_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs font-mono text-slate-900">{{ $shipment->tracking_number ?? '—' }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $shipment->status->badgeColor() }}">{{ $shipment->status->label() }}</span>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ $shipment->created_at->format('M d, H:i') }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.shipping.shipments.show', $shipment) }}" class="text-slate-900 hover:text-slate-900-dim text-sm font-medium">View</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="p-8 text-center text-slate-500">
                        <div class="bg-slate-50 border border-dashed border-slate-300 rounded-md py-6 text-[11px] uppercase tracking-widest font-bold">
                            No shipments found.
                        </div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $shipments->links() }}</div>
@endif
@endsection
