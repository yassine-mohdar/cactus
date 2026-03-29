<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $order->reference_number }} — NinoWorld</title>
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
                    <p class="text-[11px] font-bold uppercase tracking-[0.32em] text-slate-500">Customer order detail</p>
                    <h1 class="mt-4 text-4xl font-extrabold tracking-[-0.04em] text-slate-900">{{ $order->reference_number }}</h1>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('customer.account.orders.index') }}" class="btn-secondary">Back to orders</a>
                    @if($tracking && $tracking['tracking_url'])
                        <a href="{{ $tracking['tracking_url'] }}" target="_blank" rel="noopener noreferrer" class="btn-primary">Open tracking</a>
                    @endif
                </div>
            </div>

            <div class="mt-8 grid gap-4 md:grid-cols-4">
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-500">Status</p><p class="mt-3 text-sm font-semibold text-slate-900">{{ $summary['status_label'] }}</p></article>
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-500">Grand total</p><p class="mt-3 text-sm font-semibold text-slate-900">{{ $summary['grand_total'] }} {{ $summary['currency'] }}</p></article>
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-500">Items</p><p class="mt-3 text-sm font-semibold text-slate-900">{{ $summary['items_count'] }}</p></article>
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-500">Placed</p><p class="mt-3 text-sm font-semibold text-slate-900">{{ $summary['created_at_human'] }}</p></article>
            </div>
        </section>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <section class="rounded-[28px] border border-[rgba(15,23,42,0.08)] bg-white/90 p-8 shadow-[0_30px_90px_-54px_rgba(15,23,42,0.35)]">
                <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-500">Items</p>
                <div class="mt-6 space-y-4">
                    @foreach($order->lineItems as $item)
                        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-sm font-semibold text-slate-900">{{ $item->product_name }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $item->variant_name }}</p>
                            <p class="mt-2 text-sm text-slate-600">{{ $item->quantity }} × {{ number_format((float) $item->unit_price, 2) }} {{ $order->currency }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
            <section class="rounded-[28px] border border-[rgba(15,23,42,0.08)] bg-white/90 p-8 shadow-[0_30px_90px_-54px_rgba(15,23,42,0.35)]">
                <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-500">Tracking</p>
                @if($tracking)
                    <div class="mt-6 space-y-4">
                        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-sm font-semibold text-slate-900">{{ $tracking['status_label'] }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $tracking['carrier'] ?: 'Carrier pending' }}</p>
                            @if($tracking['tracking_number'])
                                <p class="mt-2 text-sm font-medium text-slate-900">{{ $tracking['tracking_number'] }}</p>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="mt-6 rounded-3xl border border-dashed border-slate-300 bg-white/70 p-6 text-sm text-slate-600">Tracking not available yet.</div>
                @endif
            </section>
        </div>
    </main>
</body>
</html>
