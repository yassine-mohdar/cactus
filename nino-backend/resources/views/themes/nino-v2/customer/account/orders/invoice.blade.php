<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $invoice->invoice_number }} — NinoWorld</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    @include('admin.partials.theme-assets')
</head>
<body data-theme="{{ $adminTheme['key'] ?? 'nino-v2' }}" class="theme-nino-v2 min-h-screen bg-canvas font-body text-ink antialiased">
    <main class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        <section class="surface-panel p-8 sm:p-10">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-[#7C8C83]">Invoice</p>
                    <h1 class="mt-3 text-4xl font-extrabold tracking-[-0.04em] text-[#17302A]">{{ $invoice->invoice_number }}</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-[#5D6F66]">Printable billing document for order {{ $order->reference_number }}.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('customer.account.orders.show', $order) }}" class="btn-secondary">Back to order</a>
                    <a href="{{ route('customer.account.orders.invoice', $order) }}" class="btn-primary">Download PDF</a>
                </div>
            </div>

            <div class="mt-8 grid gap-4 md:grid-cols-4">
                <article class="metric-card">
                    <span class="metric-label">Invoice status</span>
                    <div class="mt-3 text-sm font-semibold text-[#17302A]">{{ $invoice->status->label() }}</div>
                </article>
                <article class="metric-card">
                    <span class="metric-label">Issued</span>
                    <div class="mt-3 text-sm font-semibold text-[#17302A]">{{ $invoice->issued_at?->format('M j, Y') ?? '—' }}</div>
                </article>
                <article class="metric-card">
                    <span class="metric-label">Paid</span>
                    <div class="mt-3 text-sm font-semibold text-[#17302A]">{{ $invoice->paid_at?->format('M j, Y') ?? '—' }}</div>
                </article>
                <article class="metric-card">
                    <span class="metric-label">Grand total</span>
                    <div class="mt-3 text-sm font-semibold text-[#17302A]">{{ number_format((float) $invoice->grand_total, 2) }} {{ $invoice->currency }}</div>
                </article>
            </div>
        </section>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1.2fr,0.8fr]">
            <section class="surface-panel p-6 sm:p-8">
                <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-[#7C8C83]">Invoice lines</p>
                <h2 class="mt-2 text-2xl font-bold tracking-[-0.03em] text-[#17302A]">Billed items</h2>
                <div class="mt-6 space-y-4">
                    @foreach($order->lineItems as $item)
                        <div class="rounded-3xl border border-[rgba(36,88,72,0.12)] bg-white/70 p-5">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-[#17302A]">{{ $item->product_name }}</p>
                                    @if($item->variant_name)
                                        <p class="mt-1 text-sm text-[#5D6F66]">{{ $item->variant_name }}</p>
                                    @endif
                                    @if($item->sku)
                                        <p class="mt-1 text-xs uppercase tracking-[0.18em] text-[#7C8C83]">{{ $item->sku }}</p>
                                    @endif
                                </div>
                                <div class="text-sm text-[#5D6F66] sm:text-right">
                                    <p>{{ $item->quantity }} × {{ number_format((float) $item->unit_price, 2) }} {{ $invoice->currency }}</p>
                                    <p class="font-semibold text-[#17302A]">{{ number_format((float) $item->line_total, 2) }} {{ $invoice->currency }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8 ml-auto max-w-sm space-y-3 text-sm">
                    <div class="flex justify-between text-[#5D6F66]"><span>Subtotal</span><span class="font-mono text-[#17302A]">{{ number_format((float) $invoice->subtotal, 2) }} {{ $invoice->currency }}</span></div>
                    <div class="flex justify-between text-[#5D6F66]"><span>Shipping</span><span class="font-mono text-[#17302A]">{{ number_format((float) $invoice->shipping_total, 2) }} {{ $invoice->currency }}</span></div>
                    <div class="flex justify-between text-[#5D6F66]"><span>Tax</span><span class="font-mono text-[#17302A]">{{ number_format((float) $invoice->tax_total, 2) }} {{ $invoice->currency }}</span></div>
                    @if((float) $invoice->discount_total > 0)
                        <div class="flex justify-between text-[#5D6F66]"><span>Discount</span><span class="font-mono text-[#C45143]">-{{ number_format((float) $invoice->discount_total, 2) }} {{ $invoice->currency }}</span></div>
                    @endif
                    <div class="flex justify-between border-t border-[rgba(36,88,72,0.12)] pt-3"><span class="font-semibold text-[#17302A]">Grand total</span><span class="font-mono font-semibold text-[#17302A]">{{ number_format((float) $invoice->grand_total, 2) }} {{ $invoice->currency }}</span></div>
                </div>
            </section>

            <div class="space-y-6">
                <section class="surface-panel p-6 sm:p-8">
                    <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-[#7C8C83]">Billing</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-[-0.03em] text-[#17302A]">Bill to</h2>
                    @if($billingAddress)
                        <div class="mt-6 text-sm text-[#5D6F66]">
                            <p class="font-semibold text-[#17302A]">{{ $billingAddress->first_name }} {{ $billingAddress->last_name }}</p>
                            <p>{{ $billingAddress->address_line_1 }}</p>
                            @if($billingAddress->address_line_2)<p>{{ $billingAddress->address_line_2 }}</p>@endif
                            <p>{{ $billingAddress->city }}, {{ $billingAddress->postal_code }}</p>
                            <p>{{ $billingAddress->country }}</p>
                        </div>
                    @endif
                </section>

                <section class="surface-panel p-6 sm:p-8">
                    <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-[#7C8C83]">Shipping</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-[-0.03em] text-[#17302A]">Ship to</h2>
                    @if($shippingAddress)
                        <div class="mt-6 text-sm text-[#5D6F66]">
                            <p class="font-semibold text-[#17302A]">{{ $shippingAddress->first_name }} {{ $shippingAddress->last_name }}</p>
                            <p>{{ $shippingAddress->address_line_1 }}</p>
                            @if($shippingAddress->address_line_2)<p>{{ $shippingAddress->address_line_2 }}</p>@endif
                            <p>{{ $shippingAddress->city }}, {{ $shippingAddress->postal_code }}</p>
                            <p>{{ $shippingAddress->country }}</p>
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </main>
</body>
</html>
