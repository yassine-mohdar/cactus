@extends('admin.layouts.app')

@php
    $shipmentTone = match ($shipment->status->value) {
        'delivered' => 'success',
        'dispatched', 'in_transit' => 'info',
        'failed_delivery', 'returned', 'cancelled' => 'danger',
        default => 'warning',
    };
@endphp

@section('title', 'Shipment #' . $shipment->id)

@section('header')
    <x-nino.page-header
        title="Shipment #{{ $shipment->id }}"
        subtitle="Track carrier handoff, status changes, and exception handling for order {{ $shipment->order->reference_number }}.">
        <x-slot:actions>
            <x-nino.status-badge :tone="$shipmentTone">{{ $shipment->status->label() }}</x-nino.status-badge>
            @if($shipment->has_delivery_issue)
                <x-nino.status-badge tone="danger">Issue flagged</x-nino.status-badge>
            @endif
            <x-nino.button href="{{ route('admin.shipping.shipments.index') }}" variant="secondary" icon="arrow_back">Back to Shipments</x-nino.button>
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

    <div class="detail-grid">
        <div class="detail-main">
            <x-nino.detail-section title="Shipment Snapshot" subtitle="Core order, carrier, and package metadata needed by fulfillment and support.">
                <x-nino.entity-detail-grid class="xl:grid-cols-3">
                    <div>
                        <p class="detail-kicker">Order</p>
                        <a href="{{ route('admin.orders.show', $shipment->order) }}" class="table-link table-mono mt-2 inline-flex">{{ $shipment->order->reference_number }}</a>
                    </div>
                    <div>
                        <p class="detail-kicker">Customer</p>
                        <p class="detail-value">{{ trim(($shipment->order->customer?->first_name ?? 'Guest').' '.($shipment->order->customer?->last_name ?? '')) }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Order Total</p>
                        <p class="detail-value-mono">{{ number_format((float) $shipment->order->grand_total, 2) }} {{ $shipment->order->currency }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Method</p>
                        <p class="detail-value">{{ $shipment->shippingMethod?->name ?? $shipment->carrier_service ?? 'Not assigned' }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Carrier</p>
                        <p class="detail-value">{{ $shipment->carrier_name ?: 'Not assigned' }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Tracking</p>
                        @if($shipment->tracking_number)
                            @if($link = $shipment->getTrackingLink())
                                <a href="{{ $link }}" target="_blank" rel="noreferrer" class="table-link table-mono mt-2 inline-flex">{{ $shipment->tracking_number }}</a>
                            @else
                                <p class="detail-value-mono">{{ $shipment->tracking_number }}</p>
                            @endif
                        @else
                            <p class="detail-value">Pending assignment</p>
                        @endif
                    </div>
                    <div>
                        <p class="detail-kicker">Packages</p>
                        <p class="detail-value-mono">{{ number_format((int) $shipment->package_count) }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Weight</p>
                        <p class="detail-value">{{ $shipment->weight ? number_format((float) $shipment->weight, 2).' kg' : 'Not captured' }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Dimensions</p>
                        <p class="detail-value">{{ $shipment->dimensions ?: 'Not captured' }}</p>
                    </div>
                </x-nino.entity-detail-grid>
            </x-nino.detail-section>

            @if($shippingAddress)
                <x-nino.detail-section title="Shipping Destination" subtitle="Address snapshot captured at checkout for this shipment.">
                    <div class="space-y-2 text-sm text-[#61706B]">
                        <p class="font-semibold text-[#1E2B27]">{{ $shippingAddress->first_name }} {{ $shippingAddress->last_name }}</p>
                        <p>{{ $shippingAddress->address_line_1 }}</p>
                        @if($shippingAddress->address_line_2)
                            <p>{{ $shippingAddress->address_line_2 }}</p>
                        @endif
                        <p>{{ $shippingAddress->city }}, {{ $shippingAddress->postal_code }}</p>
                        <p>{{ $shippingAddress->country }}</p>
                        @if($shippingAddress->phone)
                            <a href="tel:{{ $shippingAddress->phone }}" class="inline-flex items-center gap-2 text-[#245848]">
                                <span class="material-symbols-outlined text-sm">call</span>
                                {{ $shippingAddress->phone }}
                            </a>
                        @endif
                    </div>
                </x-nino.detail-section>
            @endif

            <x-nino.detail-section title="Tracking Operations" subtitle="Maintain live tracking details and carrier information for this shipment.">
                <form action="{{ route('admin.shipping.shipments.tracking', $shipment) }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="entity-section-grid">
                        <div>
                            <label class="filter-label" for="tracking-number">Tracking Number</label>
                            <input id="tracking-number" type="text" name="tracking_number" value="{{ $shipment->tracking_number }}" class="input-field" placeholder="Tracking number">
                        </div>
                        <div>
                            <label class="filter-label" for="carrier-name">Carrier Name</label>
                            <input id="carrier-name" type="text" name="carrier_name" value="{{ $shipment->carrier_name }}" class="input-field" placeholder="Carrier">
                        </div>
                        <div class="md:col-span-2">
                            <label class="filter-label" for="tracking-url">Tracking URL</label>
                            <input id="tracking-url" type="url" name="tracking_url" value="{{ $shipment->tracking_url }}" class="input-field" placeholder="Optional public tracking URL">
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <x-nino.button type="submit" variant="primary">Update Tracking</x-nino.button>
                    </div>
                </form>
            </x-nino.detail-section>

            @if(count($availableTransitions) > 0)
                <x-nino.detail-section title="Status Transition" subtitle="Advance the shipment through the warehouse and carrier workflow.">
                    <form action="{{ route('admin.shipping.shipments.status', $shipment) }}" method="POST" class="space-y-4">
                        @csrf

                        <div class="flex flex-wrap gap-2">
                            @foreach($availableTransitions as $transition)
                                <label class="cursor-pointer">
                                    <input type="radio" name="status" value="{{ $transition->value }}" class="peer sr-only" required>
                                    <span class="tab-pill border border-[rgba(120,112,95,0.14)] peer-checked:tab-pill-active">
                                        {{ $transition->label() }}
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        <div class="entity-section-grid">
                            <div class="md:col-span-2">
                                <label class="filter-label" for="status-notes">Notes</label>
                                <textarea id="status-notes" name="notes" rows="3" class="input-field" placeholder="Optional operational note..."></textarea>
                            </div>

                            @if(in_array(\App\Modules\Shipping\Enums\ShipmentStatus::DISPATCHED, $availableTransitions, true))
                                <div>
                                    <label class="filter-label" for="dispatch-tracking-number">Tracking Number</label>
                                    <input id="dispatch-tracking-number" type="text" name="tracking_number" class="input-field" placeholder="Assign on dispatch">
                                </div>
                                <div>
                                    <label class="filter-label" for="dispatch-tracking-url">Tracking URL</label>
                                    <input id="dispatch-tracking-url" type="url" name="tracking_url" class="input-field" placeholder="Optional URL">
                                </div>
                            @endif

                            @if(in_array(\App\Modules\Shipping\Enums\ShipmentStatus::FAILED_DELIVERY, $availableTransitions, true))
                                <div class="md:col-span-2">
                                    <label class="filter-label" for="failure-reason">Failure Reason</label>
                                    <input id="failure-reason" type="text" name="failure_reason" class="input-field" placeholder="Customer absent, wrong address, carrier incident...">
                                </div>
                            @endif
                        </div>

                        <div class="flex justify-end">
                            <x-nino.button type="submit" variant="primary">Update Status</x-nino.button>
                        </div>
                    </form>
                </x-nino.detail-section>
            @endif
        </div>

        <div class="detail-sidebar">
            <x-nino.detail-section title="Delivery Issue" subtitle="Flag a problem or resolve an active delivery exception.">
                @if($shipment->has_delivery_issue)
                    <x-nino.inline-alert tone="danger" title="Open delivery issue" class="mb-4">
                        {{ $shipment->delivery_issue_notes }}
                        @if($shipment->failure_reason)
                            <span class="block font-mono text-xs uppercase tracking-[0.18em]">Reason: {{ $shipment->failure_reason }}</span>
                        @endif
                    </x-nino.inline-alert>

                    <form action="{{ route('admin.shipping.shipments.issue', $shipment) }}" method="POST" class="space-y-3">
                        @csrf
                        <input type="hidden" name="notes" value="Resolved by admin">
                        <x-nino.button type="submit" variant="secondary" class="w-full justify-center">Resolve Issue</x-nino.button>
                    </form>
                @else
                    <form action="{{ route('admin.shipping.shipments.issue', $shipment) }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="filter-label" for="delivery-issue-notes">Issue Summary</label>
                            <textarea id="delivery-issue-notes" name="delivery_issue_notes" rows="3" required class="input-field" placeholder="Describe the delivery issue..."></textarea>
                        </div>
                        <div>
                            <label class="filter-label" for="delivery-failure-reason">Failure Reason</label>
                            <input id="delivery-failure-reason" type="text" name="failure_reason" class="input-field" placeholder="Optional reason visible to the ops team">
                        </div>
                        <x-nino.button type="submit" variant="danger" class="w-full justify-center">Flag Issue</x-nino.button>
                    </form>
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Operational Timeline" subtitle="Key timestamps and all recorded shipment transitions.">
                <x-nino.entity-detail-grid class="mb-5">
                    <div>
                        <p class="detail-kicker">Created</p>
                        <p class="detail-value">{{ $shipment->created_at->format('M d, Y H:i') }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Packed</p>
                        <p class="detail-value">{{ $shipment->packed_at?->format('M d, Y H:i') ?? 'Pending' }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Dispatched</p>
                        <p class="detail-value">{{ $shipment->dispatched_at?->format('M d, Y H:i') ?? 'Pending' }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Delivered</p>
                        <p class="detail-value">{{ $shipment->delivered_at?->format('M d, Y H:i') ?? 'Pending' }}</p>
                    </div>
                </x-nino.entity-detail-grid>

                <x-nino.activity-timeline emptyTitle="No shipment history yet" emptyDescription="The status history will appear here as this shipment moves through the workflow.">
                    @foreach($shipment->statusHistory as $entry)
                        <div class="timeline-item">
                            <span class="timeline-marker {{ $entry->status_to === 'failed_delivery' ? 'bg-[#C45143]' : ($entry->status_to === 'delivered' ? 'bg-[#1F7A4E]' : 'bg-[#245848]') }}"></span>
                            <div class="timeline-panel">
                                <p class="timeline-title">
                                    @if($entry->status_from)
                                        {{ ucfirst(str_replace('_', ' ', $entry->status_from)) }}
                                        <span class="mx-1 text-[#A0ACA7]">→</span>
                                    @endif
                                    {{ ucfirst(str_replace('_', ' ', $entry->status_to)) }}
                                </p>
                                @if($entry->notes)
                                    <p class="timeline-copy">{{ $entry->notes }}</p>
                                @endif
                                <p class="timeline-meta">{{ $entry->changed_by_name ?? 'System' }} · {{ $entry->created_at->format('M d, H:i') }}</p>
                            </div>
                        </div>
                    @endforeach
                </x-nino.activity-timeline>

                @if($shipment->internal_notes)
                    <div class="mt-5 border-t border-[rgba(120,112,95,0.14)] pt-4">
                        <p class="detail-kicker">Internal Notes</p>
                        <p class="detail-note mt-2">{{ $shipment->internal_notes }}</p>
                    </div>
                @endif
            </x-nino.detail-section>
        </div>
    </div>
@endsection
