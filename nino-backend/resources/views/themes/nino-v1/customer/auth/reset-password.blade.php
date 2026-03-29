<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — NinoWorld</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    @include('admin.partials.theme-assets')
</head>
<body class="min-h-screen bg-[#F4EFE6] font-sans text-slate-900">
    <div class="mx-auto flex min-h-screen max-w-4xl items-center justify-center px-4 py-10">
        <section class="w-full rounded-[28px] border border-[rgba(15,23,42,0.08)] bg-white/90 p-8 shadow-[0_30px_90px_-54px_rgba(15,23,42,0.35)] sm:p-10">
            <p class="text-[11px] font-bold uppercase tracking-[0.32em] text-slate-500">Password reset</p>
            <h1 class="mt-4 text-4xl font-extrabold tracking-[-0.04em] text-slate-900">Choose a new customer password</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">This reset link is tied to <span class="font-semibold text-slate-900">{{ $email }}</span>.</p>

            @if ($errors->any())
                <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('customer.password.reset.store') }}" class="mt-8 space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-slate-900">New password</label>
                    <input id="password" type="password" name="password" required class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/15">
                </div>

                <div>
                    <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-slate-900">Confirm password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/15">
                </div>

                <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Save new password</button>
            </form>
        </section>
    </div>
</body>
</html>
