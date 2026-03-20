@extends('admin.layouts.app')

@section('title', 'Complete Two-Factor Setup')

@section('header')
    <div class="space-y-3">
        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">Security</p>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Complete two-factor authentication</h1>
        <p class="max-w-3xl text-sm leading-6 text-slate-600">Forced staff 2FA is enabled. Confirm your password, scan the QR code, then enter the generated code to continue into the admin panel.</p>
    </div>
@endsection

@section('content')
    @if (session('success'))
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,0.95fr)_minmax(24rem,0.7fr)]">
        <x-nino.card title="Required setup" subtitle="Staff access hardening">
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Step 1</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">Confirm password</p>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Re-verify your password before the enrollment materials are shown.</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Step 2</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">Scan QR code</p>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Use any compatible authenticator app that supports time-based one-time passwords.</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Step 3</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">Confirm code</p>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Enter the generated 6-digit code to finish setup and unlock the admin area.</p>
                </div>
            </div>
        </x-nino.card>

        <x-nino.card :title="$requiresPasswordConfirmation ? 'Unlock setup materials' : 'Scan and confirm'" subtitle="Authenticator enrollment">
            @if ($requiresPasswordConfirmation)
                <form method="POST" action="{{ route('admin.security.two-factor.prepare') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="current_password" class="mb-2 block text-sm font-semibold text-slate-900">Current password</label>
                        <input id="current_password" name="current_password" type="password" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/15" required autocomplete="current-password">
                        @error('current_password')
                            <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between gap-4 border-t border-slate-200 pt-4">
                        <p class="text-sm leading-6 text-slate-600">This only unlocks the setup materials for the current browser session.</p>
                        <x-nino.button type="submit">Continue</x-nino.button>
                    </div>
                </form>
            @else
                <div class="space-y-5">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Authenticator QR</p>
                        <div class="mt-4 flex flex-col gap-5 md:flex-row">
                            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                {!! $qrCodeSvg !!}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-slate-900">Manual secret</p>
                                <p class="mt-2 break-all rounded-xl bg-white px-3 py-2 font-mono text-sm text-slate-900 shadow-sm">{{ $secretKey }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Recovery codes</p>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            @foreach ($recoveryCodes as $recoveryCode)
                                <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 font-mono text-sm text-slate-900 shadow-sm">
                                    {{ $recoveryCode }}
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.security.two-factor.confirm') }}" class="space-y-5 border-t border-slate-200 pt-5">
                        @csrf

                        <div>
                            <label for="code" class="mb-2 block text-sm font-semibold text-slate-900">Authenticator code</label>
                            <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/15" required>
                            @error('code')
                                <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <p class="text-sm leading-6 text-slate-600">Enter the live 6-digit code from your authenticator app to complete setup.</p>
                            <x-nino.button type="submit">Activate 2FA</x-nino.button>
                        </div>
                    </form>
                </div>
            @endif
        </x-nino.card>
    </div>
@endsection
