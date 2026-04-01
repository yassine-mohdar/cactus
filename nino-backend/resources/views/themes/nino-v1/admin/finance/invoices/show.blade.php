@extends('admin.layouts.app')

@section('title', 'Invoice ' . $invoice->invoice_number)

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-slate-500">Invoice</p>
            <h1 class="text-2xl font-semibold text-slate-900">{{ $invoice->invoice_number }}</h1>
            <p class="mt-1 text-sm text-slate-500">Order {{ $order->reference_number }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.orders.show', $order) }}" class="btn-secondary">Back to Order</a>
            <a href="{{ route('admin.orders.invoice', $order) }}" class="btn-primary">Download PDF</a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.2fr,0.8fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-slate-500">
                        <th class="py-2">Item</th>
                        <th class="py-2">SKU</th>
                        <th class="py-2 text-right">Qty</th>
                        <th class="py-2 text-right">Unit</th>
                        <th class="py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->lineItems as $item)
                        <tr class="border-b border-slate-100">
                            <td class="py-3 text-slate-900">{{ $item->product_name }}</td>
                            <td class="py-3 font-mono text-xs text-slate-500">{{ $item->sku ?: 'NO-SKU' }}</td>
                            <td class="py-3 text-right text-slate-900">{{ $item->quantity }}</td>
                            <td class="py-3 text-right font-mono text-slate-900">{{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="py-3 text-right font-mono font-semibold text-slate-900">{{ number_format((float) $item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-6 ml-auto max-w-sm space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span class="font-mono text-slate-900">{{ number_format((float) $invoice->subtotal, 2) }} {{ $invoice->currency }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Shipping</span><span class="font-mono text-slate-900">{{ number_format((float) $invoice->shipping_total, 2) }} {{ $invoice->currency }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Tax</span><span class="font-mono text-slate-900">{{ number_format((float) $invoice->tax_total, 2) }} {{ $invoice->currency }}</span></div>
                @if((float) $invoice->discount_total > 0)
                    <div class="flex justify-between"><span class="text-slate-500">Discount</span><span class="font-mono text-red-600">-{{ number_format((float) $invoice->discount_total, 2) }} {{ $invoice->currency }}</span></div>
                @endif
                <div class="flex justify-between border-t border-slate-200 pt-2"><span class="font-semibold text-slate-900">Grand Total</span><span class="font-mono font-semibold text-slate-900">{{ number_format((float) $invoice->grand_total, 2) }} {{ $invoice->currency }}</span></div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">Billing</h2>
                @if($billingAddress)
                    <div class="mt-3 space-y-1 text-sm text-slate-600">
                        <p class="font-medium text-slate-900">{{ $billingAddress->first_name }} {{ $billingAddress->last_name }}</p>
                        <p>{{ $billingAddress->address_line_1 }}</p>
                        @if($billingAddress->address_line_2)<p>{{ $billingAddress->address_line_2 }}</p>@endif
                        <p>{{ $billingAddress->city }}, {{ $billingAddress->postal_code }}</p>
                        <p>{{ $billingAddress->country }}</p>
                    </div>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">Invoice Meta</h2>
                <div class="mt-3 space-y-2 text-sm text-slate-600">
                    <p><span class="font-medium text-slate-900">Issued:</span> {{ $invoice->issued_at?->format('M j, Y H:i') ?? '—' }}</p>
                    <p><span class="font-medium text-slate-900">Paid:</span> {{ $invoice->paid_at?->format('M j, Y H:i') ?? '—' }}</p>
                    <p><span class="font-medium text-slate-900">Status:</span> {{ $invoice->status->label() }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
