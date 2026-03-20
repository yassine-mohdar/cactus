<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password - NinoWorld</title>
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
            <p class="mt-2 text-sm text-ink-muted">Recover your staff account access.</p>
        </div>

        <!-- Forgot Password Card -->
        <div class="bg-white px-8 py-10 shadow-sm border border-border rounded-md">
            @if (session('status'))
                <div class="mb-4 text-sm font-medium text-green-600 bg-green-50 p-3 rounded-lg border border-green-200">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
                @csrf

                <!-- Email Address -->
                <div>
                    <label for="email" class="block text-sm font-medium text-ink mb-1">Email Address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full rounded-lg border border-border bg-cream px-4 py-2.5 text-sm text-ink placeholder-ink-muted outline-none transition-colors focus:border-sage focus:ring-1 focus:ring-sage/30">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-sage hover:bg-sage-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sage transition duration-150">
                        Email Password Reset Link
                    </button>
                </div>
                
                <div class="text-center pt-2 border-t border-border mt-6">
                    <a href="{{ route('login') }}" class="text-sm font-medium text-sage hover:text-sage-dark transition duration-150">Back to login</a>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
