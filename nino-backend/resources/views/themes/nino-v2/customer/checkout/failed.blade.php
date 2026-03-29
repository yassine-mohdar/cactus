<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed — NinoWorld</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    @include('admin.partials.theme-assets')
</head>
<body data-theme="{{ $adminTheme['key'] ?? 'nino-v2' }}" class="theme-nino-v2 min-h-screen bg-canvas font-body text-ink antialiased">
    <main class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
        <section class="surface-panel p-8 sm:p-10">
            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-[#B5672B]">Payment issue</p>
            <h1 class="mt-3 text-4xl font-extrabold tracking-[-0.04em] text-[#17302A]">We could not complete the payment.</h1>
            <p class="mt-3 max-w-2xl text-sm leading-7 text-[#5D6F66]">{{ $error_message }}</p>

            @if($reference)
                <div class="mt-6 rounded-3xl border border-[rgba(181,103,43,0.18)] bg-[#FFF7F0] p-5">
                    @if($order_reference)
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-[#B5672B]">Order reference</p>
                        <p class="mt-2 text-lg font-semibold text-[#17302A]">{{ $order_reference }}</p>
                    @endif
                    <p class="mt-{{ $order_reference ? '4' : '0' }} text-[11px] font-bold uppercase tracking-[0.18em] text-[#B5672B]">{{ $payment_reference ? 'Payment reference' : 'Reference' }}</p>
                    <p class="mt-2 text-lg font-semibold text-[#17302A]">{{ $payment_reference ?? $reference }}</p>
                    @if($order)
                        <p class="mt-2 text-sm text-[#5D6F66]">Current order status: <span class="font-semibold text-[#17302A]">{{ $order->status->label() }}</span></p>
                    @endif
                </div>
            @endif
        </section>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1.05fr,0.95fr]">
            <section class="surface-panel p-6 sm:p-8">
                <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-[#7C8C83]">What happened</p>
                <h2 class="mt-2 text-2xl font-bold tracking-[-0.03em] text-[#17302A]">Clear failure summary</h2>

                <div class="mt-6 space-y-4 text-sm leading-7 text-[#5D6F66]">
                    <p>The payment gateway returned an unsuccessful result, so NinoWorld did not complete the payment flow on this attempt.</p>
                    <p>No further checkout step should be assumed until you confirm the latest order status.</p>
                    @if($reference)
                        <p>Use the order reference above to reopen the public order-status page and confirm whether the order is still pending, awaiting payment, cancelled, or already updated.</p>
                    @endif
                </div>
            </section>

            <section class="surface-panel p-6 sm:p-8">
                <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-[#7C8C83]">Recovery options</p>
                <h2 class="mt-2 text-2xl font-bold tracking-[-0.03em] text-[#17302A]">Next action</h2>

                <div class="mt-6 space-y-4 text-sm leading-7 text-[#5D6F66]">
                    <p>Start with the primary recovery path below, then confirm the latest order state before attempting another payment step.</p>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ $recovery['primary_url'] }}" class="btn-primary">{{ $recovery['primary_label'] }}</a>
                    <a href="{{ $recovery['secondary_url'] }}" class="btn-secondary">{{ $recovery['secondary_label'] }}</a>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
