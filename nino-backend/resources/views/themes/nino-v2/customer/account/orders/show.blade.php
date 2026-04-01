<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $order->reference_number }} — NinoWorld</title>
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
                    <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-[#7C8C83]">Customer order detail</p>
                    <h1 class="mt-3 text-4xl font-extrabold tracking-[-0.04em] text-[#17302A]">{{ $order->reference_number }}</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-[#5D6F66]">Review line items, shipping destination, billing details, and tracking updates for this order.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('customer.account.orders.index') }}" class="btn-secondary">Back to orders</a>
                    @if($invoice)
                        <a href="{{ route('customer.account.orders.invoice.preview', $order) }}" class="btn-secondary">Preview invoice</a>
                        <a href="{{ route('customer.account.orders.invoice', $order) }}" class="btn-primary">Download PDF</a>
                    @endif
                    @if($tracking && $tracking['tracking_url'])
                        <a href="{{ $tracking['tracking_url'] }}" target="_blank" rel="noopener noreferrer" class="btn-primary">Open tracking</a>
                    @endif
                </div>
            </div>

            <div class="mt-8 grid gap-4 md:grid-cols-4">
                <article class="metric-card">
                    <span class="metric-label">Status</span>
                    <div class="mt-3 text-sm font-semibold text-[#17302A]">{{ $summary['status_label'] }}</div>
                </article>
                <article class="metric-card">
                    <span class="metric-label">Grand total</span>
                    <div class="mt-3 text-sm font-semibold text-[#17302A]">{{ $summary['grand_total'] }} {{ $summary['currency'] }}</div>
                </article>
                <article class="metric-card">
                    <span class="metric-label">Items</span>
                    <div class="mt-3 text-sm font-semibold text-[#17302A]">{{ $summary['items_count'] }}</div>
                </article>
                <article class="metric-card">
                    <span class="metric-label">Placed</span>
                    <div class="mt-3 text-sm font-semibold text-[#17302A]">{{ $summary['created_at_human'] }}</div>
                </article>
            </div>
        </section>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1.2fr,0.8fr]">
            <section class="surface-panel p-6 sm:p-8">
                <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-[#7C8C83]">Items</p>
                <h2 class="mt-2 text-2xl font-bold tracking-[-0.03em] text-[#17302A]">What’s in this order</h2>
                <div class="mt-6 space-y-4">
                    @forelse($order->lineItems as $item)
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
                                    <p>{{ $item->quantity }} × {{ number_format((float) $item->unit_price, 2) }} {{ $order->currency }}</p>
                                    <p class="font-semibold text-[#17302A]">{{ number_format((float) $item->line_total, 2) }} {{ $order->currency }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <x-nino.empty-state
                            title="No line items recorded"
                            description="This order does not currently expose a customer-visible item snapshot."
                            icon="inventory_2" />
                    @endforelse
                </div>
            </section>

            <div class="space-y-6">
                <section class="surface-panel p-6 sm:p-8">
                    <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-[#7C8C83]">Tracking</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-[-0.03em] text-[#17302A]">Shipment visibility</h2>
                    @if($tracking)
                        <div class="mt-6 space-y-4">
                            <div class="rounded-3xl border border-[rgba(36,88,72,0.12)] bg-white/70 p-5">
                                <p class="text-sm font-semibold text-[#17302A]">{{ $tracking['status_label'] }}</p>
                                <p class="mt-2 text-sm text-[#5D6F66]">{{ $tracking['carrier'] ?: 'Carrier pending' }}</p>
                                @if($tracking['tracking_number'])
                                    <p class="mt-2 font-mono text-sm text-[#17302A]">{{ $tracking['tracking_number'] }}</p>
                                @endif
                            </div>
                            @foreach($tracking['timeline'] as $event)
                                <div class="rounded-3xl border border-[rgba(36,88,72,0.12)] bg-[#F8FBF9] p-5">
                                    <p class="text-sm font-semibold text-[#17302A]">{{ $event['to'] }}</p>
                                    <p class="mt-1 text-sm text-[#5D6F66]">{{ $event['at'] }}</p>
                                    @if($event['notes'])
                                        <p class="mt-2 text-sm text-[#5D6F66]">{{ $event['notes'] }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-6">
                            <x-nino.empty-state
                                title="Tracking not available yet"
                                description="A shipment has not been created for this order yet, so there is no carrier timeline to show."
                                icon="local_shipping" />
                        </div>
                    @endif
                </section>

                <section class="surface-panel p-6 sm:p-8">
                    <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-[#7C8C83]">Addresses</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-[-0.03em] text-[#17302A]">Shipping and billing</h2>
                    <div class="mt-6 grid gap-4">
                        @if($shippingAddress)
                            <div class="rounded-3xl border border-[rgba(36,88,72,0.12)] bg-white/70 p-5">
                                <p class="text-sm font-semibold text-[#17302A]">Shipping address</p>
                                <p class="mt-3 text-sm text-[#5D6F66]">{{ $shippingAddress->first_name }} {{ $shippingAddress->last_name }}</p>
                                <p class="text-sm text-[#5D6F66]">{{ $shippingAddress->address_line_1 }}</p>
                                @if($shippingAddress->address_line_2)
                                    <p class="text-sm text-[#5D6F66]">{{ $shippingAddress->address_line_2 }}</p>
                                @endif
                                <p class="text-sm text-[#5D6F66]">{{ $shippingAddress->city }}, {{ $shippingAddress->postal_code }}</p>
                                <p class="text-sm text-[#5D6F66]">{{ $shippingAddress->country }}</p>
                            </div>
                        @endif
                        @if($billingAddress)
                            <div class="rounded-3xl border border-[rgba(36,88,72,0.12)] bg-white/70 p-5">
                                <p class="text-sm font-semibold text-[#17302A]">Billing address</p>
                                <p class="mt-3 text-sm text-[#5D6F66]">{{ $billingAddress->first_name }} {{ $billingAddress->last_name }}</p>
                                <p class="text-sm text-[#5D6F66]">{{ $billingAddress->address_line_1 }}</p>
                                @if($billingAddress->address_line_2)
                                    <p class="text-sm text-[#5D6F66]">{{ $billingAddress->address_line_2 }}</p>
                                @endif
                                <p class="text-sm text-[#5D6F66]">{{ $billingAddress->city }}, {{ $billingAddress->postal_code }}</p>
                                <p class="text-sm text-[#5D6F66]">{{ $billingAddress->country }}</p>
                            </div>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
