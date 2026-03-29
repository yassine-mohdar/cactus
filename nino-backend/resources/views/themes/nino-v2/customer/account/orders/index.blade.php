<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History — NinoWorld</title>
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
                    <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-[#7C8C83]">Customer account</p>
                    <h1 class="mt-3 text-4xl font-extrabold tracking-[-0.04em] text-[#17302A]">Order history</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-[#5D6F66]">Review every order linked to your customer account, inspect details, and jump into shipment tracking when available.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('customer.account.home') }}" class="btn-secondary">Back to account</a>
                    <form method="POST" action="{{ route('customer.logout') }}">
                        @csrf
                        <button type="submit" class="btn-secondary">Sign out</button>
                    </form>
                </div>
            </div>

            <div class="mt-8 grid gap-4 md:grid-cols-3">
                <article class="metric-card">
                    <span class="metric-label">Total orders</span>
                    <div class="metric-value mt-3">{{ number_format((int) ($summary['total_orders'] ?? 0)) }}</div>
                </article>
                <article class="metric-card">
                    <span class="metric-label">Active</span>
                    <div class="metric-value mt-3">{{ number_format((int) ($summary['active_orders'] ?? 0)) }}</div>
                </article>
                <article class="metric-card">
                    <span class="metric-label">Delivered</span>
                    <div class="metric-value mt-3">{{ number_format((int) ($summary['delivered_orders'] ?? 0)) }}</div>
                </article>
            </div>
        </section>

        <section class="surface-panel mt-8 p-6 sm:p-8">
            <div class="space-y-4">
                @forelse($orders as $order)
                    <a href="{{ route('customer.account.orders.show', $order) }}" class="block rounded-3xl border border-[rgba(36,88,72,0.12)] bg-white/70 p-5 transition hover:border-[rgba(36,88,72,0.28)] hover:bg-white">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-[#17302A]">{{ $order->reference_number }}</p>
                                <p class="mt-1 text-xs uppercase tracking-[0.18em] text-[#7C8C83]">{{ $order->status->label() }}</p>
                            </div>
                            <div class="grid gap-1 text-sm text-[#5D6F66] lg:text-right">
                                <p class="font-semibold text-[#17302A]">{{ number_format((float) $order->grand_total, 2) }} {{ $order->currency }}</p>
                                <p>{{ $order->lineItems->sum('quantity') }} item{{ $order->lineItems->sum('quantity') === 1 ? '' : 's' }}</p>
                                <p>{{ $order->created_at?->format('M j, Y H:i') }}</p>
                                @if($order->shipment?->tracking_number)
                                    <p class="font-medium text-[#245848]">Tracking: {{ $order->shipment->tracking_number }}</p>
                                @endif
                            </div>
                        </div>
                    </a>
                @empty
                    <x-nino.empty-state
                        title="No order history yet"
                        description="Orders linked to your customer account will appear here once checkout is completed."
                        icon="receipt_long" />
                @endforelse
            </div>

            @if(method_exists($orders, 'links'))
                <div class="mt-6">
                    {{ $orders->links() }}
                </div>
            @endif
        </section>
    </main>
</body>
</html>
