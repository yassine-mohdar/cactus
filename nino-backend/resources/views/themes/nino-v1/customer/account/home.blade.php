<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account — NinoWorld</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    @include('admin.partials.theme-assets')
</head>
<body class="min-h-screen bg-[#F4EFE6] font-sans text-slate-900">
    @php($recentOrders = $overview['orders']['recent'] ?? [])
    @php($activeOrders = $overview['orders']['active'] ?? [])
    @php($addresses = $overview['addresses'] ?? collect())
    <main class="mx-auto max-w-6xl px-4 py-10">
        <section class="w-full rounded-[28px] border border-[rgba(15,23,42,0.08)] bg-white/90 p-8 shadow-[0_30px_90px_-54px_rgba(15,23,42,0.35)] sm:p-10">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.32em] text-slate-500">Customer account</p>
                    <h1 class="mt-4 text-4xl font-extrabold tracking-[-0.04em] text-slate-900">Welcome back, {{ $overview['profile']['name'] ?: $customer?->name }}</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">Review recent orders, follow active deliveries, and keep your saved billing and shipping details in one place.</p>
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

            <div class="mt-8 grid gap-4 md:grid-cols-3">
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-500">Email</p>
                    <p class="mt-3 text-sm font-semibold text-slate-900">{{ $overview['profile']['email'] }}</p>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-500">Saved addresses</p>
                    <p class="mt-3 text-3xl font-extrabold tracking-[-0.04em] text-slate-900">{{ number_format((int) ($overview['summary']['addresses_count'] ?? 0)) }}</p>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-500">Active orders</p>
                    <p class="mt-3 text-3xl font-extrabold tracking-[-0.04em] text-slate-900">{{ number_format((int) ($overview['summary']['active_orders_count'] ?? 0)) }}</p>
                </article>
            </div>
        </section>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <section class="rounded-[28px] border border-[rgba(15,23,42,0.08)] bg-white/90 p-8 shadow-[0_30px_90px_-54px_rgba(15,23,42,0.35)]">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-500">Recent orders</p>
                        <h2 class="mt-2 text-2xl font-bold tracking-[-0.03em] text-slate-900">Order history snapshot</h2>
                    </div>
                    <a href="{{ route('customer.account.orders.index') }}" class="text-sm font-semibold text-slate-900">See all</a>
                </div>
                <div class="mt-6 space-y-4">
                    @forelse($recentOrders as $order)
                        <a href="{{ route('customer.account.orders.show', $order['id']) }}" class="block rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $order['reference_number'] }}</p>
                                    <p class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-500">{{ $order['status_label'] }}</p>
                                </div>
                                <div class="text-sm text-slate-600 text-right">
                                    <p class="font-semibold text-slate-900">{{ $order['grand_total'] }} {{ $order['currency'] }}</p>
                                    <p>{{ $order['created_at_human'] }}</p>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="rounded-3xl border border-dashed border-slate-300 bg-white/70 p-6 text-sm text-slate-600">No orders yet.</div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-[28px] border border-[rgba(15,23,42,0.08)] bg-white/90 p-8 shadow-[0_30px_90px_-54px_rgba(15,23,42,0.35)]">
                <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-500">Active tracking</p>
                <h2 class="mt-2 text-2xl font-bold tracking-[-0.03em] text-slate-900">Delivery visibility</h2>
                <div class="mt-6 space-y-4">
                    @forelse($activeOrders as $order)
                        <a href="{{ route('customer.account.orders.show', $order['id']) }}" class="block rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-sm font-semibold text-slate-900">{{ $order['reference_number'] }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $order['shipment_status_label'] ?? $order['status_label'] }}</p>
                            @if($order['tracking_number'])
                                <p class="mt-2 text-sm font-medium text-slate-900">{{ $order['tracking_number'] }}</p>
                            @endif
                        </a>
                    @empty
                        <div class="rounded-3xl border border-dashed border-slate-300 bg-white/70 p-6 text-sm text-slate-600">No active deliveries right now.</div>
                    @endforelse
                </div>

                <div class="mt-6 space-y-4">
                    @foreach($addresses as $address)
                        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-sm font-semibold text-slate-900">{{ ucfirst($address->type) }}@if($address->is_default) · Default @endif</p>
                            <p class="mt-2 text-sm text-slate-600">{{ $address->first_name }} {{ $address->last_name }}</p>
                            <p class="text-sm text-slate-600">{{ $address->address_line_1 }}</p>
                            <p class="text-sm text-slate-600">{{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}</p>
                            <p class="text-sm text-slate-600">{{ $address->country }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </main>
</body>
</html>
