<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $invoice->invoice_number }} — NinoWorld</title>
    @include('admin.partials.theme-assets')
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="mx-auto max-w-5xl px-4 py-10">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-500">Invoice</p>
                <h1 class="text-3xl font-semibold">{{ $invoice->invoice_number }}</h1>
                <p class="mt-1 text-sm text-slate-500">Order {{ $order->reference_number }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('customer.account.orders.show', $order) }}" class="btn-secondary">Back to order</a>
                <a href="{{ route('customer.account.orders.invoice', $order) }}" class="btn-primary">Download PDF</a>
            </div>
        </div>

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
                            <td class="py-3">{{ $item->product_name }}</td>
                            <td class="py-3 font-mono text-xs text-slate-500">{{ $item->sku ?: 'NO-SKU' }}</td>
                            <td class="py-3 text-right">{{ $item->quantity }}</td>
                            <td class="py-3 text-right font-mono">{{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="py-3 text-right font-mono font-semibold">{{ number_format((float) $item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-6 ml-auto max-w-sm space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span class="font-mono">{{ number_format((float) $invoice->subtotal, 2) }} {{ $invoice->currency }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Shipping</span><span class="font-mono">{{ number_format((float) $invoice->shipping_total, 2) }} {{ $invoice->currency }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Tax</span><span class="font-mono">{{ number_format((float) $invoice->tax_total, 2) }} {{ $invoice->currency }}</span></div>
                @if((float) $invoice->discount_total > 0)
                    <div class="flex justify-between"><span class="text-slate-500">Discount</span><span class="font-mono text-red-600">-{{ number_format((float) $invoice->discount_total, 2) }} {{ $invoice->currency }}</span></div>
                @endif
                <div class="flex justify-between border-t border-slate-200 pt-2"><span class="font-semibold">Grand total</span><span class="font-mono font-semibold">{{ number_format((float) $invoice->grand_total, 2) }} {{ $invoice->currency }}</span></div>
            </div>
        </div>
    </main>
</body>
</html>
