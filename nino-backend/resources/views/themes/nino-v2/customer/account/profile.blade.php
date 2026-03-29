<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile — NinoWorld</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    @include('admin.partials.theme-assets')
</head>
<body data-theme="{{ $adminTheme['key'] ?? 'nino-v2' }}" class="theme-nino-v2 min-h-screen bg-canvas font-body text-ink antialiased">
    <main class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
        <section class="surface-panel p-8 sm:p-10">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-[#7C8C83]">Customer account</p>
                    <h1 class="mt-3 text-4xl font-extrabold tracking-[-0.04em] text-[#17302A]">Edit profile</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-[#5D6F66]">Update account identity details, marketing preference, avatar, and password from one customer-facing workspace.</p>
                </div>
                <a href="{{ route('customer.account.home') }}" class="btn-secondary">Back to account</a>
            </div>

            @if (session('success'))
                <x-nino.inline-alert tone="success" title="Saved" class="mt-6">{{ session('success') }}</x-nino.inline-alert>
            @endif

            @if ($errors->any())
                <x-nino.inline-alert tone="danger" title="Please review the highlighted fields" class="mt-6">
                    {{ $errors->first() }}
                </x-nino.inline-alert>
            @endif
        </section>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <section class="surface-panel p-6 sm:p-8">
                <h2 class="text-2xl font-bold tracking-[-0.03em] text-[#17302A]">Profile details</h2>
                <form method="POST" action="{{ route('customer.account.profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-5">
                    @csrf
                    @method('PUT')
                    <div class="grid gap-5 sm:grid-cols-2">
                        <label class="space-y-2">
                            <span class="filter-label">First name</span>
                            <input type="text" name="first_name" value="{{ old('first_name', $customer->first_name) }}" class="input-field">
                        </label>
                        <label class="space-y-2">
                            <span class="filter-label">Last name</span>
                            <input type="text" name="last_name" value="{{ old('last_name', $customer->last_name) }}" class="input-field">
                        </label>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <label class="space-y-2">
                            <span class="filter-label">Email</span>
                            <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="input-field">
                        </label>
                        <label class="space-y-2">
                            <span class="filter-label">Username</span>
                            <input type="text" name="username" value="{{ old('username', $customer->username) }}" class="input-field">
                        </label>
                    </div>
                    <label class="space-y-2">
                        <span class="filter-label">Avatar</span>
                        <input type="file" name="avatar" class="input-field">
                    </label>
                    <label class="toggle-row">
                        <input type="hidden" name="marketing_opt_in" value="0">
                        <input type="checkbox" name="marketing_opt_in" value="1" class="h-4 w-4" {{ old('marketing_opt_in', $customer->marketing_opt_in) ? 'checked' : '' }}>
                        Receive product and promotion updates
                    </label>
                    <button type="submit" class="btn-primary">Save profile</button>
                </form>
            </section>

            <section class="surface-panel p-6 sm:p-8">
                <h2 class="text-2xl font-bold tracking-[-0.03em] text-[#17302A]">Change password</h2>
                <form method="POST" action="{{ route('customer.account.password.update') }}" class="mt-6 space-y-5">
                    @csrf
                    @method('PUT')
                    <label class="space-y-2">
                        <span class="filter-label">Current password</span>
                        <input type="password" name="current_password" class="input-field">
                    </label>
                    <label class="space-y-2">
                        <span class="filter-label">New password</span>
                        <input type="password" name="password" class="input-field">
                    </label>
                    <label class="space-y-2">
                        <span class="filter-label">Confirm new password</span>
                        <input type="password" name="password_confirmation" class="input-field">
                    </label>
                    <button type="submit" class="btn-primary">Update password</button>
                </form>
            </section>
        </div>
    </main>
</body>
</html>
