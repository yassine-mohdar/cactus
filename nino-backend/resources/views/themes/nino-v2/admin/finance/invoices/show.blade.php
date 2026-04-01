@extends('admin.layouts.app')

@section('title', 'Invoice ' . $invoice->invoice_number)

@section('header')
    <x-nino.page-header
        title="Invoice {{ $invoice->invoice_number }}"
        subtitle="Printable billing document generated from the order snapshot and payment lifecycle.">
        <x-slot:actions>
            <x-nino.status-badge tone="success">{{ $invoice->status->label() }}</x-nino.status-badge>
            <x-nino.button href="{{ route('admin.orders.show', $order) }}" variant="secondary">Back to Order</x-nino.button>
            <x-nino.button href="{{ route('admin.orders.invoice', $order) }}" variant="primary">Download PDF</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
<div class="detail-grid">
    <div class="detail-main">
        <x-nino.detail-section title="Invoice Summary" subtitle="Commercial totals locked from the order snapshot.">
            <div class="detail-meta-grid">
                <div>
                    <p class="detail-kicker">Invoice Number</p>
                    <p class="detail-value-mono">{{ $invoice->invoice_number }}</p>
                </div>
                <div>
                    <p class="detail-kicker">Order Reference</p>
                    <p class="detail-value-mono">{{ $order->reference_number }}</p>
                </div>
                <div>
                    <p class="detail-kicker">Issued At</p>
                    <p class="detail-value">{{ $invoice->issued_at?->format('M j, Y H:i') ?? '—' }}</p>
                </div>
                <div>
                    <p class="detail-kicker">Paid At</p>
                    <p class="detail-value">{{ $invoice->paid_at?->format('M j, Y H:i') ?? '—' }}</p>
                </div>
            </div>

            <div class="mt-6 w-full overflow-hidden rounded-3xl border border-[rgba(120,112,95,0.14)]">
                <table class="divide-y divide-[rgba(120,112,95,0.14)] text-sm" style="width: 100%; table-layout: fixed;">
                    <colgroup>
                        <col style="width: 40%;">
                        <col style="width: 22%;">
                        <col style="width: 10%;">
                        <col style="width: 14%;">
                        <col style="width: 14%;">
                    </colgroup>
                    <thead class="bg-[#FAF8F4]">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-[#61706B]">Item</th>
                            <th class="px-4 py-3 text-left font-semibold text-[#61706B]">SKU</th>
                            <th class="px-4 py-3 text-right font-semibold text-[#61706B]">Qty</th>
                            <th class="px-4 py-3 text-right font-semibold text-[#61706B]">Unit</th>
                            <th class="px-4 py-3 text-right font-semibold text-[#61706B]">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[rgba(120,112,95,0.10)] bg-white">
                        @foreach($order->lineItems as $item)
                            <tr>
                                <td class="px-4 py-3 align-top text-[#1E2B27]">
                                    <div class="font-semibold">{{ $item->product_name }}</div>
                                    @if($item->variant_name)
                                        <div class="mt-1 text-xs text-[#61706B]">{{ $item->variant_name }}</div>
                                    @endif
                                </td>
                                <td class="break-words px-4 py-3 align-top font-mono text-xs text-[#61706B]">{{ $item->sku ?: 'NO-SKU' }}</td>
                                <td class="px-4 py-3 align-top text-right text-[#1E2B27]">{{ $item->quantity }}</td>
                                <td class="px-4 py-3 align-top text-right font-mono text-[#1E2B27]">{{ number_format((float) $item->unit_price, 2) }}</td>
                                <td class="px-4 py-3 align-top text-right font-mono font-semibold text-[#1E2B27]">{{ number_format((float) $item->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6 ml-auto max-w-sm space-y-3 text-sm">
                <div class="flex items-center justify-between text-[#61706B]">
                    <span>Subtotal</span>
                    <span class="font-mono text-[#1E2B27]">{{ number_format((float) $invoice->subtotal, 2) }} {{ $invoice->currency }}</span>
                </div>
                <div class="flex items-center justify-between text-[#61706B]">
                    <span>Shipping</span>
                    <span class="font-mono text-[#1E2B27]">{{ number_format((float) $invoice->shipping_total, 2) }} {{ $invoice->currency }}</span>
                </div>
                <div class="flex items-center justify-between text-[#61706B]">
                    <span>Tax</span>
                    <span class="font-mono text-[#1E2B27]">{{ number_format((float) $invoice->tax_total, 2) }} {{ $invoice->currency }}</span>
                </div>
                @if((float) $invoice->discount_total > 0)
                    <div class="flex items-center justify-between text-[#61706B]">
                        <span>Discount</span>
                        <span class="font-mono text-[#C45143]">-{{ number_format((float) $invoice->discount_total, 2) }} {{ $invoice->currency }}</span>
                    </div>
                @endif
                <div class="flex items-center justify-between border-t border-[rgba(120,112,95,0.14)] pt-3">
                    <span class="font-semibold text-[#1E2B27]">Grand Total</span>
                    <span class="font-mono text-base font-semibold text-[#1E2B27]">{{ number_format((float) $invoice->grand_total, 2) }} {{ $invoice->currency }}</span>
                </div>
            </div>
        </x-nino.detail-section>
    </div>

    <div class="detail-sidebar">
        <x-nino.detail-section title="Bill To" subtitle="Billing identity captured from the order.">
            @if($billingAddress)
                <div class="space-y-2 text-sm text-[#61706B]">
                    <p class="font-semibold text-[#1E2B27]">{{ $billingAddress->first_name }} {{ $billingAddress->last_name }}</p>
                    <p>{{ $billingAddress->address_line_1 }}</p>
                    @if($billingAddress->address_line_2)
                        <p>{{ $billingAddress->address_line_2 }}</p>
                    @endif
                    <p>{{ $billingAddress->city }}, {{ $billingAddress->postal_code }}</p>
                    <p>{{ $billingAddress->country }}</p>
                </div>
            @else
                <x-nino.empty-state title="No billing address" description="No billing snapshot is attached to this order." icon="location_off" />
            @endif
        </x-nino.detail-section>

        <x-nino.detail-section title="Ship To" subtitle="Shipping snapshot carried into the invoice context.">
            @if($shippingAddress)
                <div class="space-y-2 text-sm text-[#61706B]">
                    <p class="font-semibold text-[#1E2B27]">{{ $shippingAddress->first_name }} {{ $shippingAddress->last_name }}</p>
                    <p>{{ $shippingAddress->address_line_1 }}</p>
                    @if($shippingAddress->address_line_2)
                        <p>{{ $shippingAddress->address_line_2 }}</p>
                    @endif
                    <p>{{ $shippingAddress->city }}, {{ $shippingAddress->postal_code }}</p>
                    <p>{{ $shippingAddress->country }}</p>
                </div>
            @else
                <x-nino.empty-state title="No shipping address" description="No shipping snapshot is attached to this order." icon="location_off" />
            @endif
        </x-nino.detail-section>
    </div>
</div>
@endsection
