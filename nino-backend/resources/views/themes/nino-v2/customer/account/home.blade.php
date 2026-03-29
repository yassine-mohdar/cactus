<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account — NinoWorld</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    @include('admin.partials.theme-assets')
</head>
<body data-theme="{{ $adminTheme['key'] ?? 'nino-v2' }}" class="theme-nino-v2 min-h-screen bg-canvas font-body text-ink antialiased">
    @php($recentOrders = $overview['orders']['recent'] ?? [])
    @php($activeOrders = $overview['orders']['active'] ?? [])
    @php($addresses = $overview['addresses'] ?? collect())
    <main class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        <section class="surface-panel p-8 sm:p-10">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-[#7C8C83]">Customer account</p>
                    <h1 class="mt-3 text-4xl font-extrabold tracking-[-0.04em] text-[#17302A]">Welcome back, {{ $overview['profile']['name'] ?: $customer?->name }}</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-[#5D6F66]">Review recent orders, follow active deliveries, and keep your saved shipping and billing details up to date from one secure account workspace.</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('customer.account.orders.index') }}" class="btn-primary">View order history</a>
                    <a href="{{ route('customer.account.addresses.index') }}" class="btn-secondary">Manage addresses</a>
                    <a href="{{ route('customer.account.profile.edit') }}" class="btn-secondary">Edit profile</a>
                    <form method="POST" action="{{ route('customer.logout') }}">
                        @csrf
                        <button type="submit" class="btn-secondary">Sign out</button>
                    </form>
                </div>
            </div>

            @if (session('success'))
                <x-nino.inline-alert tone="success" title="Account updated" class="mt-6">
                    {{ session('success') }}
                </x-nino.inline-alert>
            @endif

            <div class="mt-8 grid gap-4 md:grid-cols-3">
                <article class="metric-card">
                    <span class="metric-label">Email</span>
                    <div class="mt-3 text-sm font-semibold text-[#17302A]">{{ $overview['profile']['email'] }}</div>
                    <p class="metric-subtitle">Primary login identity for this account</p>
                </article>
                <article class="metric-card">
                    <span class="metric-label">Saved addresses</span>
                    <div class="metric-value mt-3">{{ number_format((int) ($overview['summary']['addresses_count'] ?? 0)) }}</div>
                    <p class="metric-subtitle">Billing and shipping entries available at checkout</p>
                </article>
                <article class="metric-card">
                    <span class="metric-label">Active orders</span>
                    <div class="metric-value mt-3">{{ number_format((int) ($overview['summary']['active_orders_count'] ?? 0)) }}</div>
                    <p class="metric-subtitle">Orders still in progress or waiting on delivery</p>
                </article>
            </div>
        </section>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1.35fr,0.95fr]">
            <section class="surface-panel p-6 sm:p-8">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-[#7C8C83]">Recent orders</p>
                        <h2 class="mt-2 text-2xl font-bold tracking-[-0.03em] text-[#17302A]">Order history snapshot</h2>
                    </div>
                    <a href="{{ route('customer.account.orders.index') }}" class="text-sm font-semibold text-[#245848]">See all orders</a>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse($recentOrders as $order)
                        <a href="{{ route('customer.account.orders.show', $order['id']) }}" class="block rounded-3xl border border-[rgba(36,88,72,0.12)] bg-white/70 p-5 transition hover:border-[rgba(36,88,72,0.28)] hover:bg-white">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-[#17302A]">{{ $order['reference_number'] }}</p>
                                    <p class="mt-1 text-xs uppercase tracking-[0.18em] text-[#7C8C83]">{{ $order['status_label'] }}</p>
                                </div>
                                <div class="text-sm text-[#5D6F66] sm:text-right">
                                    <p class="font-semibold text-[#17302A]">{{ $order['grand_total'] }} {{ $order['currency'] }}</p>
                                    <p>{{ $order['items_count'] }} item{{ $order['items_count'] === 1 ? '' : 's' }} • {{ $order['created_at_human'] }}</p>
                                </div>
                            </div>
                        </a>
                    @empty
                        <x-nino.empty-state
                            title="No orders yet"
                            description="Once you place an order, your history and delivery updates will appear here."
                            icon="receipt_long" />
                    @endforelse
                </div>
            </section>

            <div class="space-y-6">
                <section class="surface-panel p-6 sm:p-8">
                    <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-[#7C8C83]">Active tracking</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-[-0.03em] text-[#17302A]">Delivery visibility</h2>

                    <div class="mt-6 space-y-4">
                        @forelse($activeOrders as $order)
                            <a href="{{ route('customer.account.orders.show', $order['id']) }}" class="block rounded-3xl border border-[rgba(36,88,72,0.12)] bg-[#F8FBF9] p-5 transition hover:border-[rgba(36,88,72,0.28)]">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-semibold text-[#17302A]">{{ $order['reference_number'] }}</p>
                                        <p class="mt-1 text-sm text-[#5D6F66]">{{ $order['shipment_status_label'] ?? $order['status_label'] }}</p>
                                    </div>
                                    @if($order['tracking_number'])
                                        <span class="rounded-full bg-[#245848]/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#245848]">{{ $order['tracking_number'] }}</span>
                                    @endif
                                </div>
                            </a>
                        @empty
                            <x-nino.empty-state
                                title="Nothing in transit"
                                description="When an order is being prepared or shipped, tracking visibility will appear here."
                                icon="local_shipping" />
                        @endforelse
                    </div>
                </section>

                <section class="surface-panel p-6 sm:p-8">
                    <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-[#7C8C83]">Saved addresses</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-[-0.03em] text-[#17302A]">Billing and shipping records</h2>

                    <div class="mt-6 space-y-4">
                        @forelse($addresses as $address)
                            <div class="rounded-3xl border border-[rgba(36,88,72,0.12)] bg-white/70 p-5">
                                <div class="flex items-center justify-between gap-4">
                                    <p class="text-sm font-semibold text-[#17302A]">{{ ucfirst($address->type) }}</p>
                                    @if($address->is_default)
                                        <span class="rounded-full bg-[#245848]/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#245848]">Default</span>
                                    @endif
                                </div>
                                <p class="mt-3 text-sm font-medium text-[#17302A]">{{ $address->first_name }} {{ $address->last_name }}</p>
                                <p class="mt-1 text-sm text-[#5D6F66]">{{ $address->address_line_1 }}</p>
                                @if($address->address_line_2)
                                    <p class="text-sm text-[#5D6F66]">{{ $address->address_line_2 }}</p>
                                @endif
                                <p class="text-sm text-[#5D6F66]">{{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}</p>
                                <p class="text-sm text-[#5D6F66]">{{ $address->country }}</p>
                            </div>
                        @empty
                            <x-nino.empty-state
                                title="No saved addresses"
                                description="Billing and shipping addresses saved to your account will appear here."
                                icon="home_pin" />
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
