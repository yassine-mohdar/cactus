<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — NinoWorld</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    @include('admin.partials.theme-assets')
</head>
<body data-theme="{{ $adminTheme['key'] ?? 'nino-v1' }}" class="flex min-h-screen items-center justify-center bg-canvas font-body antialiased">
    <div class="w-full max-w-md px-6">
        {{-- Logo --}}
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-indigo-100 bg-indigo-50 text-xl font-black tracking-widest text-indigo-700 shadow-sm shadow-indigo-500/10">NW</div>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">
                <span class="text-indigo-700">Nino</span>World
            </h1>
            <p class="mt-2 text-sm text-slate-500">Sign in to your admin account</p>
        </div>

        {{-- Login Card --}}
        <div class="rounded-2xl border border-slate-200 bg-white px-8 py-8 shadow-sm shadow-slate-900/5">
            @if ($errors->any())
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                {{-- Email --}}
                <div class="mb-4">
                    <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-900">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                           class="input-field" placeholder="you@ninoworld.com">
                </div>

                {{-- Password --}}
                <div class="mb-4">
                    <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-900">Password</label>
                    <input type="password" id="password" name="password" required
                           class="input-field" placeholder="Enter your password">
                </div>

                {{-- Remember + Forgot --}}
                <div class="mb-6 flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm text-slate-500">
                        <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-200 text-slate-900 focus:ring-slate-900/30">
                        Remember me
                    </label>
                </div>

                 <div class="flex items-center justify-between">
                    <button type="submit" class="btn-primary w-full">
                        Sign in
                    </button>
                </div>
                
                <div class="text-center pt-2 border-t border-slate-200 mt-6">
                    <a href="{{ route('password.request') }}" class="text-sm font-medium text-slate-900 transition duration-150 hover:text-indigo-700">Forgot your password?</a>
                </div>
            </form>
        </div>

        <p class="mt-6 text-center text-xs text-slate-500">&copy; {{ date('Y') }} NinoWorld. All rights reserved.</p>
    </div>
</body>
</html>
