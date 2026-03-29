<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile — NinoWorld</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    @include('admin.partials.theme-assets')
</head>
<body class="min-h-screen bg-[#F4EFE6] font-sans text-slate-900">
    <main class="mx-auto max-w-5xl px-4 py-10">
        <section class="rounded-[28px] border border-[rgba(15,23,42,0.08)] bg-white/90 p-8 shadow-[0_30px_90px_-54px_rgba(15,23,42,0.35)] sm:p-10">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.32em] text-slate-500">Customer account</p>
                    <h1 class="mt-4 text-4xl font-extrabold tracking-[-0.04em] text-slate-900">Edit profile</h1>
                </div>
                <a href="{{ route('customer.account.home') }}" class="btn-secondary">Back to account</a>
            </div>
            @if(session('success'))
                <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
            @endif
        </section>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <section class="rounded-[28px] border border-[rgba(15,23,42,0.08)] bg-white/90 p-8 shadow-[0_30px_90px_-54px_rgba(15,23,42,0.35)]">
                <form method="POST" action="{{ route('customer.account.profile.update') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <input type="text" name="first_name" value="{{ old('first_name', $customer->first_name) }}" class="input-field" placeholder="First name">
                    <input type="text" name="last_name" value="{{ old('last_name', $customer->last_name) }}" class="input-field" placeholder="Last name">
                    <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="input-field" placeholder="Email">
                    <input type="text" name="username" value="{{ old('username', $customer->username) }}" class="input-field" placeholder="Username">
                    <input type="file" name="avatar" class="input-field">
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="hidden" name="marketing_opt_in" value="0">
                        <input type="checkbox" name="marketing_opt_in" value="1" @checked(old('marketing_opt_in', $customer->marketing_opt_in))>
                        Receive product and promotion updates
                    </label>
                    <button type="submit" class="btn-primary">Save profile</button>
                </form>
            </section>

            <section class="rounded-[28px] border border-[rgba(15,23,42,0.08)] bg-white/90 p-8 shadow-[0_30px_90px_-54px_rgba(15,23,42,0.35)]">
                <form method="POST" action="{{ route('customer.account.password.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <input type="password" name="current_password" class="input-field" placeholder="Current password">
                    <input type="password" name="password" class="input-field" placeholder="New password">
                    <input type="password" name="password_confirmation" class="input-field" placeholder="Confirm new password">
                    <button type="submit" class="btn-primary">Update password</button>
                </form>
            </section>
        </div>
    </main>
</body>
</html>
