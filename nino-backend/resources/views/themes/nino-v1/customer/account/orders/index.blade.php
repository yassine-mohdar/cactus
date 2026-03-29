<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History — NinoWorld</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    @include('admin.partials.theme-assets')
</head>
<body class="min-h-screen bg-[#F4EFE6] font-sans text-slate-900">
    <main class="mx-auto max-w-6xl px-4 py-10">
        <section class="rounded-[28px] border border-[rgba(15,23,42,0.08)] bg-white/90 p-8 shadow-[0_30px_90px_-54px_rgba(15,23,42,0.35)] sm:p-10">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.32em] text-slate-500">Customer account</p>
                    <h1 class="mt-4 text-4xl font-extrabold tracking-[-0.04em] text-slate-900">Order history</h1>
                </div>
                <a href="{{ route('customer.account.home') }}" class="btn-secondary">Back to account</a>
            </div>

            <div class="mt-8 grid gap-4 md:grid-cols-3">
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-500">Total</p><p class="mt-3 text-3xl font-extrabold tracking-[-0.04em] text-slate-900">{{ number_format((int) ($summary['total_orders'] ?? 0)) }}</p></article>
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-500">Active</p><p class="mt-3 text-3xl font-extrabold tracking-[-0.04em] text-slate-900">{{ number_format((int) ($summary['active_orders'] ?? 0)) }}</p></article>
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-500">Delivered</p><p class="mt-3 text-3xl font-extrabold tracking-[-0.04em] text-slate-900">{{ number_format((int) ($summary['delivered_orders'] ?? 0)) }}</p></article>
            </div>
        </section>

        <section class="mt-8 rounded-[28px] border border-[rgba(15,23,42,0.08)] bg-white/90 p-8 shadow-[0_30px_90px_-54px_rgba(15,23,42,0.35)]">
            <div class="space-y-4">
                @forelse($orders as $order)
                    <a href="{{ route('customer.account.orders.show', $order) }}" class="block rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $order->reference_number }}</p>
                                <p class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-500">{{ $order->status->label() }}</p>
                            </div>
                            <div class="text-sm text-slate-600 text-right">
                                <p class="font-semibold text-slate-900">{{ number_format((float) $order->grand_total, 2) }} {{ $order->currency }}</p>
                                <p>{{ $order->created_at?->format('M j, Y H:i') }}</p>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-300 bg-white/70 p-6 text-sm text-slate-600">No order history yet.</div>
                @endforelse
            </div>
            <div class="mt-6">{{ $orders->links() }}</div>
        </section>
    </main>
</body>
</html>
