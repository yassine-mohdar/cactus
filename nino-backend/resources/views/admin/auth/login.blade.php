<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — NinoWorld</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-cream font-sans antialiased">
    <div class="w-full max-w-md px-6">
        {{-- Logo --}}
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-extrabold tracking-tight">
                <span class="text-sage">Nino</span><span class="text-ink">World</span>
            </h1>
            <p class="mt-2 text-sm text-ink-muted">Sign in to your admin account</p>
        </div>

        {{-- Login Card --}}
        <div class="rounded-2xl border border-border bg-surface px-8 py-8 shadow-sm">
            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-danger/20 bg-danger-light px-4 py-3 text-sm text-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                {{-- Email --}}
                <div class="mb-4">
                    <label for="email" class="mb-1.5 block text-sm font-semibold text-ink">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full rounded-lg border border-border bg-cream px-4 py-2.5 text-sm text-ink placeholder-ink-muted outline-none transition-colors focus:border-sage focus:ring-1 focus:ring-sage/30">
                </div>

                {{-- Password --}}
                <div class="mb-4">
                    <label for="password" class="mb-1.5 block text-sm font-semibold text-ink">Password</label>
                    <input type="password" id="password" name="password" required
                           class="w-full rounded-lg border border-border bg-cream px-4 py-2.5 text-sm text-ink placeholder-ink-muted outline-none transition-colors focus:border-sage focus:ring-1 focus:ring-sage/30">
                </div>

                {{-- Remember + Forgot --}}
                <div class="mb-6 flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm text-ink-light">
                        <input type="checkbox" name="remember" class="h-4 w-4 rounded border-border text-sage focus:ring-sage/30">
                        Remember me
                    </label>
                </div>

                 <div class="flex items-center justify-between">
                    <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-sage hover:bg-sage-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sage transition duration-150">
                        Sign in
                    </button>
                </div>
                
                <div class="text-center pt-2 border-t border-border mt-6">
                    <a href="{{ route('password.request') }}" class="text-sm font-medium text-sage hover:text-sage-dark transition duration-150">Forgot your password?</a>
                </div>
            </form>
        </div>

        <p class="mt-6 text-center text-xs text-ink-muted">&copy; {{ date('Y') }} NinoWorld. All rights reserved.</p>
    </div>
</body>
</html>
