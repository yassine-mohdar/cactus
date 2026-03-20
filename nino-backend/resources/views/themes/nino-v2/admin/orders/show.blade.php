@extends('admin.layouts.app')

@php
    $orderTone = match ($order->status->value) {
        'delivered' => 'success',
        'shipped' => 'info',
        'failed', 'cancelled', 'refunded' => 'danger',
        'preparing', 'pending', 'awaiting_payment' => 'warning',
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
                    <p class="detail-value">{{ $order->payment_method ?: 'Not captured' }}</p>
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
                    <p>{{ $shippingAddress->city }}, {{ $shippingAddress->postal_code }}</p>
                    <p class="font-medium text-[#1E2B27]">{{ $shippingAddress->country }}</p>
                    @if($shippingAddress->phone)
                        <a href="tel:{{ $shippingAddress->phone }}" class="inline-flex items-center gap-2 pt-2 text-[#245848]">
                            <span class="material-symbols-outlined text-sm">call</span>
                            {{ $shippingAddress->phone }}
                        </a>
                    @endif
                </div>
            </x-nino.detail-section>
        @endif

        @if($order->customer_notes)
            <x-nino.detail-section title="Order Notes" subtitle="Captured from the customer at checkout.">
                <p class="detail-note">"{{ $order->customer_notes }}"</p>
            </x-nino.detail-section>
        @endif
    </div>
</div>
@endsection
