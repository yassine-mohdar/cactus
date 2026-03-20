@extends('admin.layouts.app')

@section('title', 'Shipments')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Shipments</h1>
        <p class="text-sm text-slate-500 mt-1">Fulfillment queue and shipment tracking.</p>
    </div>
</div>

@if(session('success'))
    <div class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm border border-green-200">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm border border-red-200">{{ session('error') }}</div>
@endif

{{-- Stats Cards --}}
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <a href="{{ route('admin.shipping.shipments.index', ['status' => 'ready_to_ship']) }}" class="bg-white border border-slate-200 rounded-md p-4 hover:shadow-sm transition-shadow">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Ready to Ship</p>
        <p class="text-2xl font-bold text-blue-600 mt-1">{{ $stats['ready_to_ship'] }}</p>
    </a>
    <a href="{{ route('admin.shipping.shipments.index', ['status' => 'packed']) }}" class="bg-white border border-slate-200 rounded-md p-4 hover:shadow-sm transition-shadow">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Packed</p>
        <p class="text-2xl font-bold text-indigo-600 mt-1">{{ $stats['packed'] }}</p>
    </a>
    <a href="{{ route('admin.shipping.shipments.index', ['status' => 'dispatched']) }}" class="bg-white border border-slate-200 rounded-md p-4 hover:shadow-sm transition-shadow">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">In Transit</p>
        <p class="text-2xl font-bold text-purple-600 mt-1">{{ $stats['in_transit'] }}</p>
    </a>
    <a href="{{ route('admin.shipping.shipments.index', ['issues' => '1']) }}" class="bg-white border border-slate-200 rounded-md p-4 hover:shadow-sm transition-shadow {{ $stats['issues'] > 0 ? 'border-red-300' : '' }}">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Issues</p>
        <p class="text-2xl font-bold text-red-600 mt-1">{{ $stats['issues'] }}</p>
    </a>
    <a href="{{ route('admin.shipping.shipments.index', ['status' => 'returned']) }}" class="bg-white border border-slate-200 rounded-md p-4 hover:shadow-sm transition-shadow">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Returned</p>
        <p class="text-2xl font-bold text-orange-600 mt-1">{{ $stats['returned'] }}</p>
    </a>
</div>

{{-- Filters --}}
<form method="GET" class="flex flex-wrap gap-3 mb-4 items-end">
    <div>
        <label class="block text-xs font-semibold text-slate-500 mb-1">Status</label>
        <select name="status" class="rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20 w-40">
            <option value="">All Statuses</option>
            @foreach(\App\Modules\Shipping\Enums\ShipmentStatus::cases() as $s)
                <option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-semibold text-slate-500 mb-1">Search</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Tracking # or Order ref..." class="rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20 w-56">
    </div>
    <button type="submit" class="btn-primary">Filter</button>
    @if(request()->hasAny(['status', 'search', 'issues']))
        <a href="{{ route('admin.shipping.shipments.index') }}" class="px-4 py-2 text-sm text-slate-500 hover:text-slate-900 transition-colors">Clear</a>
    @endif
</form>

{{-- Shipments Table --}}
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
            <tr class="hover:bg-white-dim/50 transition-colors {{ $shipment->has_delivery_issue ? 'bg-red-50/30' : '' }}">
                <td class="px-4 py-3">
                    <a href="{{ route('admin.orders.show', $shipment->order_id) }}" class="text-slate-900 hover:text-slate-900-dim font-medium">
                        {{ $shipment->order?->reference_number ?? '#'.$shipment->order_id }}
                    </a>
                </td>
                <td class="px-4 py-3 text-slate-500">{{ $shipment->order?->customer?->first_name ?? 'Guest' }} {{ $shipment->order?->customer?->last_name ?? '' }}</td>
                <td class="px-4 py-3 text-slate-900">{{ $shipment->carrier_name ?? '—' }}</td>
                <td class="px-4 py-3">
                    @if($shipment->tracking_number)
                        @if($link = $shipment->getTrackingLink())
                            <a href="{{ $link }}" target="_blank" class="text-slate-900 hover:text-slate-900-dim text-xs font-mono">{{ $shipment->tracking_number }}</a>
                        @else
                            <span class="text-xs font-mono text-slate-900">{{ $shipment->tracking_number }}</span>
                        @endif
                    @else
                        <span class="text-slate-500">—</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $shipment->status->badgeColor() }}">
                        {{ $shipment->status->label() }}
                    </span>
                    @if($shipment->has_delivery_issue)
                        <span class="inline-flex items-center ml-1 px-1.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">⚠</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-xs text-slate-500">{{ $shipment->created_at->format('M d, H:i') }}</td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('admin.shipping.shipments.show', $shipment) }}" class="text-slate-900 hover:text-slate-900-dim text-sm font-medium">View</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="p-8 text-center text-slate-500">
                        <div class="bg-slate-50 border border-dashed border-slate-300 rounded-md py-6 text-[11px] uppercase tracking-widest font-bold">
                            No shipments found.
                        </div>
                    </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $shipments->links() }}
</div>
@endsection
