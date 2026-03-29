<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login — NinoWorld</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    @include('admin.partials.theme-assets')
</head>
<body class="min-h-screen bg-[#F4EFE6] font-sans text-slate-900">
    <div class="mx-auto flex min-h-screen max-w-5xl items-center justify-center px-4 py-10">
        <div class="grid w-full gap-8 rounded-[28px] border border-[rgba(15,23,42,0.08)] bg-white/90 p-6 shadow-[0_30px_90px_-54px_rgba(15,23,42,0.35)] lg:grid-cols-[1fr_0.9fr] lg:p-10">
            <section class="hidden rounded-[24px] bg-[#17302A] p-10 text-white lg:block">
                <p class="text-[11px] font-bold uppercase tracking-[0.32em] text-white/70">Customer access</p>
                <h1 class="mt-6 text-4xl font-extrabold tracking-[-0.04em]">Sign in to manage your NinoWorld account.</h1>
                <p class="mt-4 max-w-lg text-sm leading-7 text-white/78">If your account was created during checkout, use the password setup email you received to activate it securely.</p>
            </section>

            <section class="rounded-[24px] border border-[rgba(15,23,42,0.08)] bg-white p-8 shadow-sm">
                <h2 class="text-3xl font-extrabold tracking-[-0.04em] text-slate-900">Customer login</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Customer accounts sign in here. Staff accounts must use the admin login.</p>

                @if (session('status'))
                    <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
                @endif

                @if (session('success'))
                    <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
                @endif

                @if ($errors->any())
                    <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('customer.login.store') }}" class="mt-6 space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="mb-2 block text-sm font-semibold text-slate-900">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/15">
                    </div>

                    <div>
                        <label for="password" class="mb-2 block text-sm font-semibold text-slate-900">Password</label>
                        <input id="password" type="password" name="password" required class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/15">
                    </div>

                    <div class="flex items-center justify-between gap-4 text-sm text-slate-600">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/20">
                            Remember me
                        </label>
                        <a href="{{ route('customer.password.request') }}" class="font-semibold text-slate-900 hover:text-indigo-600">Forgot password?</a>
                    </div>

                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Sign in</button>
                </form>

                <p class="mt-4 text-center text-sm text-slate-600">Need staff access? <a href="{{ route('login') }}" class="font-semibold text-slate-900 hover:text-indigo-600">Go to admin login</a></p>
            </section>
        </div>
    </div>
</body>
</html>
