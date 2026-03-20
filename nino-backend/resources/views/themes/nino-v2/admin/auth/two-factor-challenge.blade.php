<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Two-Factor Authentication - NinoWorld</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    @include('admin.partials.theme-assets')
    <script src="//unpkg.com/alpinejs" defer></script>
</head>
<body data-theme="{{ $adminTheme['key'] ?? 'nino-v2' }}" class="theme-nino-v2 h-full bg-canvas font-body text-ink antialiased">
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10">
        <div class="auth-panel w-full max-w-xl px-8 py-10 sm:px-10" x-data="{ recovery: false }">
            <div class="mb-8">
                <div class="auth-eyebrow">
                    <span class="flex h-8 w-8 items-center justify-center rounded-md bg-[#17302A] text-white">NW</span>
                    Two-Factor Access
                </div>
                <h1 class="mt-6 text-3xl font-extrabold tracking-[-0.04em] text-[#17302A]">Confirm your identity.</h1>
                <p class="mt-2 text-sm leading-6 text-[#5D6F66]" x-show="!recovery">Enter the authentication code from your authenticator app to continue.</p>
                <p class="mt-2 text-sm leading-6 text-[#5D6F66]" x-show="recovery" style="display: none;">Enter one of your emergency recovery codes instead.</p>
            </div>

            <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-6">
                @csrf

                <div x-show="!recovery">
                    <label for="code" class="mb-2 block text-sm font-semibold text-[#17302A]">Authentication code</label>
                    <input id="code" type="text" inputmode="numeric" name="code" autofocus x-ref="code" autocomplete="one-time-code" class="input-field">
                    @error('code')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div x-show="recovery" style="display: none;">
                    <label for="recovery_code" class="mb-2 block text-sm font-semibold text-[#17302A]">Recovery code</label>
                    <input id="recovery_code" type="text" name="recovery_code" x-ref="recovery_code" autocomplete="one-time-code" class="input-field">
                    @error('recovery_code')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col gap-4 border-t border-[rgba(120,112,95,0.16)] pt-4 sm:flex-row sm:items-center sm:justify-between">
                    <button type="button" class="text-sm font-semibold text-[#245848] underline-offset-4 transition-colors hover:text-[#173B31] hover:underline"
                            x-show="!recovery"
                            x-on:click="
                                recovery = true;
                                $nextTick(() => { $refs.recovery_code.focus() })
                            ">
                        Use a recovery code
                    </button>

                    <button type="button" class="text-sm font-semibold text-[#245848] underline-offset-4 transition-colors hover:text-[#173B31] hover:underline"
                            x-show="recovery" style="display: none;"
                            x-on:click="
                                recovery = false;
                                $nextTick(() => { $refs.code.focus() })
                            ">
                        Use an authentication code
                    </button>

                    <button type="submit" class="btn-primary justify-center sm:min-w-[9rem]">Log in</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
