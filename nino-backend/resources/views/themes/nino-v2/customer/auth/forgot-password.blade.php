<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — NinoWorld</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    @include('admin.partials.theme-assets')
</head>
<body data-theme="{{ $adminTheme['key'] ?? 'nino-v2' }}" class="theme-nino-v2 min-h-screen bg-canvas font-body text-ink antialiased">
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10">
        <div class="grid w-full max-w-5xl gap-8 lg:grid-cols-[1fr_0.92fr]">
            <section class="auth-panel hidden p-10 lg:flex lg:flex-col lg:justify-between">
                <div>
                    <div class="auth-eyebrow">
                        <span class="flex h-8 w-8 items-center justify-center rounded-md bg-[#17302A] text-white">NW</span>
                        Customer recovery
                    </div>
                    <h1 class="mt-8 max-w-xl text-5xl font-extrabold tracking-[-0.04em] text-[#17302A]">Reset your customer password securely.</h1>
                    <p class="mt-5 max-w-lg text-base leading-7 text-[#5D6F66]">We’ll send a time-limited password reset link to the customer email address on your account.</p>
                </div>

                <div class="surface-panel p-5">
                    <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-[#7C8C83]">What happens next</p>
                    <ul class="mt-4 space-y-3 text-sm text-[#44554E]">
                        <li class="flex items-center gap-3"><span class="material-symbols-outlined text-[#245848]">mail</span><span>You receive a reset link by email.</span></li>
                        <li class="flex items-center gap-3"><span class="material-symbols-outlined text-[#245848]">schedule</span><span>The link expires automatically.</span></li>
                        <li class="flex items-center gap-3"><span class="material-symbols-outlined text-[#245848]">shield_lock</span><span>Older sessions are invalidated after a successful reset.</span></li>
                    </ul>
                </div>
            </section>

            <section class="auth-panel p-8 sm:p-10">
                <div class="mb-8">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl border border-[#D6E4DB] bg-[#ECF4EE] text-lg font-black tracking-[0.24em] text-[#245848]">NW</div>
                    <h2 class="mt-6 text-3xl font-extrabold tracking-[-0.04em] text-[#17302A]">Forgot your password?</h2>
                    <p class="mt-2 text-sm leading-6 text-[#5D6F66]">Enter your customer email and we’ll send you a secure reset link.</p>
                </div>

                @if (session('status'))
                    <x-nino.inline-alert tone="success" title="Reset link sent" class="mb-5">
                        {{ session('status') }}
                    </x-nino.inline-alert>
                @endif

                @if ($errors->any())
                    <x-nino.inline-alert tone="danger" title="Request failed" class="mb-5">
                        {{ $errors->first() }}
                    </x-nino.inline-alert>
                @endif

                <form method="POST" action="{{ route('customer.password.email') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="mb-2 block text-sm font-semibold text-[#17302A]">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus class="input-field" placeholder="you@example.com">
                    </div>

                    <button type="submit" class="btn-primary w-full justify-center">Email reset link</button>

                    <div class="border-t border-[rgba(120,112,95,0.16)] pt-4 text-center">
                        <a href="{{ route('customer.login') }}" class="text-sm font-semibold text-[#17302A] transition-colors hover:text-[#245848]">Back to customer login</a>
                    </div>
                </form>
            </section>
        </div>
    </div>
</body>
</html>
