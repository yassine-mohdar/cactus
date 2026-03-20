<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password - NinoWorld</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    @include('admin.partials.theme-assets')
</head>
<body data-theme="{{ $adminTheme['key'] ?? 'nino-v1' }}" class="h-full font-sans antialiased text-ink bg-white flex flex-col items-center justify-center p-4">

    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <span class="text-3xl font-extrabold tracking-tight text-sage">Nino</span><span class="text-3xl font-extrabold tracking-tight text-ink">World</span>
            <p class="mt-2 text-sm text-ink-muted">Set a new password for your account.</p>
        </div>

        <!-- Reset Password Card -->
        <div class="bg-white px-8 py-10 shadow-sm border border-border rounded-md">
            <form method="POST" action="{{ route('password.update') }}" class="space-y-6">
                @csrf

                <!-- Password Reset Token -->
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <!-- Email Address -->
                <div>
                    <label for="email" class="block text-sm font-medium text-ink mb-1">Email Address</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus
                           class="w-full rounded-lg border border-border bg-gray-50 px-4 py-2 text-sm text-gray-600 focus:outline-none" readonly>
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-ink mb-1">New Password</label>
                    <input id="password" type="password" name="password" required
                           class="w-full rounded-lg border border-border bg-cream px-4 py-2.5 text-sm text-ink placeholder-ink-muted outline-none transition-colors focus:border-sage focus:ring-1 focus:ring-sage/30">
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-ink mb-1">Confirm New Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required
                           class="w-full rounded-lg border border-border bg-cream px-4 py-2.5 text-sm text-ink placeholder-ink-muted outline-none transition-colors focus:border-sage focus:ring-1 focus:ring-sage/30">
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-sage hover:bg-sage-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sage transition duration-150">
                        Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
