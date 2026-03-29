<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed — NinoWorld</title>
    @include('admin.partials.theme-assets')
</head>
<body class="min-h-screen bg-gray-50 text-slate-900">
    <main class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
        <section class="rounded-3xl bg-white p-8 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Checkout complete</p>
            <h1 class="mt-3 text-3xl font-bold text-slate-900">Your order is confirmed.</h1>
            <p class="mt-3 text-sm text-slate-600">
                Reference <span class="font-semibold text-slate-900">{{ $summary['reference_number'] }}</span> has been created successfully.
            </p>

            <div class="mt-8 grid gap-4 md:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Order total</p>
                    <p class="mt-2 text-xl font-bold text-slate-900">{{ $summary['grand_total'] }} {{ $summary['currency'] }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Items</p>
                    <p class="mt-2 text-xl font-bold text-slate-900">{{ number_format($summary['items_count']) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Payment</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ str($summary['payment_method'])->replace('_', ' ')->title() }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Status</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ $summary['status_label'] }}</p>
                </div>
            </div>
        </section>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1.2fr,0.8fr]">
            <section class="rounded-3xl bg-white p-8 shadow-sm">
                <h2 class="text-2xl font-bold text-slate-900">Order summary</h2>
                <div class="mt-6 space-y-4">
                    @foreach($order->lineItems as $item)
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $item->product_name }}</p>
                                    @if($item->variant_name)
                                        <p class="mt-1 text-sm text-slate-600">{{ $item->variant_name }}</p>
                                    @endif
                                    @if($item->sku)
                                        <p class="mt-1 text-xs uppercase tracking-[0.2em] text-slate-500">{{ $item->sku }}</p>
                                    @endif
                                </div>
                                <div class="text-right text-sm text-slate-600">
                                    <p class="font-semibold text-slate-900">{{ number_format((float) $item->line_total, 2) }} {{ $summary['currency'] }}</p>
                                    <p>{{ $item->quantity }} × {{ number_format((float) $item->unit_price, 2) }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <dl class="mt-6 space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    <div class="flex items-center justify-between gap-4">
                        <dt>Subtotal</dt>
                        <dd class="font-semibold text-slate-900">{{ $summary['subtotal'] }} {{ $summary['currency'] }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt>Tax</dt>
                        <dd class="font-semibold text-slate-900">{{ $summary['tax_total'] }} {{ $summary['currency'] }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt>Shipping</dt>
                        <dd class="font-semibold text-slate-900">{{ $summary['shipping_total'] }} {{ $summary['currency'] }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt>Discount</dt>
                        <dd class="font-semibold text-slate-900">{{ $summary['discount_total'] }} {{ $summary['currency'] }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-t border-slate-200 pt-3">
                        <dt class="font-semibold text-slate-900">Grand total</dt>
                        <dd class="font-bold text-slate-900">{{ $summary['grand_total'] }} {{ $summary['currency'] }}</dd>
                    </div>
                </dl>
            </section>

            <div class="space-y-6">
                <section class="rounded-3xl bg-white p-8 shadow-sm">
                    <h2 class="text-2xl font-bold text-slate-900">Shipping snapshot</h2>
                    @if($order->shippingAddress)
                        <div class="mt-6 rounded-2xl border border-slate-200 p-4 text-sm text-slate-600">
                            <p class="font-semibold text-slate-900">{{ $order->shippingAddress->first_name }} {{ $order->shippingAddress->last_name }}</p>
                            <p class="mt-2">{{ $order->shippingAddress->address_line_1 }}</p>
                            @if($order->shippingAddress->address_line_2)
                                <p>{{ $order->shippingAddress->address_line_2 }}</p>
                            @endif
                            <p>{{ $order->shippingAddress->city }}, {{ $order->shippingAddress->state }} {{ $order->shippingAddress->postal_code }}</p>
                            <p>{{ $order->shippingAddress->country }}</p>
                        </div>
                    @endif
                </section>

                <section class="rounded-3xl bg-white p-8 shadow-sm">
                    <h2 class="text-2xl font-bold text-slate-900">Next step</h2>
                    @if($account['created_via_guest_checkout'])
                        <p class="mt-4 text-sm text-slate-600">We created a secure customer account for <span class="font-semibold text-slate-900">{{ $account['email'] }}</span>. Check your inbox for your password setup link.</p>
                        <div class="mt-6 flex flex-wrap gap-3">
                            <a href="{{ route('customer.login') }}" class="inline-flex rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white">Customer login</a>
                            <a href="{{ $lookup['public_status_url'] }}" class="inline-flex rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">View public order status</a>
                        </div>
                    @else
                        <p class="mt-4 text-sm text-slate-600">Your order is linked to your customer account and can be reviewed there at any time.</p>
                        <div class="mt-6 flex flex-wrap gap-3">
                            <a href="{{ $account['account_home_url'] }}" class="inline-flex rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white">Open my account</a>
                            <a href="{{ $account['orders_url'] }}" class="inline-flex rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">View my orders</a>
                            <a href="{{ $account['order_detail_url'] }}" class="inline-flex rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Track this order</a>
                        </div>
                    @endif
                </section>

                <section class="rounded-3xl bg-white p-8 shadow-sm">
                    <h2 class="text-2xl font-bold text-slate-900">Tracking & lookup</h2>
                    <p class="mt-4 text-sm text-slate-600">Use reference <span class="font-semibold text-slate-900">{{ $summary['reference_number'] }}</span> to reopen this status page or the public tracking data endpoint.</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ $lookup['public_status_url'] }}" class="inline-flex rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Open status page</a>
                        <a href="{{ $lookup['tracking_api_url'] }}" class="inline-flex rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Open tracking data</a>
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
