@extends('admin.layouts.app')

@section('title', 'Shipment #' . $shipment->id)

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.shipping.shipments.index') }}" class="text-sm text-slate-900 hover:text-slate-900-dim">← Back to Shipments</a>
    <div class="flex items-center gap-3 mt-2">
        <h1 class="text-2xl font-bold text-slate-900">Shipment #{{ $shipment->id }}</h1>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $shipment->status->badgeColor() }}">{{ $shipment->status->label() }}</span>
        @if($shipment->has_delivery_issue)
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">⚠ Issue Flagged</span>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm border border-green-200">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm border border-red-200">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Left Column: Shipment Details + Actions --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Order Info Card --}}
        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-5">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-3">Order Details</h2>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <span class="text-slate-500">Order:</span>
                    <a href="{{ route('admin.orders.show', $shipment->order) }}" class="text-slate-900 font-medium ml-1">{{ $shipment->order->reference_number }}</a>
                </div>
                <div>
                    <span class="text-slate-500">Customer:</span>
                    <span class="text-slate-900 ml-1">{{ $shipment->order->customer?->first_name ?? 'Guest' }} {{ $shipment->order->customer?->last_name ?? '' }}</span>
                </div>
                <div>
                    <span class="text-slate-500">Total:</span>
                    <span class="text-slate-900 font-medium ml-1">{{ number_format($shipment->order->grand_total, 2) }} {{ $shipment->order->currency }}</span>
                </div>
                <div>
                    <span class="text-slate-500">Method:</span>
                    <span class="text-slate-900 ml-1">{{ $shipment->shippingMethod?->name ?? $shipment->carrier_service ?? '—' }}</span>
                </div>
            </div>

            {{-- Shipping Address --}}
            @php $shippingAddress = $shipment->order->addresses->where('type', 'shipping')->first(); @endphp
            @if($shippingAddress)
            <div class="mt-4 pt-3 border-t border-slate-200">
                <h3 class="text-xs font-semibold text-slate-500 uppercase mb-1">Ship To</h3>
                <p class="text-sm text-slate-900">{{ $shippingAddress->first_name }} {{ $shippingAddress->last_name }}</p>
                <p class="text-sm text-slate-500">{{ $shippingAddress->address_line_1 }}</p>
                @if($shippingAddress->address_line_2)<p class="text-sm text-slate-500">{{ $shippingAddress->address_line_2 }}</p>@endif
                <p class="text-sm text-slate-500">{{ $shippingAddress->city }}, {{ $shippingAddress->postal_code }}</p>
                <p class="text-sm text-slate-500">{{ $shippingAddress->phone }}</p>
            </div>
            @endif
        </div>

        {{-- Carrier & Tracking Card --}}
        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-5">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-3">Carrier & Tracking</h2>
            <div class="grid grid-cols-2 gap-3 text-sm mb-4">
                <div>
                    <span class="text-slate-500">Carrier:</span>
                    <span class="text-slate-900 ml-1">{{ $shipment->carrier_name ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-slate-500">Service:</span>
                    <span class="text-slate-900 ml-1">{{ $shipment->carrier_service ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-slate-500">Tracking #:</span>
                    @if($shipment->tracking_number)
                        @if($link = $shipment->getTrackingLink())
                            <a href="{{ $link }}" target="_blank" class="text-slate-900 font-mono text-xs ml-1">{{ $shipment->tracking_number }} ↗</a>
                        @else
                            <span class="font-mono text-xs text-slate-900 ml-1">{{ $shipment->tracking_number }}</span>
                        @endif
                    @else
                        <span class="text-slate-500 ml-1">Not assigned</span>
                    @endif
                </div>
                <div>
                    <span class="text-slate-500">Packages:</span>
                    <span class="text-slate-900 ml-1">{{ $shipment->package_count }}</span>
                </div>
                @if($shipment->weight)
                <div>
                    <span class="text-slate-500">Weight:</span>
                    <span class="text-slate-900 ml-1">{{ $shipment->weight }} kg</span>
                </div>
                @endif
                @if($shipment->dimensions)
                <div>
                    <span class="text-slate-500">Dimensions:</span>
                    <span class="text-slate-900 ml-1">{{ $shipment->dimensions }}</span>
                </div>
                @endif
            </div>

            {{-- Update Tracking Form --}}
            <form action="{{ route('admin.shipping.shipments.tracking', $shipment) }}" method="POST" class="border-t border-slate-200 pt-3">
                @csrf
                <div class="grid grid-cols-3 gap-2">
                    <input type="text" name="tracking_number" value="{{ $shipment->tracking_number }}" placeholder="Tracking Number" class="rounded-lg border-slate-200 bg-background text-xs text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                    <input type="text" name="carrier_name" value="{{ $shipment->carrier_name }}" placeholder="Carrier" class="rounded-lg border-slate-200 bg-background text-xs text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                    <button type="submit" class="px-3 py-2 text-xs font-medium bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition-colors">Update Tracking</button>
                </div>
            </form>
        </div>

        @if($senditContext)
        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-5">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-3">Sendit Provider</h2>
            <div class="grid grid-cols-2 gap-3 text-sm mb-4">
                <div>
                    <span class="text-slate-500">Carrier:</span>
                    <span class="text-slate-900 ml-1">{{ $senditContext['carrier']?->name ?? 'Sendit' }}</span>
                </div>
                <div>
                    <span class="text-slate-500">Configured:</span>
                    <span class="text-slate-900 ml-1">{{ $senditContext['configured'] ? 'Yes' : 'No' }}</span>
                </div>
                <div>
                    <span class="text-slate-500">External Code:</span>
                    <span class="font-mono text-xs text-slate-900 ml-1">{{ $shipment->external_reference ?: '—' }}</span>
                </div>
                <div>
                    <span class="text-slate-500">External Status:</span>
                    <span class="text-slate-900 ml-1">{{ $shipment->external_status ?: 'Not synced' }}</span>
                </div>
                <div>
                    <span class="text-slate-500">Last Sync:</span>
                    <span class="text-slate-900 ml-1">{{ $shipment->last_provider_sync_at?->format('M d, H:i') ?? 'Never' }}</span>
                </div>
                <div>
                    <span class="text-slate-500">Provider Error:</span>
                    <span class="text-slate-900 ml-1">{{ $shipment->provider_error ?: 'None' }}</span>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 border-t border-slate-200 pt-3">
                @if(! $shipment->external_reference)
                    <form action="{{ route('admin.shipping.shipments.sendit.create', $shipment) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 text-xs font-medium bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition-colors shadow-sm">Create Sendit Delivery</button>
                    </form>
                @else
                    <form action="{{ route('admin.shipping.shipments.sendit.update', $shipment) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 text-xs font-medium bg-white border border-slate-200 text-slate-900 rounded-lg hover:bg-slate-50 transition-colors">Update Delivery</button>
                    </form>
                    <form action="{{ route('admin.shipping.shipments.sendit.sync', $shipment) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 text-xs font-medium bg-white border border-slate-200 text-slate-900 rounded-lg hover:bg-slate-50 transition-colors">Sync Status</button>
                    </form>
                    <a href="{{ route('admin.shipping.shipments.sendit.label', [$shipment, 'format' => 'a4']) }}" class="px-4 py-2 text-xs font-medium bg-white border border-slate-200 text-slate-900 rounded-lg hover:bg-slate-50 transition-colors">Download A4 Label</a>
                    <a href="{{ route('admin.shipping.shipments.sendit.label', [$shipment, 'format' => 'thermal']) }}" class="px-4 py-2 text-xs font-medium bg-white border border-slate-200 text-slate-900 rounded-lg hover:bg-slate-50 transition-colors">Download Thermal Label</a>
                @endif
            </div>
        </div>
        @endif

        {{-- Status Transition Card --}}
        @if(count($availableTransitions) > 0)
        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-5">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-3">Update Status</h2>
            <form action="{{ route('admin.shipping.shipments.status', $shipment) }}" method="POST" class="space-y-3">
                @csrf
                <div class="flex flex-wrap gap-2">
                    @foreach($availableTransitions as $transition)
                    <label class="relative cursor-pointer">
                        <input type="radio" name="status" value="{{ $transition->value }}" class="peer sr-only" required>
                        <span class="block px-4 py-2 text-sm font-medium border border-slate-200 rounded-lg peer-checked:border-slate-900 peer-checked:bg-slate-100 peer-checked:text-slate-900 transition-colors hover:bg-white-dim">
                            {{ $transition->label() }}
                        </span>
                    </label>
                    @endforeach
                </div>
                <div>
                    <textarea name="notes" rows="2" placeholder="Notes (optional)..." class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20"></textarea>
                </div>
                @if(in_array(\App\Modules\Shipping\Enums\ShipmentStatus::DISPATCHED, $availableTransitions))
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" name="tracking_number" placeholder="Tracking # (if new)" class="rounded-lg border-slate-200 bg-background text-xs text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                    <input type="url" name="tracking_url" placeholder="Tracking URL (optional)" class="rounded-lg border-slate-200 bg-background text-xs text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                </div>
                @endif
                @if(in_array(\App\Modules\Shipping\Enums\ShipmentStatus::FAILED_DELIVERY, $availableTransitions))
                <div>
                    <input type="text" name="failure_reason" placeholder="Failure reason (e.g. Customer absent, Wrong address)..." class="w-full rounded-lg border-slate-200 bg-background text-xs text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                </div>
                @endif
                <button type="submit" class="px-5 py-2 text-sm font-medium bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition-colors shadow-sm">Update Status</button>
            </form>
        </div>
        @endif

        {{-- Delivery Issue Card --}}
        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-5">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-3">Delivery Issues</h2>
            @if($shipment->has_delivery_issue)
                <div class="p-3 rounded-lg bg-red-50 border border-red-200 mb-3">
                    <p class="text-sm text-red-700 font-medium">Issue: {{ $shipment->delivery_issue_notes }}</p>
                    @if($shipment->failure_reason)
                        <p class="text-xs text-red-600 mt-1">Reason: {{ $shipment->failure_reason }}</p>
                    @endif
                </div>
                <form action="{{ route('admin.shipping.shipments.issue', $shipment) }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="notes" value="Resolved by admin">
                    <button type="submit" class="px-4 py-2 text-xs font-medium bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">Resolve Issue</button>
                </form>
            @else
                <form action="{{ route('admin.shipping.shipments.issue', $shipment) }}" method="POST" class="space-y-2">
                    @csrf
                    <input type="text" name="delivery_issue_notes" placeholder="Describe the delivery issue..." required class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                    <input type="text" name="failure_reason" placeholder="Failure reason (optional)" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                    <button type="submit" class="px-4 py-2 text-xs font-medium bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">Flag Issue</button>
                </form>
            @endif
        </div>
    </div>

    {{-- Right Column: Timeline --}}
    <div>
        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-5 sticky top-6">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Shipment Timeline</h2>

            {{-- Key timestamps --}}
            <div class="space-y-2 text-xs mb-5 pb-4 border-b border-slate-200">
                <div class="flex justify-between"><span class="text-slate-500">Created:</span><span class="text-slate-900">{{ $shipment->created_at->format('M d, H:i') }}</span></div>
                @if($shipment->packed_at)
                <div class="flex justify-between"><span class="text-slate-500">Packed:</span><span class="text-slate-900">{{ $shipment->packed_at->format('M d, H:i') }}</span></div>
                @endif
                @if($shipment->dispatched_at)
                <div class="flex justify-between"><span class="text-slate-500">Dispatched:</span><span class="text-slate-900">{{ $shipment->dispatched_at->format('M d, H:i') }}</span></div>
                @endif
                @if($shipment->delivered_at)
                <div class="flex justify-between"><span class="text-slate-500">Delivered:</span><span class="text-slate-900 font-medium text-green-700">{{ $shipment->delivered_at->format('M d, H:i') }}</span></div>
                @endif
                @if($shipment->failed_at)
                <div class="flex justify-between"><span class="text-slate-500">Failed:</span><span class="text-slate-900 font-medium text-red-700">{{ $shipment->failed_at->format('M d, H:i') }}</span></div>
                @endif
                @if($shipment->returned_at)
                <div class="flex justify-between"><span class="text-slate-500">Returned:</span><span class="text-slate-900 font-medium text-orange-700">{{ $shipment->returned_at->format('M d, H:i') }}</span></div>
                @endif
                @if($shipment->packedByUser)
                <div class="flex justify-between"><span class="text-slate-500">Packed by:</span><span class="text-slate-900">{{ $shipment->packedByUser->first_name }}</span></div>
                @endif
                @if($shipment->dispatchedByUser)
                <div class="flex justify-between"><span class="text-slate-500">Dispatched by:</span><span class="text-slate-900">{{ $shipment->dispatchedByUser->first_name }}</span></div>
                @endif
            </div>

            {{-- Audit Trail --}}
            <div class="space-y-4 max-h-96 overflow-y-auto">
                @foreach($shipment->statusHistory as $entry)
                <div class="relative pl-6 pb-4 {{ !$loop->last ? 'border-l-2 border-slate-200' : '' }}">
                    <div class="absolute left-0 top-0.5 w-3 h-3 rounded-full border-2 border-slate-900 bg-white -translate-x-[5px]"></div>
                    <p class="text-xs font-medium text-slate-900">
                        @if($entry->status_from)
                            {{ ucfirst(str_replace('_', ' ', $entry->status_from)) }} → {{ ucfirst(str_replace('_', ' ', $entry->status_to)) }}
                        @else
                            {{ ucfirst(str_replace('_', ' ', $entry->status_to)) }}
                        @endif
                    </p>
                    @if($entry->notes)
                        <p class="text-xs text-slate-500 mt-0.5">{{ $entry->notes }}</p>
                    @endif
                    <p class="text-[10px] text-slate-500 mt-1">{{ $entry->changed_by_name ?? 'System' }} · {{ $entry->created_at->format('M d, H:i') }}</p>
                </div>
                @endforeach
            </div>

            @if($shipment->internal_notes)
            <div class="mt-4 pt-3 border-t border-slate-200">
                <h3 class="text-xs font-semibold text-slate-500 uppercase mb-1">Internal Notes</h3>
                <p class="text-sm text-slate-900">{{ $shipment->internal_notes }}</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
