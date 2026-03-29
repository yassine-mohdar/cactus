<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — NinoWorld</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    @include('admin.partials.theme-assets')
</head>
<body data-theme="{{ $adminTheme['key'] ?? 'nino-v2' }}" class="theme-nino-v2 min-h-screen bg-canvas font-body text-ink antialiased">
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10">
        <div class="grid w-full max-w-6xl gap-8 lg:grid-cols-[1.1fr_0.9fr]">
            <section class="auth-panel hidden p-10 lg:flex lg:flex-col lg:justify-between">
                <div>
                    <div class="auth-eyebrow">
                        <span class="flex h-8 w-8 items-center justify-center rounded-md bg-[#17302A] text-white">NW</span>
                        Nino v2
                    </div>
                    <h1 class="mt-8 max-w-xl text-5xl font-extrabold tracking-[-0.04em] text-[#17302A]">Run commerce operations from one calm, precise workspace.</h1>
                    <p class="mt-5 max-w-lg text-base leading-7 text-[#5D6F66]">A cleaner admin shell for inventory, orders, finance, and support with better hierarchy, calmer density, and a stronger operational rhythm.</p>
                </div>

                <div class="space-y-4">
                    <div class="surface-panel p-5">
                        <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-[#7C8C83]">Inside this workspace</p>
                        <ul class="mt-4 space-y-3 text-sm text-[#44554E]">
                            <li class="flex items-center gap-3"><span class="material-symbols-outlined text-[#245848]">dashboard</span><span>Operational dashboards with stronger hierarchy.</span></li>
                            <li class="flex items-center gap-3"><span class="material-symbols-outlined text-[#245848]">table_view</span><span>Refined tables and clearer filters for high-volume tasks.</span></li>
                            <li class="flex items-center gap-3"><span class="material-symbols-outlined text-[#245848]">verified_user</span><span>Secure access for staff, support, and management.</span></li>
                        </ul>
                    </div>
                    <p class="text-xs font-medium uppercase tracking-[0.24em] text-[#7A8A82]">&copy; {{ date('Y') }} NinoWorld. Operations Hub.</p>
                </div>
            </section>

            <section class="auth-panel p-8 sm:p-10">
                <div class="mb-8">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl border border-[#D6E4DB] bg-[#ECF4EE] text-lg font-black tracking-[0.24em] text-[#245848]">NW</div>
                    <h2 class="mt-6 text-3xl font-extrabold tracking-[-0.04em] text-[#17302A]">Sign in to Operations Hub</h2>
                    <p class="mt-2 text-sm leading-6 text-[#5D6F66]">Use your staff credentials to access the new admin workspace.</p>
                </div>

                @if ($errors->any())
                    <x-nino.inline-alert tone="danger" title="Sign-in failed" class="mb-5">
                        {{ $errors->first() }}
                    </x-nino.inline-alert>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="mb-2 block text-sm font-semibold text-[#17302A]">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus class="input-field" placeholder="you@ninoworld.com">
                    </div>

                    <div>
                        <label for="password" class="mb-2 block text-sm font-semibold text-[#17302A]">Password</label>
                        <input type="password" id="password" name="password" required class="input-field" placeholder="Enter your password">
                    </div>

                    <div class="flex items-center justify-between gap-4 text-sm">
                        <label class="flex items-center gap-2 text-[#5D6F66]">
                            <input type="checkbox" name="remember" class="h-4 w-4 rounded border-[rgba(145,133,109,0.3)] text-[#245848] focus:ring-[#245848]/20">
                            Remember me
                        </label>
                        <span class="rounded-full border border-[rgba(145,133,109,0.16)] bg-[#F7F2E8] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-[#6D7A72]">Secure access</span>
                    </div>

                    <button type="submit" class="btn-primary w-full justify-center">Sign in</button>

                    <div class="border-t border-[rgba(120,112,95,0.16)] pt-4 text-center">
                        <a href="{{ route('password.request') }}" class="text-sm font-semibold text-[#17302A] transition-colors hover:text-[#245848]">Forgot your password?</a>
                    </div>
                </form>
            </section>
        </div>
    </div>
</body>
</html>
