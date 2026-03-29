<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password - NinoWorld</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    @include('admin.partials.theme-assets')
</head>
<body data-theme="{{ $adminTheme['key'] ?? 'nino-v2' }}" class="theme-nino-v2 h-full bg-canvas font-body text-ink antialiased">
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10">
        <div class="auth-panel w-full max-w-xl px-8 py-10 sm:px-10">
            <div class="mb-8">
                <div class="auth-eyebrow">
                    <span class="flex h-8 w-8 items-center justify-center rounded-md bg-[#17302A] text-white">NW</span>
                    Account Recovery
                </div>
                <h1 class="mt-6 text-3xl font-extrabold tracking-[-0.04em] text-[#17302A]">Reset access to your staff account.</h1>
                <p class="mt-2 text-sm leading-6 text-[#5D6F66]">Enter your email address and we’ll send you a secure password reset link.</p>
            </div>

            @if (session('status'))
                <x-nino.inline-alert tone="success" title="Reset link sent" class="mb-5">
                    {{ session('status') }}
                </x-nino.inline-alert>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
                @csrf

                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-[#17302A]">Email address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="input-field">
                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn-primary w-full justify-center">Email Password Reset Link</button>

                <div class="border-t border-[rgba(120,112,95,0.16)] pt-4 text-center">
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-[#17302A] transition-colors hover:text-[#245848]">Back to login</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
