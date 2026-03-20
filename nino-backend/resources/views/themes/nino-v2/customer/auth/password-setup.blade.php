<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Password — NinoWorld</title>
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
                        Secure onboarding
                    </div>
                    <h1 class="mt-8 max-w-xl text-5xl font-extrabold tracking-[-0.04em] text-[#17302A]">Finish setting up your customer account.</h1>
                    <p class="mt-5 max-w-lg text-base leading-7 text-[#5D6F66]">Choose a strong password to access order updates, addresses, and future customer account tools.</p>
                </div>

                <div class="surface-panel p-5">
                    <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-[#7C8C83]">Security notes</p>
                    <ul class="mt-4 space-y-3 text-sm text-[#44554E]">
                        <li class="flex items-center gap-3"><span class="material-symbols-outlined text-[#245848]">schedule</span><span>Your setup link is time-limited.</span></li>
                        <li class="flex items-center gap-3"><span class="material-symbols-outlined text-[#245848]">encrypted</span><span>Passwords are never sent in plain text.</span></li>
                        <li class="flex items-center gap-3"><span class="material-symbols-outlined text-[#245848]">verified_user</span><span>This flow is separate from staff/admin access.</span></li>
                    </ul>
                </div>
            </section>

            <section class="auth-panel p-8 sm:p-10">
                <div class="mb-8">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl border border-[#D6E4DB] bg-[#ECF4EE] text-lg font-black tracking-[0.24em] text-[#245848]">NW</div>
                    <h2 class="mt-6 text-3xl font-extrabold tracking-[-0.04em] text-[#17302A]">Set your password</h2>
                    <p class="mt-2 text-sm leading-6 text-[#5D6F66]">This setup link is tied to <span class="font-semibold text-[#17302A]">{{ $email }}</span>.</p>
                </div>

                @if ($errors->any())
                    <x-nino.inline-alert tone="danger" title="Setup failed" class="mb-5">
                        {{ $errors->first() }}
                    </x-nino.inline-alert>
                @endif

                <form method="POST" action="{{ route('customer.password.setup.store') }}" class="space-y-5">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="email" value="{{ $email }}">

                    <div>
                        <label for="password" class="mb-2 block text-sm font-semibold text-[#17302A]">New password</label>
                        <input type="password" id="password" name="password" required class="input-field" placeholder="Choose a strong password" autocomplete="new-password">
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-[#17302A]">Confirm password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required class="input-field" placeholder="Repeat your password" autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn-primary w-full justify-center">Save password and continue</button>

                    <div class="border-t border-[rgba(120,112,95,0.16)] pt-4 text-center">
                        <a href="{{ route('customer.login') }}" class="text-sm font-semibold text-[#17302A] transition-colors hover:text-[#245848]">Back to customer login</a>
                    </div>
                </form>
            </section>
        </div>
    </div>
</body>
</html>
