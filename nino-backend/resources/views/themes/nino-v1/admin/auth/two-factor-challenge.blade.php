<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Two-Factor Authentication - NinoWorld</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    @include('admin.partials.theme-assets')
    <script src="//unpkg.com/alpinejs" defer></script>
</head>
<body data-theme="{{ $adminTheme['key'] ?? 'nino-v1' }}" class="h-full font-sans antialiased text-ink bg-white flex flex-col items-center justify-center p-4">

    <div class="w-full max-w-md" x-data="{ recovery: false }">
        <!-- Logo -->
        <div class="text-center mb-8">
            <span class="text-3xl font-extrabold tracking-tight text-sage">Nino</span><span class="text-3xl font-extrabold tracking-tight text-ink">World</span>
            <p class="mt-2 text-sm text-ink-muted">Secure your account access.</p>
        </div>

        <!-- 2FA Card -->
        <div class="bg-white px-8 py-10 shadow-sm border border-border rounded-md">
            <div class="mb-6 text-sm text-gray-600" x-show="! recovery">
                Please confirm access to your account by entering the authentication code provided by your authenticator application.
            </div>

            <div class="mb-6 text-sm text-gray-600" x-show="recovery" style="display: none;">
                Please confirm access to your account by entering one of your emergency recovery codes.
            </div>

            <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-6">
                @csrf

                <div x-show="!recovery">
                    <label for="code" class="block text-sm font-medium text-ink mb-1">Authentication Code</label>
                    <input id="code" type="text" inputmode="numeric" name="code" autofocus x-ref="code" autocomplete="one-time-code"
                           class="w-full rounded-lg border border-border bg-cream px-4 py-2.5 text-sm text-ink placeholder-ink-muted outline-none transition-colors focus:border-sage focus:ring-1 focus:ring-sage/30">
                    @error('code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div x-show="recovery" style="display: none;">
                    <label for="recovery_code" class="block text-sm font-medium text-ink mb-1">Recovery Code</label>
                    <input id="recovery_code" type="text" name="recovery_code" x-ref="recovery_code" autocomplete="one-time-code"
                           class="w-full rounded-lg border border-border bg-cream px-4 py-2.5 text-sm text-ink placeholder-ink-muted outline-none transition-colors focus:border-sage focus:ring-1 focus:ring-sage/30">
                    @error('recovery_code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between mt-4">
                    <button type="button" class="text-sm text-sage hover:text-sage-dark underline cursor-pointer"
                            x-show="!recovery"
                            x-on:click="
                                recovery = true;
                                $nextTick(() => { $refs.recovery_code.focus() })
                            ">
                        Use a recovery code
                    </button>

                    <button type="button" class="text-sm text-sage hover:text-sage-dark underline cursor-pointer"
                            x-show="recovery" style="display: none;"
                            x-on:click="
                                recovery = false;
                                $nextTick(() => { $refs.code.focus() })
                            ">
                        Use an authentication code
                    </button>

                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-sage hover:bg-sage-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sage transition duration-150">
                        Log in
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
