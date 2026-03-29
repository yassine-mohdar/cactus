<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set Staff Access - NinoWorld</title>
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
                    Staff Access Setup
                </div>
                <h1 class="mt-6 text-3xl font-extrabold tracking-[-0.04em] text-[#17302A]">Secure your staff access.</h1>
                <p class="mt-2 text-sm leading-6 text-[#5D6F66]">Use this one-time setup link to set the password for your operations account.</p>
            </div>

            <form method="POST" action="{{ route('staff.access.setup.store') }}" class="space-y-6">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-[#17302A]">Email address</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autofocus class="input-field bg-[#F3EFE6] text-[#617169]" readonly>
                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-[#17302A]">New password</label>
                    <input id="password" type="password" name="password" required class="input-field">
                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-[#17302A]">Confirm new password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required class="input-field">
                </div>

                <button type="submit" class="btn-primary w-full justify-center">Activate Staff Access</button>
            </form>
        </div>
    </div>
</body>
</html>
