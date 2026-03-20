@extends('admin.layouts.app')

@section('title', 'Complete Two-Factor Setup')

@section('content')
    <x-nino.page-header
        eyebrow="Security hardening"
        title="Complete two-factor authentication"
        subtitle="Staff access is protected by a mandatory authenticator-app setup. Confirm your password, scan the QR code, and enter the generated code to unlock the admin panel."
    />

    @if (session('success'))
        <x-nino.inline-alert tone="success" title="Setup in progress" class="mb-6">
            {{ session('success') }}
        </x-nino.inline-alert>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,0.92fr)_minmax(21rem,0.74fr)]">
        <x-nino.detail-section title="Why this is required" subtitle="Forced 2FA is enabled in Security settings for all staff accounts." icon="shield_lock">
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-xl border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] p-4">
                    <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-[#7A8681]">Step 1</p>
                    <h3 class="mt-2 text-sm font-bold text-[#1E2B27]">Confirm your password</h3>
                    <p class="mt-2 text-sm leading-6 text-[#61706B]">We unlock the enrollment materials only after re-verifying your current password.</p>
                </div>
                <div class="rounded-xl border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] p-4">
                    <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-[#7A8681]">Step 2</p>
                    <h3 class="mt-2 text-sm font-bold text-[#1E2B27]">Scan the QR code</h3>
                    <p class="mt-2 text-sm leading-6 text-[#61706B]">Pair the account with Google Authenticator, 1Password, Authy, or another TOTP-compatible app.</p>
                </div>
                <div class="rounded-xl border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] p-4">
                    <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-[#7A8681]">Step 3</p>
                    <h3 class="mt-2 text-sm font-bold text-[#1E2B27]">Confirm the generated code</h3>
                    <p class="mt-2 text-sm leading-6 text-[#61706B]">Once confirmed, you will use a 6-digit code at login in addition to your password.</p>
                </div>
            </div>
        </x-nino.detail-section>

        <x-nino.detail-section
            :title="$requiresPasswordConfirmation ? 'Unlock setup materials' : 'Scan and confirm'"
            :subtitle="$requiresPasswordConfirmation ? 'Re-enter your password to reveal the QR code and recovery codes.' : 'Keep a copy of the recovery codes before you confirm the setup.'"
            icon="qr_code_2"
        >
            @if ($requiresPasswordConfirmation)
                <form method="POST" action="{{ route('admin.security.two-factor.prepare') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="current_password" class="mb-2 block text-sm font-semibold text-[#17302A]">Current password</label>
                        <input id="current_password" name="current_password" type="password" class="input-field" required autocomplete="current-password">
                        @error('current_password')
                            <p class="mt-2 text-sm font-medium text-[#C45143]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between gap-3 border-t border-[rgba(120,112,95,0.14)] pt-4">
                        <p class="text-sm leading-6 text-[#61706B]">This step does not log you out. It only unlocks the enrollment materials for this browser session.</p>
                        <x-nino.button type="submit" icon="lock_open_right">Continue</x-nino.button>
                    </div>
                </form>
            @else
                <div class="space-y-5">
                    <div class="rounded-2xl border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] p-5">
                        <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-[#7A8681]">Authenticator QR</p>
                        <div class="mt-4 flex flex-col gap-5 md:flex-row md:items-start">
                            <div class="rounded-2xl border border-[rgba(120,112,95,0.14)] bg-white p-4 shadow-[0_1px_2px_rgba(17,24,39,0.05)]">
                                {!! $qrCodeSvg !!}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-[#1E2B27]">Manual secret</p>
                                <p class="mt-2 break-all rounded-xl bg-[#F5F1EA] px-3 py-2 font-mono text-sm text-[#245848]">{{ $secretKey }}</p>
                                <p class="mt-3 text-sm leading-6 text-[#61706B]">If your authenticator cannot scan the QR code, use the manual secret instead.</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] p-5">
                        <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-[#7A8681]">Recovery codes</p>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            @foreach ($recoveryCodes as $recoveryCode)
                                <div class="rounded-xl border border-[rgba(120,112,95,0.14)] bg-white px-3 py-2 font-mono text-sm text-[#1E2B27]">
                                    {{ $recoveryCode }}
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-3 text-sm leading-6 text-[#61706B]">Store these codes somewhere secure. Each one can be used once if you lose access to your authenticator app.</p>
                    </div>

                    <form method="POST" action="{{ route('admin.security.two-factor.confirm') }}" class="space-y-5 border-t border-[rgba(120,112,95,0.14)] pt-5">
                        @csrf

                        <div>
                            <label for="code" class="mb-2 block text-sm font-semibold text-[#17302A]">Authenticator code</label>
                            <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" class="input-field" required>
                            @error('code')
                                <p class="mt-2 text-sm font-medium text-[#C45143]">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm leading-6 text-[#61706B]">Enter the current 6-digit code from your authenticator app to finalize setup.</p>
                            <x-nino.button type="submit" icon="verified_user">Activate 2FA</x-nino.button>
                        </div>
                    </form>
                </div>
            @endif
        </x-nino.detail-section>
    </div>
@endsection
