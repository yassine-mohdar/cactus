<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed — NinoWorld</title>
    @include('admin.partials.theme-assets')
</head>
<body class="min-h-screen bg-gray-50 text-slate-900">
    <main class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
        <section class="rounded-3xl bg-white p-8 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-orange-600">Payment issue</p>
            <h1 class="mt-3 text-3xl font-bold text-slate-900">We could not complete the payment.</h1>
            <p class="mt-3 text-sm text-slate-600">{{ $error_message }}</p>

            @if($reference)
                <div class="mt-6 rounded-2xl border border-orange-200 bg-orange-50 p-4">
                    @if($order_reference)
                        <p class="text-xs uppercase tracking-[0.2em] text-orange-700">Order reference</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900">{{ $order_reference }}</p>
                    @endif
                    <p class="mt-{{ $order_reference ? '4' : '0' }} text-xs uppercase tracking-[0.2em] text-orange-700">{{ $payment_reference ? 'Payment reference' : 'Reference' }}</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ $payment_reference ?? $reference }}</p>
                    @if($order)
                        <p class="mt-2 text-sm text-slate-600">Current order status: <span class="font-semibold text-slate-900">{{ $order->status->label() }}</span></p>
                    @endif
                </div>
            @endif
        </section>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <section class="rounded-3xl bg-white p-8 shadow-sm">
                <h2 class="text-2xl font-bold text-slate-900">Clear failure summary</h2>
                <div class="mt-6 space-y-4 text-sm text-slate-600">
                    <p>The payment gateway returned an unsuccessful result, so this checkout attempt was not completed.</p>
                    @if($reference)
                        <p>Use the order reference above to reopen the public order-status page and confirm the latest state before attempting another payment step.</p>
                    @endif
                </div>
            </section>

            <section class="rounded-3xl bg-white p-8 shadow-sm">
                <h2 class="text-2xl font-bold text-slate-900">Recovery options</h2>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ $recovery['primary_url'] }}" class="inline-flex rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white">{{ $recovery['primary_label'] }}</a>
                    <a href="{{ $recovery['secondary_url'] }}" class="inline-flex rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">{{ $recovery['secondary_label'] }}</a>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
