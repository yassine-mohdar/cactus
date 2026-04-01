@extends('admin.layouts.app')

@php
    $shipmentTone = match ($shipment->status->value) {
        'delivered' => 'success',
        'dispatched', 'in_transit' => 'info',
        'failed_delivery', 'returned', 'cancelled' => 'danger',
        default => 'warning',
    };
    $trackingLink = $shipment->getTrackingLink();
    $isSendit = $shipment->usesSendit();
    $customerName = trim(($shipment->order->customer?->first_name ?? '').' '.($shipment->order->customer?->last_name ?? ''));
    $customerName = $customerName !== '' ? $customerName : ($shipment->order->customer?->name ?? 'Guest');
    $locationParts = array_values(array_filter([
        $shippingAddress?->city,
        $shippingAddress?->state,
    ]));
    $locationLine = implode(' · ', array_unique($locationParts));
    if (filled($shippingAddress?->postal_code)) {
        $locationLine = trim($locationLine !== '' ? $locationLine.' · '.$shippingAddress?->postal_code : (string) $shippingAddress?->postal_code);
    }
    if (filled($shippingAddress?->country)) {
        $locationLine = trim($locationLine !== '' ? $locationLine.' · '.$shippingAddress?->country : (string) $shippingAddress?->country);
    }
    $timelineMoments = [
        ['label' => 'Created', 'value' => $shipment->created_at->format('M d, Y H:i')],
        ['label' => 'Packed', 'value' => $shipment->packed_at?->format('M d, Y H:i') ?? 'Pending'],
        ['label' => 'Dispatched', 'value' => $shipment->dispatched_at?->format('M d, Y H:i') ?? 'Pending'],
        ['label' => 'Delivered', 'value' => $shipment->delivered_at?->format('M d, Y H:i') ?? 'Pending'],
    ];
    $transitionMeta = collect($availableTransitions ?? [])->mapWithKeys(function ($transition) {
        $meta = match ($transition->value) {
            'packed' => [
                'label' => $transition->label(),
                'description' => 'Confirm picking and packing are complete before the parcel is handed to the courier.',
                'tone' => 'warehouse',
            ],
            'dispatched' => [
                'label' => $transition->label(),
                'description' => 'Mark the shipment as handed off to the carrier and record tracking details if needed.',
                'tone' => 'carrier',
            ],
            'in_transit' => [
                'label' => $transition->label(),
                'description' => 'Use when the parcel is moving in the carrier network and customer tracking is live.',
                'tone' => 'carrier',
            ],
            'delivered' => [
                'label' => $transition->label(),
                'description' => 'Close the fulfillment loop after final delivery confirmation.',
                'tone' => 'success',
            ],
            'failed_delivery' => [
                'label' => $transition->label(),
                'description' => 'Record an unsuccessful delivery attempt and capture the failure reason below.',
                'tone' => 'danger',
            ],
            'returned' => [
                'label' => $transition->label(),
                'description' => 'Use when the parcel comes back from the carrier and requires reverse-logistics handling.',
                'tone' => 'danger',
            ],
            'cancelled' => [
                'label' => $transition->label(),
                'description' => 'Cancel the shipment before completion. Use only when fulfillment should stop.',
                'tone' => 'danger',
            ],
            default => [
                'label' => $transition->label(),
                'description' => 'Advance the shipment to the next operational step.',
                'tone' => 'neutral',
            ],
        };

        return [$transition->value => $meta];
    })->all();
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
            <x-nino.button href="{{ route('admin.orders.show', $shipment->order) }}" variant="secondary" icon="receipt_long">View Order</x-nino.button>
            @if($trackingLink)
                <x-nino.button href="{{ $trackingLink }}" variant="outline" icon="travel_explore" :navigate="false" target="_blank" rel="noreferrer">
                    Open Tracking Link
                </x-nino.button>
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
                <x-nino.entity-detail-grid class="xl:grid-cols-4">
                    <div>
                        <p class="detail-kicker">Order</p>
                        <a href="{{ route('admin.orders.show', $shipment->order) }}" class="table-link table-mono mt-2 inline-flex">{{ $shipment->order->reference_number }}</a>
                    </div>
                    <div>
                        <p class="detail-kicker">Order Status</p>
                        <p class="detail-value">{{ $shipment->order->status->label() }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Customer</p>
                        <p class="detail-value">{{ $customerName }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Payment Method</p>
                        <p class="detail-value">{{ $shipment->order->resolvedPaymentMethodLabel() ?: 'Not captured' }}</p>
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
                    @if($isSendit)
                        <div>
                            <p class="detail-kicker">Sendit Parcel Code</p>
                            <p class="detail-value-mono">{{ $shipment->external_reference ?: 'Not created' }}</p>
                        </div>
                        <div>
                            <p class="detail-kicker">External Status</p>
                            <p class="detail-value">{{ $shipment->external_status ?: 'Not synced' }}</p>
                        </div>
                        <div>
                            <p class="detail-kicker">Label File</p>
                            <p class="detail-value">{{ $shipment->label_url ? 'Stored and ready' : 'Will refresh from Sendit on download' }}</p>
                        </div>
                        <div>
                            <p class="detail-kicker">Last Provider Sync</p>
                            <p class="detail-value">{{ $shipment->last_provider_sync_at?->format('M d, Y H:i') ?? 'Never' }}</p>
                        </div>
                    @endif
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
                        <p class="font-semibold text-[#1E2B27]">{{ trim($shippingAddress->first_name.' '.$shippingAddress->last_name) }}</p>
                        <p>{{ $shippingAddress->address_line_1 }}</p>
                        @if($shippingAddress->address_line_2)
                            <p>{{ $shippingAddress->address_line_2 }}</p>
                        @endif
                        @if($locationLine !== '')
                            <p>{{ $locationLine }}</p>
                        @endif
                        @if($shippingAddress->phone)
                            <a href="tel:{{ $shippingAddress->phone }}" class="inline-flex items-center gap-2 text-[#245848]">
                                <span class="material-symbols-outlined text-sm">call</span>
                                {{ $shippingAddress->phone }}
                            </a>
                        @endif
                    </div>
                </x-nino.detail-section>
            @endif

            <x-nino.detail-section
                :title="$isSendit ? 'Customer Tracking' : 'Tracking Operations'"
                :subtitle="$isSendit ? 'Use this share-ready Sendit link for customers while keeping the shipment identifiers current.' : 'Maintain live tracking details and carrier information for this shipment.'">
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
                        @if($isSendit)
                            <div class="md:col-span-2">
                                <label class="filter-label" for="tracking-url">Customer Tracking Link</label>
                                <div class="flex flex-col gap-3 rounded-2xl border border-[rgba(36,88,72,0.12)] bg-[#F8FBF9] px-4 py-4 lg:flex-row lg:items-center lg:justify-between">
                                    <div class="min-w-0">
                                        <input id="tracking-url" type="text" value="{{ $trackingLink }}" readonly class="input-field border-0 bg-transparent px-0 py-0 font-mono text-xs text-[#245848] shadow-none focus:ring-0" placeholder="Generated automatically after Sendit parcel creation">
                                        <p class="mt-2 text-xs text-[#61706B]">Share this Sendit delivery URL with the customer and support team.</p>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <x-nino.button href="{{ $trackingLink }}" variant="outline" size="sm" icon="open_in_new" :navigate="false" target="_blank" rel="noreferrer">
                                            Open Link
                                        </x-nino.button>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="md:col-span-2">
                                <label class="filter-label" for="tracking-url">Tracking URL</label>
                                <input id="tracking-url" type="url" name="tracking_url" value="{{ $shipment->tracking_url }}" class="input-field" placeholder="Optional public tracking URL">
                            </div>
                        @endif
                    </div>

                    @if($isSendit)
                        <x-nino.inline-alert tone="info" title="Sendit links are generated automatically">
                            The customer-facing link uses the Sendit delivery page and follows the parcel code. Update the tracking number or parcel by syncing Sendit, not by editing a manual tracking URL.
                        </x-nino.inline-alert>
                    @endif

                    <div class="flex items-center justify-between gap-3">
                        @if($isSendit)
                            <p class="text-xs text-[#7A8A82]">Tracking URL is managed automatically by the Sendit parcel code.</p>
                        @endif
                        <x-nino.button type="submit" variant="primary">Update Tracking</x-nino.button>
                    </div>
                </form>
            </x-nino.detail-section>

            @if($senditContext)
                <x-nino.detail-section title="Sendit Delivery" subtitle="Provider state, label readiness, and courier actions for this parcel.">
                    <div class="space-y-4">
                        <x-nino.entity-detail-grid class="xl:grid-cols-4">
                            <div>
                                <p class="detail-kicker">Carrier Record</p>
                                <p class="detail-value">{{ $senditContext['carrier']?->name ?? 'Sendit' }}</p>
                            </div>
                            <div>
                                <p class="detail-kicker">Connection State</p>
                                <p class="detail-value">{{ $senditContext['configured'] ? 'Configured' : 'Needs credentials' }}</p>
                            </div>
                            <div>
                                <p class="detail-kicker">Parcel Code</p>
                                <p class="detail-value-mono">{{ $shipment->external_reference ?: 'Not created' }}</p>
                            </div>
                            <div>
                                <p class="detail-kicker">External Status</p>
                                <p class="detail-value">{{ $shipment->external_status ?: 'Not synced' }}</p>
                            </div>
                            <div>
                                <p class="detail-kicker">Last Sync</p>
                                <p class="detail-value">{{ $shipment->last_provider_sync_at?->format('M d, Y H:i') ?? 'Never' }}</p>
                            </div>
                            <div>
                                <p class="detail-kicker">Label File</p>
                                <p class="detail-value">{{ $shipment->label_url ? 'Stored and ready' : 'Generated on download' }}</p>
                            </div>
                            <div>
                                <p class="detail-kicker">Documents</p>
                                <p class="detail-value">{{ $shipment->label_url ? 'A4 + thermal ready' : 'Generate labels on demand' }}</p>
                            </div>
                        </x-nino.entity-detail-grid>

                        @if($shipment->provider_error)
                            <x-nino.inline-alert tone="danger" title="Provider reported an issue">
                                {{ $shipment->provider_error }}
                            </x-nino.inline-alert>
                        @endif

                        <div class="flex flex-wrap gap-3">
                            @if(! $shipment->external_reference)
                                <form action="{{ route('admin.shipping.shipments.sendit.create', $shipment) }}" method="POST">
                                    @csrf
                                    <x-nino.button type="submit" variant="primary" icon="outbound">Create Sendit Delivery</x-nino.button>
                                </form>
                            @else
                                <form action="{{ route('admin.shipping.shipments.sendit.update', $shipment) }}" method="POST">
                                    @csrf
                                    <x-nino.button type="submit" variant="secondary" icon="sync">Update Sendit Delivery</x-nino.button>
                                </form>

                                <form action="{{ route('admin.shipping.shipments.sendit.sync', $shipment) }}" method="POST">
                                    @csrf
                                    <x-nino.button type="submit" variant="secondary" icon="autorenew">Sync Sendit Status</x-nino.button>
                                </form>

                                @if($trackingLink)
                                    <x-nino.button href="{{ $trackingLink }}" variant="outline" icon="open_in_new" :navigate="false" target="_blank" rel="noreferrer">
                                        Open Sendit Delivery
                                    </x-nino.button>
                                @endif

                                <x-nino.button href="{{ route('admin.shipping.shipments.sendit.label', [$shipment, 'format' => 'a4']) }}" variant="outline" icon="picture_as_pdf" :navigate="false">
                                    Download A4 Label
                                </x-nino.button>

                                <x-nino.button href="{{ route('admin.shipping.shipments.sendit.label', [$shipment, 'format' => 'thermal']) }}" variant="outline" icon="print" :navigate="false">
                                    Download Thermal Label
                                </x-nino.button>
                            @endif
                        </div>
                    </div>
                </x-nino.detail-section>
            @endif

            @if(count($availableTransitions) > 0)
                <x-nino.detail-section title="Status Transition" subtitle="Advance the shipment through the warehouse and carrier workflow.">
                    <form
                        action="{{ route('admin.shipping.shipments.status', $shipment) }}"
                        method="POST"
                        class="space-y-5"
                        x-data='{
                            selectedStatus: "",
                            transitionMeta: @json($transitionMeta),
                        }'>
                        @csrf

                        <div>
                            <p class="detail-kicker">Choose the next operational state</p>
                            <p class="mt-2 text-sm text-[#61706B]">The selected transition is applied with the notes and shipment data below.</p>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            @foreach($availableTransitions as $transition)
                                @php
                                    $transitionInfo = $transitionMeta[$transition->value] ?? ['description' => 'Advance the shipment to the next operational step.', 'tone' => 'neutral'];
                                @endphp
                                <label class="group cursor-pointer">
                                    <input
                                        type="radio"
                                        name="status"
                                        value="{{ $transition->value }}"
                                        class="peer sr-only"
                                        x-model="selectedStatus"
                                        required>
                                    <span class="relative flex h-full min-h-[110px] flex-col justify-between rounded-2xl border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] px-4 py-4 text-left transition-all duration-150 hover:border-[rgba(36,88,72,0.26)] hover:bg-[#F8FBF9] peer-focus-visible:ring-2 peer-focus-visible:ring-[#245848]/18 peer-checked:border-[#245848] peer-checked:bg-[#ECF4EE] peer-checked:shadow-[0_10px_24px_-18px_rgba(36,88,72,0.35)]">
                                        <span class="flex items-start justify-between gap-3">
                                            <span class="min-w-0">
                                                <span class="block text-sm font-semibold text-[#1E2B27]">{{ $transition->label() }}</span>
                                                <span class="mt-1 block text-xs leading-5 text-[#61706B]">{{ $transitionInfo['description'] }}</span>
                                            </span>
                                            <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-[rgba(120,112,95,0.18)] bg-white text-[#A0ACA7] transition-all duration-150 peer-checked:border-[#245848] peer-checked:bg-[#245848] peer-checked:text-white">
                                                <span class="material-symbols-outlined text-sm">check</span>
                                            </span>
                                        </span>

                                        <span class="mt-4 inline-flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#7A8A82] peer-checked:text-[#245848]">
                                            <span class="inline-block h-2 w-2 rounded-full {{ $transitionInfo['tone'] === 'success' ? 'bg-[#1F7A4E]' : ($transitionInfo['tone'] === 'danger' ? 'bg-[#C45143]' : ($transitionInfo['tone'] === 'carrier' ? 'bg-[#245848]' : 'bg-[#B9A98C]')) }}"></span>
                                            {{ $transitionInfo['tone'] === 'success' ? 'Completion' : ($transitionInfo['tone'] === 'danger' ? 'Exception' : ($transitionInfo['tone'] === 'carrier' ? 'Carrier step' : ($transitionInfo['tone'] === 'warehouse' ? 'Warehouse step' : 'Operational step'))) }}
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        <div
                            x-cloak
                            x-show="selectedStatus && transitionMeta[selectedStatus]"
                            class="rounded-2xl border border-[rgba(36,88,72,0.14)] bg-[#F8FBF9] px-4 py-4">
                            <p class="detail-kicker">Selected transition</p>
                            <p class="mt-2 text-sm font-semibold text-[#1E2B27]" x-text="transitionMeta[selectedStatus]?.label"></p>
                            <p class="mt-1 text-sm text-[#61706B]" x-text="transitionMeta[selectedStatus]?.description"></p>
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

                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs text-[#7A8A82]">Tip: capture the operational reason here so support and finance can understand the change later.</p>
                            <x-nino.button type="submit" variant="primary" x-bind:disabled="!selectedStatus">Apply Transition</x-nino.button>
                        </div>
                    </form>
                </x-nino.detail-section>
            @endif
        </div>

        <div class="detail-sidebar space-y-4">
            <x-nino.detail-section title="Delivery Issue" subtitle="Flag or resolve courier exceptions without leaving this shipment.">
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
                    <div class="mb-4 rounded-2xl border border-[rgba(36,88,72,0.12)] bg-[#F8FBF9] px-4 py-3">
                        <div class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#245848]">
                            <span class="inline-block h-2 w-2 rounded-full bg-[#245848]"></span>
                            No active delivery issue
                        </div>
                        <p class="mt-2 text-sm leading-6 text-[#61706B]">Use this only for real courier exceptions so support and finance can trust the shipment state.</p>
                    </div>

                    <form action="{{ route('admin.shipping.shipments.issue', $shipment) }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label class="filter-label" for="delivery-issue-notes">Issue Summary</label>
                            <textarea id="delivery-issue-notes" name="delivery_issue_notes" rows="2" required class="input-field" placeholder="Describe the delivery issue..."></textarea>
                        </div>
                        <div>
                            <label class="filter-label" for="delivery-failure-reason">Failure Reason</label>
                            <input id="delivery-failure-reason" type="text" name="failure_reason" class="input-field" placeholder="Optional reason visible to the ops team">
                        </div>
                        <x-nino.button type="submit" variant="danger" class="w-full justify-center">Flag Issue</x-nino.button>
                    </form>
                @endif
            </x-nino.detail-section>

            <x-nino.detail-section title="Operational Timeline" subtitle="Compact checkpoint and status history for this parcel.">
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-2">
                    @foreach($timelineMoments as $moment)
                        <div class="rounded-2xl border border-[rgba(120,112,95,0.12)] bg-[#FBFAF7] px-4 py-3">
                            <p class="detail-kicker">{{ $moment['label'] }}</p>
                            <p class="mt-1 text-sm font-semibold text-[#1E2B27]">{{ $moment['value'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5">
                    <p class="detail-kicker">Status History</p>
                </div>

                <x-nino.activity-timeline emptyTitle="No shipment history yet" emptyDescription="The status history will appear here as this shipment moves through the workflow.">
                    @foreach($shipment->statusHistory as $entry)
                        @php
                            $isStateChange = $entry->isStateChange();
                            $entryTone = $entry->status_to === 'failed_delivery'
                                ? 'bg-[#C45143]'
                                : ($entry->status_to === 'delivered' ? 'bg-[#1F7A4E]' : ($isStateChange ? 'bg-[#245848]' : 'bg-[#B9A98C]'));
                        @endphp
                        <div class="flex gap-3">
                            <span class="mt-2 h-2.5 w-2.5 shrink-0 rounded-full {{ $entryTone }}"></span>
                            <div class="min-w-0 flex-1 rounded-xl border border-[rgba(120,112,95,0.12)] bg-[#FBFAF7] px-3.5 py-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-[#1E2B27]">{{ $entry->eventTitle() }}</p>
                                        @if($isStateChange)
                                            <p class="mt-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-[#7A8A82]">
                                                @if($entry->status_from)
                                                    {{ $entry->fromLabel() }}
                                                    <span class="mx-1 text-[#A0ACA7]">→</span>
                                                @endif
                                                {{ $entry->toLabel() }}
                                            </p>
                                        @else
                                            <p class="mt-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-[#7A8A82]">
                                                {{ $entry->toLabel() }} checkpoint
                                            </p>
                                        @endif
                                    </div>
                                    <p class="shrink-0 text-[10px] font-semibold uppercase tracking-[0.16em] text-[#7A8A82]">{{ $entry->created_at->format('M d, H:i') }}</p>
                                </div>
                                <p class="mt-1 text-sm leading-6 text-[#61706B]">{{ $entry->eventCopy() }}</p>
                                <p class="mt-2 text-[10px] font-semibold uppercase tracking-[0.16em] text-[#6A7671]">{{ $entry->changed_by_name ?? 'System' }}</p>
                            </div>
                        </div>
                    @endforeach
                </x-nino.activity-timeline>

                @if($shipment->internal_notes)
                    <div class="mt-4 border-t border-[rgba(120,112,95,0.14)] pt-4">
                        <p class="detail-kicker">Internal Notes</p>
                        <p class="mt-2 rounded-xl border border-dashed border-[rgba(120,112,95,0.18)] bg-[#FBFAF7] px-3.5 py-3 text-sm leading-6 text-[#61706B]">{{ $shipment->internal_notes }}</p>
                    </div>
                @endif
            </x-nino.detail-section>
        </div>
    </div>
@endsection
