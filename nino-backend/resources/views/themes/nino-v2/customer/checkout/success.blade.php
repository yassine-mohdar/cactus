<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed — NinoWorld</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    @include('admin.partials.theme-assets')
</head>
<body data-theme="{{ $adminTheme['key'] ?? 'nino-v2' }}" class="theme-nino-v2 min-h-screen bg-canvas font-body text-ink antialiased">
    <main class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
        <section class="surface-panel p-8 sm:p-10">
            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-[#7C8C83]">Checkout complete</p>
            <h1 class="mt-3 text-4xl font-extrabold tracking-[-0.04em] text-[#17302A]">Your order is confirmed.</h1>
            <p class="mt-3 max-w-2xl text-sm leading-7 text-[#5D6F66]">
                Reference <span class="font-semibold text-[#17302A]">{{ $summary['reference_number'] }}</span> has been created successfully and is now marked as {{ strtolower($summary['status_label']) }}.
            </p>

            <div class="mt-8 grid gap-4 md:grid-cols-4">
                <article class="metric-card">
                    <span class="metric-label">Order total</span>
                    <div class="metric-value mt-3">{{ $summary['grand_total'] }}</div>
                    <p class="metric-subtitle">{{ $summary['currency'] }}</p>
                </article>
                <article class="metric-card">
                    <span class="metric-label">Items</span>
                    <div class="metric-value mt-3">{{ number_format($summary['items_count']) }}</div>
                    <p class="metric-subtitle">Units captured in this order</p>
                </article>
                <article class="metric-card">
                    <span class="metric-label">Payment</span>
                    <div class="mt-3 text-sm font-semibold text-[#17302A]">{{ str($summary['payment_method'])->replace('_', ' ')->title() }}</div>
                    <p class="metric-subtitle">Selected checkout method</p>
                </article>
                <article class="metric-card">
                    <span class="metric-label">Shipping</span>
                    <div class="mt-3 text-sm font-semibold text-[#17302A]">{{ str($summary['shipping_method'] ?: 'standard')->replace('_', ' ')->title() }}</div>
                    <p class="metric-subtitle">{{ $summary['status_label'] }}</p>
                </article>
            </div>
        </section>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1.2fr,0.8fr]">
            <section class="surface-panel p-6 sm:p-8">
                <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-[#7C8C83]">Order summary</p>
                <h2 class="mt-2 text-2xl font-bold tracking-[-0.03em] text-[#17302A]">Snapshot at checkout</h2>

                <div class="mt-6 space-y-4">
                    @foreach($order->lineItems as $item)
                        <div class="rounded-3xl border border-[rgba(36,88,72,0.12)] bg-white/70 p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold text-[#17302A]">{{ $item->product_name }}</p>
                                    @if($item->variant_name)
                                        <p class="mt-1 text-sm text-[#5D6F66]">{{ $item->variant_name }}</p>
                                    @endif
                                    @if($item->sku)
                                        <p class="mt-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#7C8C83]">{{ $item->sku }}</p>
                                    @endif
                                </div>
                                <div class="text-right text-sm text-[#5D6F66]">
                                    <p class="font-semibold text-[#17302A]">{{ number_format((float) $item->line_total, 2) }} {{ $summary['currency'] }}</p>
                                    <p>{{ $item->quantity }} × {{ number_format((float) $item->unit_price, 2) }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 rounded-3xl border border-[rgba(36,88,72,0.12)] bg-[#F8FBF9] p-5">
                    <dl class="space-y-3 text-sm text-[#5D6F66]">
                        <div class="flex items-center justify-between gap-4">
                            <dt>Subtotal</dt>
                            <dd class="font-semibold text-[#17302A]">{{ $summary['subtotal'] }} {{ $summary['currency'] }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt>Tax</dt>
                            <dd class="font-semibold text-[#17302A]">{{ $summary['tax_total'] }} {{ $summary['currency'] }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt>Shipping</dt>
                            <dd class="font-semibold text-[#17302A]">{{ $summary['shipping_total'] }} {{ $summary['currency'] }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt>Discount</dt>
                            <dd class="font-semibold text-[#17302A]">{{ $summary['discount_total'] }} {{ $summary['currency'] }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 border-t border-[rgba(36,88,72,0.12)] pt-3">
                            <dt class="font-semibold text-[#17302A]">Grand total</dt>
                            <dd class="text-base font-bold text-[#17302A]">{{ $summary['grand_total'] }} {{ $summary['currency'] }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            <div class="space-y-6">
                <section class="surface-panel p-6 sm:p-8">
                    <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-[#7C8C83]">Delivery address</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-[-0.03em] text-[#17302A]">Shipping snapshot</h2>

                    @if($order->shippingAddress)
                        <div class="mt-6 rounded-3xl border border-[rgba(36,88,72,0.12)] bg-white/70 p-5 text-sm text-[#5D6F66]">
                            <p class="font-semibold text-[#17302A]">{{ $order->shippingAddress->first_name }} {{ $order->shippingAddress->last_name }}</p>
                            <p class="mt-2">{{ $order->shippingAddress->address_line_1 }}</p>
                            @if($order->shippingAddress->address_line_2)
                                <p>{{ $order->shippingAddress->address_line_2 }}</p>
                            @endif
                            <p>{{ $order->shippingAddress->city }}, {{ $order->shippingAddress->state }} {{ $order->shippingAddress->postal_code }}</p>
                            <p>{{ $order->shippingAddress->country }}</p>
                            @if($order->shippingAddress->phone)
                                <p class="mt-2">{{ $order->shippingAddress->phone }}</p>
                            @endif
                        </div>
                    @endif
                </section>

                <section class="surface-panel p-6 sm:p-8">
                    <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-[#7C8C83]">Account access</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-[-0.03em] text-[#17302A]">Next step</h2>

                    <div class="mt-6 space-y-4 text-sm leading-7 text-[#5D6F66]">
                        @if($account['created_via_guest_checkout'])
                            <p>We created a secure customer account for <span class="font-semibold text-[#17302A]">{{ $account['email'] }}</span>. Check your inbox for your password setup link before you sign in.</p>
                            <p>Once your password is set, you can return to your account workspace to review this order and any later delivery updates.</p>
                        @else
                            <p>Your order is linked to your customer account. You can continue from your account workspace at any time.</p>
                            <p>Use your account workspace to revisit this order, review shipment progress, and manage future checkout details.</p>
                        @endif
                    </div>

                    <div class="mt-6 flex flex-wrap gap-3">
                        @if($account['account_home_url'])
                            <a href="{{ $account['account_home_url'] }}" class="btn-primary">Open my account</a>
                            <a href="{{ $account['orders_url'] }}" class="btn-secondary">View my orders</a>
                            <a href="{{ $account['order_detail_url'] }}" class="btn-secondary">Track this order</a>
                        @else
                            <a href="{{ route('customer.login') }}" class="btn-primary">Customer login</a>
                            <a href="{{ $lookup['public_status_url'] }}" class="btn-secondary">View public order status</a>
                        @endif
                    </div>
                </section>

                @if($offlinePayment)
                    <section class="surface-panel p-6 sm:p-8">
                        <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-[#7C8C83]">Offline payment</p>
                        <h2 class="mt-2 text-2xl font-bold tracking-[-0.03em] text-[#17302A]">{{ $offlinePayment['checkout_title'] ?? 'Bank transfer instructions' }}</h2>

                        @if(filled($offlinePayment['checkout_description'] ?? null))
                            <p class="mt-4 text-sm leading-7 text-[#5D6F66]">{{ $offlinePayment['checkout_description'] }}</p>
                        @endif

                        <div class="mt-6 rounded-3xl border border-[rgba(36,88,72,0.12)] bg-white/70 p-5 text-sm text-[#5D6F66]">
                            <p class="font-semibold text-[#17302A]">{{ $offlinePayment['method_label'] ?? 'Bank Transfer' }}</p>
                            @if(filled($offlinePayment['bank_name'] ?? null))
                                <p class="mt-3"><span class="font-semibold text-[#17302A]">Bank:</span> {{ $offlinePayment['bank_name'] }}</p>
                            @endif
                            @if(filled($offlinePayment['account_holder'] ?? null))
                                <p><span class="font-semibold text-[#17302A]">Account holder:</span> {{ $offlinePayment['account_holder'] }}</p>
                            @endif
                            @if(filled($offlinePayment['account_number'] ?? null))
                                <p><span class="font-semibold text-[#17302A]">Account number:</span> {{ $offlinePayment['account_number'] }}</p>
                            @endif
                            @if(filled($offlinePayment['iban'] ?? null))
                                <p><span class="font-semibold text-[#17302A]">IBAN:</span> {{ $offlinePayment['iban'] }}</p>
                            @endif
                            @if(filled($offlinePayment['swift_code'] ?? null))
                                <p><span class="font-semibold text-[#17302A]">SWIFT:</span> {{ $offlinePayment['swift_code'] }}</p>
                            @endif
                            @if(filled($offlinePayment['payment_reference'] ?? null))
                                <p class="mt-3"><span class="font-semibold text-[#17302A]">Transfer reference:</span> {{ $offlinePayment['payment_reference'] }}</p>
                            @endif
                            @if(filled($offlinePayment['instructions'] ?? null))
                                <p class="mt-4 whitespace-pre-line">{{ $offlinePayment['instructions'] }}</p>
                            @endif
                        </div>
                    </section>
                @endif

                <section class="surface-panel p-6 sm:p-8">
                    <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-[#7C8C83]">Tracking & lookup</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-[-0.03em] text-[#17302A]">Order access</h2>

                    <div class="mt-6 space-y-4 text-sm leading-7 text-[#5D6F66]">
                        <p>Reference <span class="font-semibold text-[#17302A]">{{ $summary['reference_number'] }}</span> can be used to revisit this confirmation page and the safe public order-status endpoint.</p>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ $lookup['public_status_url'] }}" class="btn-secondary">Open status page</a>
                        <a href="{{ $lookup['tracking_api_url'] }}" class="btn-secondary">Open tracking data</a>
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
