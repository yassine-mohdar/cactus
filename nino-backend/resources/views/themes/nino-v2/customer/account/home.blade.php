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
    <main class="mx-auto flex min-h-screen max-w-5xl flex-col justify-center px-4 py-10 sm:px-6">
        <section class="surface-panel p-8 sm:p-10">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-[#7C8C83]">Customer account</p>
                    <h1 class="mt-3 text-4xl font-extrabold tracking-[-0.04em] text-[#17302A]">Welcome back, {{ $customer?->full_name ?: $customer?->name }}</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-[#5D6F66]">Your authentication foundation is now active. Orders, addresses, and profile APIs are connected, and the richer account workspace can build on top of this login surface safely.</p>
                </div>

                <form method="POST" action="{{ route('customer.logout') }}">
                    @csrf
                    <button type="submit" class="btn-secondary">Sign out</button>
                </form>
            </div>

            @if (session('success'))
                <x-nino.inline-alert tone="success" title="Account ready" class="mt-6">
                    {{ session('success') }}
                </x-nino.inline-alert>
            @endif

            <div class="mt-8 grid gap-4 md:grid-cols-3">
                <article class="metric-card">
                    <span class="metric-label">Email</span>
                    <div class="mt-3 text-sm font-semibold text-[#17302A]">{{ $customer?->email }}</div>
                    <p class="metric-subtitle">Primary login identity</p>
                </article>

                <article class="metric-card">
                    <span class="metric-label">Saved addresses</span>
                    <div class="metric-value mt-3">{{ number_format((int) ($customer?->addresses_count ?? 0)) }}</div>
                    <p class="metric-subtitle">Billing and shipping entries linked to your account</p>
                </article>

                <article class="metric-card">
                    <span class="metric-label">Orders on account</span>
                    <div class="metric-value mt-3">{{ number_format((int) ($customer?->orders_count ?? 0)) }}</div>
                    <p class="metric-subtitle">Historical count currently attached to this customer</p>
                </article>
            </div>
        </section>
    </main>
</body>
</html>
