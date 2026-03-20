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
    <main class="mx-auto flex min-h-screen max-w-5xl items-center justify-center px-4 py-10">
        <section class="w-full rounded-[28px] border border-[rgba(15,23,42,0.08)] bg-white/90 p-8 shadow-[0_30px_90px_-54px_rgba(15,23,42,0.35)] sm:p-10">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.32em] text-slate-500">Customer account</p>
                    <h1 class="mt-4 text-4xl font-extrabold tracking-[-0.04em] text-slate-900">Welcome back, {{ $customer?->full_name ?: $customer?->name }}</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">Customer authentication is active. This page is the safe landing point for the current account foundation while the richer account workspace continues in later phases.</p>
                </div>

                <form method="POST" action="{{ route('customer.logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-900 transition hover:border-slate-400">Sign out</button>
                </form>
            </div>

            @if (session('success'))
                <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
            @endif

            <div class="mt-8 grid gap-4 md:grid-cols-3">
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-500">Email</p>
                    <p class="mt-3 text-sm font-semibold text-slate-900">{{ $customer?->email }}</p>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-500">Saved addresses</p>
                    <p class="mt-3 text-3xl font-extrabold tracking-[-0.04em] text-slate-900">{{ number_format((int) ($customer?->addresses_count ?? 0)) }}</p>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-500">Orders on account</p>
                    <p class="mt-3 text-3xl font-extrabold tracking-[-0.04em] text-slate-900">{{ number_format((int) ($customer?->orders_count ?? 0)) }}</p>
                </article>
            </div>
        </section>
    </main>
</body>
</html>
