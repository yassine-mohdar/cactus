@extends('admin.layouts.app')

@php
    $orderTone = match ($order->status->value) {
        'delivered' => 'success',
        'shipped' => 'info',
        'failed', 'cancelled', 'refunded' => 'danger',
        'refund_pending', 'preparing', 'pending', 'awaiting_payment' => 'warning',
        default => 'neutral',
    };
@endphp

@section('title', 'Order ' . $order->reference_number)

@section('header')
    <x-nino.page-header
        title="Order {{ $order->reference_number }}"
        subtitle="Placed {{ $order->created_at->format('F j, Y \\a\\t g:i A') }}. Review item lines, customer context, and shipment details in one place.">
        <x-slot:actions>
            <x-nino.status-badge :tone="$orderTone">{{ $order->status->label() }}</x-nino.status-badge>
            <x-nino.button href="{{ route('admin.orders.index') }}" variant="secondary" icon="arrow_back">Back to Orders</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
@if(session('warning'))
    <x-nino.inline-alert tone="warning" title="Sendit sync attention needed" class="mb-6">
        {{ session('warning') }}
    </x-nino.inline-alert>
@endif

<div class="detail-grid">
    <div class="detail-main">
        <x-nino.detail-section title="Order Items" subtitle="{{ number_format($order->lineItems->sum('quantity')) }} items across this order." noPadding>
            <x-nino.table>
                <x-slot name="head">
                    <th class="text-left">Product</th>
                    <th class="text-left">SKU</th>
                    <th class="text-right">Unit</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Total</th>
                </x-slot>

                <x-slot name="body">
                    @foreach ($order->lineItems as $item)
                        <tr>
                            <td class="text-left">
                                <p class="font-semibold text-[#1E2B27]">{{ $item->product_name }}</p>
                                @if($item->variant_name)
                                    <p class="mt-1 text-xs text-[#7A8681]">{{ $item->variant_name }}</p>
                                @endif
                            </td>
                            <td class="text-left">
                                <span class="font-mono text-xs text-[#61706B]">{{ $item->sku ?: 'NO-SKU' }}</span>
                            </td>
                            <td class="text-right font-mono text-sm text-[#1E2B27]">{{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="text-right font-mono text-sm text-[#1E2B27]">{{ $item->quantity }}</td>
                            <td class="text-right font-mono text-sm font-semibold text-[#1E2B27]">{{ number_format((float) $item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </x-slot>
            </x-nino.table>

            <div class="border-t border-[rgba(120,112,95,0.14)] bg-[#FAF8F4] px-5 py-4">
                <div class="ml-auto max-w-sm space-y-3 text-sm">
                    <div class="flex items-center justify-between text-[#61706B]">
                        <span>Subtotal</span>
                        <span class="font-mono text-[#1E2B27]">{{ number_format((float) $order->subtotal, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-[#61706B]">
                        <span>Shipping</span>
                        <span class="font-mono text-[#1E2B27]">{{ number_format((float) $order->shipping_total, 2) }}</span>
                    </div>
                    @if((float) $order->discount_total > 0)
                        <div class="flex items-center justify-between text-[#61706B]">
                            <span>Discount</span>
                            <span class="font-mono text-[#C45143]">-{{ number_format((float) $order->discount_total, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between border-t border-[rgba(120,112,95,0.14)] pt-3">
                        <span class="text-sm font-semibold text-[#1E2B27]">Grand Total</span>
                        <span class="font-mono text-base font-semibold text-[#1E2B27]">{{ number_format((float) $order->grand_total, 2) }} {{ $order->currency }}</span>
                    </div>
                </div>
            </div>
        </x-nino.detail-section>

        <x-nino.detail-section title="Operational Timeline" subtitle="Key order, payment, shipment, and note events in one chronological stream.">
            <x-nino.activity-timeline emptyTitle="No timeline events yet" emptyDescription="Timeline entries will appear here once this order moves through payment, shipping, or staff actions.">
                @foreach($timeline as $event)
                    @php
                        $markerClass = match ($event['tone']) {
                            'success' => 'bg-[#1F7A4E]',
                            'danger' => 'bg-[#C45143]',
                            'info' => 'bg-[#245848]',
                            default => 'bg-[#7A8681]',
                        };
                    @endphp
                    <div class="timeline-item">
                        <span class="timeline-marker {{ $markerClass }}"></span>
                        <div class="timeline-panel">
                            <p class="timeline-title">{{ $event['title'] }}</p>
                            @if($event['copy'])
                                <p class="timeline-copy">{{ $event['copy'] }}</p>
                            @endif
                            <p class="timeline-meta">{{ $event['meta'] }}</p>
                        </div>
                    </div>
                @endforeach
            </x-nino.activity-timeline>
        </x-nino.detail-section>

        <x-nino.detail-section title="Internal Notes" subtitle="{{ $notes->count() }} operational notes attached to this order.">
            <form action="{{ route('admin.support.notes.store') }}" method="POST" class="space-y-3 border-b border-[rgba(120,112,95,0.14)] pb-5">
                @csrf
                <input type="hidden" name="notable_type" value="{{ get_class($order) }}">
                <input type="hidden" name="notable_id" value="{{ $order->id }}">
                <textarea name="content" rows="4" class="input-field" placeholder="Add internal note for support, operations, or finance..."></textarea>
                <x-nino.button type="submit" variant="primary">Add Internal Note</x-nino.button>
            </form>

            @if($notes->count())
                <div class="mt-5 space-y-4">
                    @foreach($notes as $note)
                        <div class="{{ $note->is_pinned ? 'surface-panel-active' : 'surface-panel' }}">
                            <p class="whitespace-pre-line text-sm leading-6 text-[#1E2B27]">{{ $note->content }}</p>
                            <div class="mt-3 flex items-center justify-between gap-3 text-[11px] text-[#7A8681]">
                                <span>{{ $note->author?->first_name ?? $note->author?->name ?? 'Unknown' }} · {{ $note->created_at->diffForHumans() }}</span>
                                <div class="flex items-center gap-2">
                                    <form action="{{ route('admin.support.notes.pin', $note) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="inline-flex rounded-md p-1 text-[#61706B] transition-colors hover:bg-[#FCFBF8] hover:text-[#1E2B27]" aria-label="Pin note">
                                            <span class="material-symbols-outlined text-base">{{ $note->is_pinned ? 'keep' : 'push_pin' }}</span>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.support.notes.destroy', $note) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex rounded-md p-1 text-[#C45143] transition-colors hover:bg-[#FCFBF8] hover:text-[#A73D30]" aria-label="Delete note">
                                            <span class="material-symbols-outlined text-base">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="pt-5">
                    <x-nino.empty-state
                        title="No internal notes yet"
                        description="Use internal notes to preserve support and operations context for future staff hand-offs."
                        icon="sticky_note_2" />
                </div>
            @endif
        </x-nino.detail-section>
    </div>

    <div class="detail-sidebar">
        <x-nino.detail-section title="Order Snapshot" subtitle="Primary metadata used by operations, support, and finance.">
            <div class="detail-meta-grid">
                <div>
                    <p class="detail-kicker">Status</p>
                    <x-nino.status-badge :tone="$orderTone" class="mt-2">{{ $order->status->label() }}</x-nino.status-badge>
                </div>
                <div>
                    <p class="detail-kicker">Currency</p>
                    <p class="detail-value-mono">{{ $order->currency }}</p>
                </div>
                <div>
                    <p class="detail-kicker">Payment Method</p>
                    <p class="detail-value">{{ $order->resolvedPaymentMethodLabel() ?: 'Not captured' }}</p>
                </div>
                <div>
                    <p class="detail-kicker">Shipping Method</p>
                    <p class="detail-value">{{ $order->shipping_method ?: 'Not captured' }}</p>
                </div>
                <div>
                    <p class="detail-kicker">Created</p>
                    <p class="detail-value">{{ $order->created_at->format('M j, Y') }}</p>
                </div>
                <div>
                    <p class="detail-kicker">Reference</p>
                    <p class="detail-value-mono">{{ $order->reference_number }}</p>
                </div>
            </div>

            @if($invoice)
                <div class="mt-5 flex items-center justify-between rounded-3xl border border-[rgba(36,88,72,0.12)] bg-[#F8FBF9] px-4 py-4">
                    <div>
                        <p class="detail-kicker">Invoice</p>
                        <p class="detail-value-mono">{{ $invoice->invoice_number }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-nino.button href="{{ route('admin.orders.invoice.preview', $order) }}" size="sm" variant="secondary">Preview</x-nino.button>
                        <x-nino.button href="{{ route('admin.orders.invoice', $order) }}" size="sm" variant="primary" :navigate="false">Download PDF</x-nino.button>
                    </div>
                </div>
            @endif

            @can('orders.override_status')
                <form action="{{ route('admin.orders.status', $order) }}" method="POST" class="mt-5 space-y-4 border-t border-[rgba(120,112,95,0.14)] pt-5">
                    @csrf
                    <div>
                        <label class="form-label" for="order-status">Manual status override</label>
                        <select id="order-status" name="status" class="form-select">
                            @foreach($statuses as $statusOption)
                                <option value="{{ $statusOption->value }}" @selected(old('status', $order->status->value) === $statusOption->value)>{{ $statusOption->label() }}</option>
                            @endforeach
                        </select>
                        @error('status')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label" for="order-status-notes">Reason / note</label>
                        <textarea id="order-status-notes" name="notes" rows="3" class="input-field" placeholder="Explain why this override is needed for support, finance, or operations.">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-note">
                        Manual overrides are audited. Use shipment and payment flows for normal lifecycle progression, and this form only when an operator must correct or force the order state.
                    </div>

                    <x-nino.button type="submit" variant="secondary" size="sm">Update Order Status</x-nino.button>
                </form>
            @endcan
        </x-nino.detail-section>

        <x-nino.detail-section title="Customer" subtitle="Linked profile and primary checkout information.">
            @if($order->customer)
                <div class="space-y-3">
                    <div>
                        <p class="text-sm font-semibold text-[#1E2B27]">{{ $order->customer->full_name ?: $order->customer->name }}</p>
                        <a href="mailto:{{ $order->customer->email }}" class="mt-1 inline-block text-sm text-[#61706B] hover:text-[#245848]">{{ $order->customer->email }}</a>
                    </div>
                    <x-nino.button href="{{ route('admin.customers.show', $order->customer) }}" size="sm" variant="secondary">View Customer</x-nino.button>
                </div>
            @else
                <x-nino.empty-state
                    title="Guest checkout"
                    description="This order was placed without a linked customer account."
                    icon="person_off" />
            @endif
        </x-nino.detail-section>

        @if($shippingAddress)
            <x-nino.detail-section title="Shipping Destination" subtitle="Delivery address captured at checkout.">
                <div class="space-y-2 text-sm text-[#61706B]">
                    <p class="font-semibold text-[#1E2B27]">{{ $shippingAddress->first_name }} {{ $shippingAddress->last_name }}</p>
                    <p>{{ $shippingAddress->address_line_1 }}</p>
                    @if($shippingAddress->address_line_2)
                        <p>{{ $shippingAddress->address_line_2 }}</p>
                    @endif
                    <p>
                        @if($shippingAddress->state && strcasecmp((string) $shippingAddress->state, (string) $shippingAddress->city) !== 0)
                            {{ $shippingAddress->city }} · {{ $shippingAddress->state }}
                        @else
                            {{ $shippingAddress->city }}
                        @endif
                        @if($shippingAddress->postal_code)
                            · {{ $shippingAddress->postal_code }}
                        @endif
                    </p>
                    <p class="font-medium text-[#1E2B27]">{{ $shippingAddress->country }}</p>
                    @if($shippingAddress->phone)
                        <a href="tel:{{ $shippingAddress->phone }}" class="inline-flex items-center gap-2 pt-2 text-[#245848]">
                            <span class="material-symbols-outlined text-sm">call</span>
                            {{ $shippingAddress->phone }}
                        </a>
                    @endif
                </div>

                @canany(['orders.update', 'shipping.update'])
                    <div
                        class="mt-5 border-t border-[rgba(120,112,95,0.14)] pt-5"
                        x-data="ninoDeliveryContactEditor({
                            open: @js($deliveryEditor['open']),
                            selectedDistrictId: @js(old('district_id', $deliveryEditor['selected_district_id'])),
                            districts: @js($deliveryEditor['district_options']),
                            currentShipping: @js((float) $order->shipping_total),
                            currentGrand: @js((float) $order->grand_total),
                            subtotal: @js((float) $order->subtotal),
                            tax: @js((float) $order->tax_total),
                            discount: @js((float) $order->discount_total),
                            currency: @js($order->currency),
                            canReprice: @js($deliveryEditor['can_reprice']),
                        })"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-[#1E2B27]">Delivery details</p>
                                <p class="mt-1 text-xs text-[#61706B]">
                                    {{ $deliveryEditor['sendit_mode']
                                        ? 'Open the editor only when the customer changes their delivery details. Sendit district edits can reprice this order before collection.'
                                        : 'Open the editor only when the customer changes their delivery details.' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($deliveryEditor['sendit_live_sync'])
                                    <span class="inline-flex items-center rounded-full bg-[#EAF3EE] px-3 py-1 text-[11px] font-semibold text-[#245848]">
                                        Live Sendit sync
                                    </span>
                                @endif
                                <button
                                    type="button"
                                    x-on:click="open = !open"
                                    class="inline-flex items-center gap-2 rounded-full border border-[rgba(120,112,95,0.18)] bg-[#FCFBF8] px-3 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-[#17302A] transition-colors hover:border-[rgba(36,88,72,0.22)] hover:bg-[#F7F2E8]"
                                >
                                    <span class="material-symbols-outlined text-[1rem]" x-text="open ? 'close' : 'edit'"></span>
                                    <span>{{ $deliveryEditor['can_reprice'] ? 'Edit & Reprice' : 'Edit Delivery' }}</span>
                                </button>
                            </div>
                        </div>

                        <div x-show="open" x-cloak class="mt-5">
                            <form action="{{ route('admin.orders.delivery-contact', $order) }}" method="POST" class="space-y-5">
                                @csrf
                                @method('PUT')

                                @if($deliveryEditor['sendit_mode'])
                                    <input type="hidden" name="country" value="MA">
                                    <input type="hidden" name="district_id" x-model="selectedDistrictId">
                                @endif

                                <div class="rounded-[1.4rem] border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] p-4">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-[#8C7B65]">Editing workspace</p>
                                            <p class="mt-2 text-sm font-semibold text-[#17302A]">
                                                {{ $deliveryEditor['sendit_mode'] ? 'Sendit district-aware delivery editor' : 'Delivery contact editor' }}
                                            </p>
                                            <p class="mt-1 text-xs text-[#61706B]">
                                                {{ $deliveryEditor['sendit_mode']
                                                    ? 'Pick the exact Sendit district so the payload and shipping quote stay aligned.'
                                                    : 'Update the delivery snapshot stored on this order.' }}
                                            </p>
                                        </div>

                                        @if($deliveryEditor['totals_locked'])
                                            <span class="inline-flex items-center rounded-full bg-[#F7F2E8] px-3 py-1 text-[11px] font-semibold text-[#8C7B65]">
                                                Financial totals locked
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="form-label" for="delivery-first-name">First name</label>
                                        <input id="delivery-first-name" type="text" name="first_name" value="{{ old('first_name', $shippingAddress->first_name) }}" class="input-field" required>
                                        @error('first_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label class="form-label" for="delivery-last-name">Last name</label>
                                        <input id="delivery-last-name" type="text" name="last_name" value="{{ old('last_name', $shippingAddress->last_name) }}" class="input-field" required>
                                        @error('last_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label class="form-label" for="delivery-phone">Phone</label>
                                        <input id="delivery-phone" type="text" name="phone" value="{{ old('phone', $shippingAddress->phone) }}" class="input-field" required>
                                        @error('phone')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label class="form-label" for="delivery-postal-code">Postal code</label>
                                        <input id="delivery-postal-code" type="text" name="postal_code" value="{{ old('postal_code', $shippingAddress->postal_code) }}" class="input-field">
                                        @error('postal_code')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="form-label" for="delivery-address-line-1">Address line 1</label>
                                        <input id="delivery-address-line-1" type="text" name="address_line_1" value="{{ old('address_line_1', $shippingAddress->address_line_1) }}" class="input-field" required>
                                        @error('address_line_1')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="form-label" for="delivery-address-line-2">Address line 2</label>
                                        <input id="delivery-address-line-2" type="text" name="address_line_2" value="{{ old('address_line_2', $shippingAddress->address_line_2) }}" class="input-field">
                                        @error('address_line_2')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                                    </div>

                                    @if($deliveryEditor['sendit_mode'])
                                        <div class="md:col-span-2">
                                            <label class="form-label" for="delivery-district-picker">Destination District</label>
                                            <div
                                                class="relative"
                                                x-data="ninoAddressCombobox({
                                                    selectedValue: selectedDistrictId,
                                                    options: @js($deliveryEditor['district_options']),
                                                    placeholder: 'Search by district or city...',
                                                    badgeLabel: 'Selected district',
                                                    emptyLabel: 'No synced Sendit districts are available yet.',
                                                    onChange(value) { selectedDistrictId = String(value ?? ''); },
                                                })"
                                                x-init="$watch('selectedDistrictId', value => { selectedValue = String(value ?? ''); if (! open) { query = selectedLabel; } })"
                                                x-on:click.outside="resolveTyped(); open = false"
                                            >
                                                <div class="rounded-[1.2rem] border border-[rgba(120,112,95,0.16)] bg-[#FCFBF8] px-3 py-3 shadow-[0_10px_24px_-20px_rgba(23,48,42,0.35)] transition-all" x-bind:class="{ 'ring-2 ring-[#245848]/15 border-[#245848]/35 shadow-[0_18px_34px_-24px_rgba(23,48,42,0.45)]': open }">
                                                    <div class="mb-2 flex items-center justify-between gap-3">
                                                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-[#8C7B65]">Sendit district catalog</p>
                                                        <div class="flex items-center gap-2 text-[11px] font-mono text-[#8C7B65]">
                                                            <span x-show="selectedValue" x-cloak>Selected</span>
                                                            <span x-show="! selectedValue && options.length > 0" x-cloak><span x-text="options.length"></span> loaded</span>
                                                        </div>
                                                    </div>
                                                    <div class="relative">
                                                        <input id="delivery-district-picker" type="text" x-model="query" x-on:focus="open = true; syncActiveIndex()" x-on:input="open = true; if (query !== selectedLabel) { selectedValue = ''; } syncActiveIndex()" x-on:blur="setTimeout(() => { resolveTyped(); open = false; }, 120)" x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="chooseActive()" x-on:keydown.escape.prevent="open = false" class="input-field border-0 bg-transparent px-0 py-0 pr-24 shadow-none focus:ring-0" placeholder="Search by district or city..." autocomplete="off">
                                                        <div class="pointer-events-none absolute inset-y-0 right-16 flex items-center text-[#8C7B65]"><span class="material-symbols-outlined text-[1rem]">search</span></div>
                                                        <button type="button" x-show="selectedValue" x-cloak x-on:click="clear(); selectedDistrictId = '';" class="absolute inset-y-0 right-0 inline-flex items-center text-xs font-bold uppercase tracking-[0.18em] text-[#8C7B65] transition-colors hover:text-[#17302A]">Clear</button>
                                                    </div>
                                                    <p x-show="selectedOption" x-cloak class="mt-2 text-xs font-medium text-[#245848]" x-text="'Selected district: ' + selectedOption.label"></p>
                                                    <p x-show="! selectedValue && query.trim() !== ''" x-cloak class="mt-2 text-xs text-[#8C7B65]">Choose one of the suggested districts to apply this change.</p>
                                                </div>
                                                <div x-show="open" x-cloak class="absolute z-30 mt-2 w-full overflow-hidden rounded-[1.1rem] border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] shadow-[0_18px_38px_-22px_rgba(17,30,26,0.22)]">
                                                    <div class="max-h-72 overflow-y-auto">
                                                        <template x-for="(option, index) in filteredOptions" :key="option.value">
                                                            <button type="button" x-on:click="choose(option)" x-on:mouseenter="activeIndex = index" class="flex w-full items-center justify-between gap-3 border-b border-[rgba(145,133,109,0.08)] px-4 py-3 text-left transition-colors last:border-b-0" x-bind:class="{ 'bg-[#F7F2E8]': activeIndex === index, 'bg-[#EFF4F1]': selectedValue === option.value && activeIndex !== index }">
                                                                <span class="min-w-0">
                                                                    <span class="block text-sm font-semibold text-[#17302A]" x-text="option.label"></span>
                                                                    <span class="mt-1 block text-xs text-[#8C7B65]" x-text="option.eta ? option.eta + ' · ' + money(option.effective_price) : money(option.effective_price)"></span>
                                                                </span>
                                                                <span class="material-symbols-outlined text-[1rem] text-[#8C7B65]">local_shipping</span>
                                                            </button>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                            @error('district_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                                        </div>

                                        <div>
                                            <label class="form-label">Country</label>
                                            <div class="rounded-[1.1rem] border border-[rgba(120,112,95,0.12)] bg-[#F8F4EC] px-4 py-3">
                                                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#8C7B65]">Locked for Sendit</p>
                                                <p class="mt-1 text-sm font-semibold text-[#17302A]">{{ $deliveryEditor['country_flag'] }} Morocco (MA)</p>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="form-label">Derived City</label>
                                            <div class="rounded-[1.1rem] border border-[rgba(120,112,95,0.12)] bg-[#F8F4EC] px-4 py-3">
                                                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#8C7B65]">Synced with district</p>
                                                <p class="mt-1 text-sm font-semibold text-[#17302A]" x-text="selectedDistrict?.city || @js(old('city', $shippingAddress->city)) || 'Select a district'"></p>
                                            </div>
                                        </div>
                                    @else
                                        <div>
                                            <label class="form-label" for="delivery-city">City</label>
                                            <input id="delivery-city" type="text" name="city" value="{{ old('city', $shippingAddress->city) }}" class="input-field" required>
                                            @error('city')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                                        </div>
                                        <div>
                                            <label class="form-label" for="delivery-state">State / district</label>
                                            <input id="delivery-state" type="text" name="state" value="{{ old('state', $shippingAddress->state) }}" class="input-field">
                                            @error('state')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                                        </div>
                                        <div>
                                            <label class="form-label" for="delivery-country">Country</label>
                                            <input id="delivery-country" type="text" name="country" value="{{ old('country', $shippingAddress->country) }}" class="input-field" required>
                                            @error('country')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                                        </div>
                                    @endif
                                </div>

                                @if($deliveryEditor['sendit_mode'])
                                    <div class="grid gap-3 md:grid-cols-2">
                                        <div class="rounded-[1.2rem] border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] p-4">
                                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-[#8C7B65]">Shipping quote preview</p>
                                            <p class="mt-3 text-lg font-black text-[#17302A]" x-text="money(previewShipping)"></p>
                                            <p class="mt-1 text-xs text-[#61706B]" x-text="selectedDistrict?.eta ? 'ETA ' + selectedDistrict.eta : 'Select a district to preview ETA'"></p>
                                        </div>
                                        <div class="rounded-[1.2rem] border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] p-4">
                                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-[#8C7B65]">Projected grand total</p>
                                            <p class="mt-3 text-lg font-black text-[#17302A]" x-text="money(previewGrand)"></p>
                                            <p class="mt-1 text-xs text-[#61706B]" x-text="canReprice ? 'Pending transactions will be updated with the new delivery total.' : 'Financial totals are locked, so this edit only updates the delivery details.'"></p>
                                        </div>
                                    </div>
                                @endif

                                <div class="flex flex-wrap gap-3">
                                    <x-nino.button type="submit" variant="secondary" size="sm">Save Delivery Contact</x-nino.button>
                                    <button type="button" x-on:click="open = false" class="inline-flex items-center rounded-full px-3 py-2 text-sm font-medium text-[#61706B] transition-colors hover:bg-[#F7F2E8] hover:text-[#17302A]">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endcanany
            </x-nino.detail-section>
        @endif

        <x-nino.detail-section title="Shipment Fulfillment" subtitle="Latest carrier handoff, tracking number, and courier documents for this order.">
            @if($latestShipment)
                <div class="space-y-4">
                    <x-nino.entity-detail-grid class="xl:grid-cols-3">
                        <div>
                            <p class="detail-kicker">Shipment Status</p>
                            <p class="detail-value">{{ $latestShipment->status->label() }}</p>
                        </div>
                        <div>
                            <p class="detail-kicker">Carrier</p>
                            <p class="detail-value">{{ $latestShipment->carrier_name ?: ($latestShipment->shippingMethod?->shippingCarrier?->name ?? 'Not assigned') }}</p>
                        </div>
                        <div>
                            <p class="detail-kicker">Method</p>
                            <p class="detail-value">{{ $latestShipment->shippingMethod?->name ?? $latestShipment->carrier_service ?? 'Not assigned' }}</p>
                        </div>
                        <div>
                            <p class="detail-kicker">Tracking Number</p>
                            @if($latestShipment->tracking_number)
                                @if($trackingLink = $latestShipment->getTrackingLink())
                                    <a href="{{ $trackingLink }}" target="_blank" rel="noreferrer" class="table-link table-mono mt-2 inline-flex">{{ $latestShipment->tracking_number }}</a>
                                @else
                                    <p class="detail-value-mono">{{ $latestShipment->tracking_number }}</p>
                                @endif
                            @else
                                <p class="detail-value">Pending assignment</p>
                            @endif
                        </div>
                        <div>
                            <p class="detail-kicker">{{ $latestShipment->usesSendit() ? 'Sendit Parcel Code' : 'External Reference' }}</p>
                            <p class="detail-value-mono">{{ $latestShipment->external_reference ?: 'Not synced yet' }}</p>
                        </div>
                        <div>
                            <p class="detail-kicker">Last Provider Sync</p>
                            <p class="detail-value">{{ $latestShipment->last_provider_sync_at?->format('M d, Y H:i') ?? 'Never' }}</p>
                        </div>
                    </x-nino.entity-detail-grid>

                    @if($latestShipment->provider_error)
                        <x-nino.inline-alert tone="danger" title="Carrier reported an issue">
                            {{ $latestShipment->provider_error }}
                        </x-nino.inline-alert>
                    @endif

                    <div class="flex flex-wrap gap-3">
                        <x-nino.button href="{{ route('admin.shipping.shipments.show', $latestShipment) }}" variant="secondary" icon="local_shipping">View Shipment</x-nino.button>

                        @if($latestShipment->usesSendit() && filled($latestShipment->external_reference))
                            <x-nino.button href="{{ route('admin.shipping.shipments.sendit.label', [$latestShipment, 'format' => 'a4']) }}" variant="outline" icon="picture_as_pdf" :navigate="false">
                                Download A4 Label
                            </x-nino.button>

                            <x-nino.button href="{{ route('admin.shipping.shipments.sendit.label', [$latestShipment, 'format' => 'thermal']) }}" variant="outline" icon="print" :navigate="false">
                                Download Thermal Label
                            </x-nino.button>
                        @endif
                    </div>
                </div>
            @else
                <x-nino.empty-state
                    title="No shipment record yet"
                    description="A shipment will appear here once fulfillment creates or links a carrier handoff."
                    icon="local_shipping" />
            @endif
        </x-nino.detail-section>

        <x-nino.detail-section title="Customer Notes" subtitle="Notes captured from the customer during checkout or follow-up.">
            @if($order->customer_notes)
                <p class="detail-note">"{{ $order->customer_notes }}"</p>
            @else
                <x-nino.empty-state
                    title="No customer notes"
                    description="This order does not currently include customer-authored notes."
                    icon="sms" />
            @endif
        </x-nino.detail-section>

        <x-nino.detail-section title="Recent Audit Activity" subtitle="Latest tracked administrative events recorded against this order.">
            @if($auditTrail->count())
                <div class="space-y-4">
                    @foreach($auditTrail as $audit)
                        <div class="surface-panel">
                            <p class="text-sm font-semibold text-[#1E2B27]">{{ \Illuminate\Support\Str::of($audit->action)->replace('.', ' ')->title() }}</p>
                            @if($audit->notes)
                                <p class="mt-1 text-sm text-[#61706B]">{{ $audit->notes }}</p>
                            @endif
                            @if(!empty($audit->new_values))
                                <p class="mt-2 text-[11px] text-[#7A8681]">
                                    Updated: {{ collect(array_keys($audit->new_values))->map(fn ($key) => \Illuminate\Support\Str::of($key)->replace('_', ' ')->title()->value())->join(', ') }}
                                </p>
                            @endif
                            <p class="mt-2 text-[11px] text-[#7A8681]">{{ $audit->actor_name ?? 'System' }} · {{ $audit->created_at?->format('M d, Y H:i') ?? 'Unknown time' }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <x-nino.empty-state
                    title="No order audit history yet"
                    description="Audit entries will appear here when staff actions explicitly target this order record."
                    icon="history" />
            @endif
        </x-nino.detail-section>
    </div>
</div>
<script>
    if (! window.ninoAddressCombobox) {
        window.ninoAddressCombobox = function (config) {
            return {
                selectedValue: config.selectedValue,
                options: config.options ?? [],
                query: '',
                open: false,
                activeIndex: 0,
                init() {
                    this.query = this.selectedLabel;
                    this.notifyChange(this.selectedValue);

                    this.$watch('selectedValue', () => {
                        if (! this.open) {
                            this.query = this.selectedLabel;
                        }

                        this.notifyChange(this.selectedValue);
                    });
                },
                get selectedOption() {
                    return this.options.find(option => this.sameValue(option.value, this.selectedValue)) ?? null;
                },
                get selectedLabel() {
                    return this.selectedOption?.label ?? '';
                },
                get filteredOptions() {
                    const term = this.query.toLowerCase().trim();

                    if (term === '') {
                        return this.options.slice(0, 80);
                    }

                    return this.options.filter(option => (option.search ?? option.label ?? '').includes(term)).slice(0, 80);
                },
                syncActiveIndex() {
                    const index = this.filteredOptions.findIndex(option => this.sameValue(option.value, this.selectedValue));
                    this.activeIndex = index >= 0 ? index : 0;
                },
                move(step) {
                    if (! this.open) {
                        this.open = true;
                        this.syncActiveIndex();
                        return;
                    }

                    if (this.filteredOptions.length === 0) {
                        this.activeIndex = 0;
                        return;
                    }

                    this.activeIndex = (this.activeIndex + step + this.filteredOptions.length) % this.filteredOptions.length;
                },
                chooseActive() {
                    const option = this.filteredOptions[this.activeIndex] ?? this.filteredOptions[0] ?? null;

                    if (option) {
                        this.choose(option);
                    }
                },
                choose(option) {
                    this.selectedValue = option.value;
                    this.query = option.label;
                    this.open = false;
                },
                resolveTyped() {
                    const term = this.query.toLowerCase().trim();

                    if (term === '') {
                        this.clear();
                        return false;
                    }

                    if (this.selectedOption && this.query.trim() === this.selectedLabel.trim()) {
                        return true;
                    }

                    const exactMatch = this.options.find(option => {
                        const label = String(option.label ?? '').toLowerCase().trim();
                        const search = String(option.search ?? option.label ?? '').toLowerCase();

                        return label === term || search === term;
                    });

                    if (exactMatch) {
                        this.choose(exactMatch);
                        return true;
                    }

                    if (this.filteredOptions.length === 1) {
                        this.choose(this.filteredOptions[0]);
                        return true;
                    }

                    return false;
                },
                clear() {
                    this.selectedValue = '';
                    this.query = '';
                    this.activeIndex = 0;
                    this.open = false;
                },
                notifyChange(value) {
                    if (typeof config.onChange === 'function') {
                        config.onChange(value, this.selectedOption);
                    }
                },
                sameValue(left, right) {
                    return String(left ?? '') === String(right ?? '');
                },
            };
        };
    }

    if (! window.ninoDeliveryContactEditor) {
        window.ninoDeliveryContactEditor = function (config) {
            return {
                open: config.open ?? false,
                selectedDistrictId: String(config.selectedDistrictId ?? ''),
                districts: config.districts ?? [],
                currentShipping: Number(config.currentShipping ?? 0),
                currentGrand: Number(config.currentGrand ?? 0),
                subtotal: Number(config.subtotal ?? 0),
                tax: Number(config.tax ?? 0),
                discount: Number(config.discount ?? 0),
                currency: config.currency ?? 'MAD',
                canReprice: Boolean(config.canReprice),
                get selectedDistrict() {
                    return this.districts.find(option => String(option.value ?? '') === String(this.selectedDistrictId ?? '')) ?? null;
                },
                get previewShipping() {
                    if (! this.canReprice) {
                        return this.currentShipping;
                    }

                    return this.selectedDistrict?.effective_price != null
                        ? Number(this.selectedDistrict.effective_price)
                        : this.currentShipping;
                },
                get previewGrand() {
                    if (! this.canReprice) {
                        return this.currentGrand;
                    }

                    return Math.max(0, this.subtotal + this.tax + this.previewShipping - this.discount);
                },
                money(value) {
                    return `${Number(value ?? 0).toFixed(2)} ${this.currency}`;
                },
            };
        };
    }
</script>
@endsection
