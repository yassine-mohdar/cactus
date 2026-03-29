@extends('admin.layouts.app')

@section('title', 'Payment Gateways')

@section('header')
    @php
        $enabledCount = $gateways->where('is_enabled', true)->count();
        $liveCount = $gateways->where('mode', 'live')->count();
        $configuredCount = $gateways->filter(function ($gateway) {
            $credentials = collect($gateway->credentials ?? [])->filter(fn ($value) => filled($value));
            $metadata = collect($gateway->metadata ?? [])->filter(fn ($value) => filled($value));

            return $credentials->isNotEmpty() || $metadata->isNotEmpty();
        })->count();
    @endphp

    <div class="page-header">
        <div>
            <h1 class="page-title">Payment Gateways</h1>
            <p class="page-subtitle">Configure live and test credentials, activation state, and customer-facing payment instructions for each provider.</p>
        </div>

        <span class="datatable-meta">{{ number_format($gateways->count()) }} providers</span>
    </div>
@endsection

@section('content')
    @if(session('success'))
        <div class="mb-6 rounded-[1.35rem] border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm font-medium text-emerald-800 shadow-[0_18px_42px_-30px_rgba(8,127,91,0.45)] backdrop-blur">
            {{ session('success') }}
        </div>
    @endif

    <div class="stats-grid mb-6">
        <div class="stat-card">
            <p class="stat-label">Available Gateways</p>
            <p class="stat-value">{{ number_format($gateways->count()) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Enabled</p>
            <p class="stat-value text-[#0F7A54]">{{ number_format($enabledCount) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Live Mode</p>
            <p class="stat-value text-[#235B8C]">{{ number_format($liveCount) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Configured</p>
            <p class="stat-value">{{ number_format($configuredCount) }}</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        @foreach($gateways as $gateway)
            @php
                $gatewayDescription = match($gateway->gateway_id) {
                    'offline_transfer' => 'Display bank transfer instructions for manual payment confirmation flows.',
                    'stripe' => 'Card-based online payments through Stripe checkout and payment intents.',
                    'cmi' => 'Moroccan CMI gateway with store credentials, signing keys, and localized checkout metadata.',
                    'payzone' => 'Alternative processor configuration for merchant-based online payments.',
                    default => 'Configure provider credentials and operating mode.',
                };
                $gatewayConfigured = collect($gateway->credentials ?? [])->filter(fn ($value) => filled($value))->isNotEmpty()
                    || collect($gateway->metadata ?? [])->filter(fn ($value) => filled($value))->isNotEmpty();
            @endphp

            <x-admin.card title="{{ $gateway->name }}">
                <x-slot:header>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full border px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.22em] {{ $gateway->is_enabled ? 'border-[#D7E5DB] bg-[#ECF4EE] text-[#245848]' : 'border-[rgba(145,133,109,0.18)] bg-[#FCFBF8] text-[#617169]' }}">
                            {{ $gateway->is_enabled ? 'Enabled' : 'Disabled' }}
                        </span>
                        <span class="inline-flex items-center rounded-full border px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.22em] {{ $gateway->mode === 'live' ? 'border-[#D6E7F8] bg-[#EEF5FC] text-[#235B8C]' : 'border-[rgba(145,133,109,0.18)] bg-[#FCFBF8] text-[#617169]' }}">
                            {{ strtoupper($gateway->mode) }}
                        </span>
                    </div>
                </x-slot:header>

                <form method="POST" action="{{ route('admin.gateways.update', $gateway) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <p class="form-copy">{{ $gatewayDescription }}</p>

                    <div class="flex flex-col gap-4 rounded-[1.25rem] border border-[rgba(145,133,109,0.18)] bg-[#FCFBF8] p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-[#17302A]">Enable Gateway</p>
                            <p class="mt-1 text-sm text-[#617169]">Turn this provider on for checkout and payment processing.</p>
                        </div>

                        <label class="inline-flex items-center gap-3">
                            <span class="text-sm font-semibold text-[#17302A]">{{ $gateway->is_enabled ? 'Active' : 'Disabled' }}</span>
                            <span class="relative inline-flex h-7 w-[3.25rem] items-center">
                                <input type="checkbox" name="is_enabled" value="1" {{ $gateway->is_enabled ? 'checked' : '' }} class="peer sr-only">
                                <span class="toggle-track"></span>
                                <span class="toggle-thumb"></span>
                            </span>
                        </label>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-admin.select name="mode" label="Environment">
                            <option value="test" {{ $gateway->mode === 'test' ? 'selected' : '' }}>Test Mode</option>
                            <option value="live" {{ $gateway->mode === 'live' ? 'selected' : '' }}>Live Production</option>
                        </x-admin.select>
                    </div>

                    <div class="form-note">
                        Secrets remain encrypted at rest. Leave masked password fields unchanged unless you are intentionally rotating credentials.
                    </div>

                    @if($gateway->gateway_id === 'offline_transfer')
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-admin.input type="text" name="metadata[method_label]" label="Method Label" :value="$gateway->metadata['method_label'] ?? 'Bank Transfer'" placeholder="Bank Transfer" />
                            <x-admin.input type="text" name="metadata[checkout_title]" label="Checkout Title" :value="$gateway->metadata['checkout_title'] ?? 'Bank transfer instructions'" placeholder="Bank transfer instructions" />
                            <x-admin.input type="text" name="metadata[checkout_description]" label="Checkout Description" :value="$gateway->metadata['checkout_description'] ?? ''" placeholder="Show the account details after checkout so customers can complete the transfer manually." />
                            <x-admin.input type="number" min="1" max="168" name="metadata[payment_window_hours]" label="Payment Window (Hours)" :value="$gateway->metadata['payment_window_hours'] ?? 48" placeholder="48" />
                            <x-admin.input type="text" name="metadata[bank_name]" label="Bank Name" :value="$gateway->metadata['bank_name'] ?? ''" placeholder="Attijariwafa Bank" />
                            <x-admin.input type="text" name="metadata[account_holder]" label="Account Holder" :value="$gateway->metadata['account_holder'] ?? ''" placeholder="NinoWorld SARL AU" />
                            <x-admin.input type="text" name="metadata[account_number]" label="Account Number" :value="$gateway->metadata['account_number'] ?? ''" placeholder="12345678901234567890" />
                            <x-admin.input type="text" name="metadata[iban]" label="IBAN" :value="$gateway->metadata['iban'] ?? ''" placeholder="MA64001122334455667788990011" />
                            <x-admin.input type="text" name="metadata[swift_code]" label="SWIFT Code" :value="$gateway->metadata['swift_code'] ?? ''" placeholder="BCMAMAMC" />
                            <x-admin.input type="text" name="metadata[reference_prefix]" label="Reference Prefix" :value="$gateway->metadata['reference_prefix'] ?? 'NINO'" placeholder="NINO" />
                            <x-admin.select name="metadata[require_receipt]" label="Require Receipt Confirmation">
                                <option value="1" {{ (bool) ($gateway->metadata['require_receipt'] ?? true) ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ ! (bool) ($gateway->metadata['require_receipt'] ?? true) ? 'selected' : '' }}>No</option>
                            </x-admin.select>
                        </div>
                        <x-admin.textarea name="metadata[instructions]" label="Bank instructions" rows="5" placeholder="Please wire funds to the listed company account and send the receipt to the finance team.">{{ $gateway->metadata['instructions'] ?? '' }}</x-admin.textarea>
                        <x-admin.textarea name="metadata[admin_instructions]" label="Admin verification notes" rows="4" placeholder="Finance should validate the transfer reference, receipt, and settled amount before marking the transaction as completed.">{{ $gateway->metadata['admin_instructions'] ?? '' }}</x-admin.textarea>
                        <div class="rounded-[1.15rem] border border-[rgba(145,133,109,0.18)] bg-[#FCFBF8] px-4 py-4 text-sm text-[#5D6F66]">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#6E7D75]">Customer checkout rendering</p>
                            <p class="mt-2 font-semibold text-[#17302A]">{{ $gateway->metadata['checkout_title'] ?? 'Bank transfer instructions' }}</p>
                            @if(filled($gateway->metadata['checkout_description'] ?? null))
                                <p class="mt-2">{{ $gateway->metadata['checkout_description'] }}</p>
                            @endif
                            @if(filled($gateway->metadata['admin_instructions'] ?? null))
                                <p class="mt-3"><span class="font-semibold text-[#17302A]">Internal note:</span> {{ $gateway->metadata['admin_instructions'] }}</p>
                            @endif
                        </div>
                    @elseif($gateway->gateway_id === 'stripe')
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-admin.input type="text" name="metadata[publishable_key]" label="Publishable Key" :value="$gateway->metadata['publishable_key'] ?? ''" placeholder="pk_test_..." />
                            <x-admin.input type="password" name="credentials[secret_key]" label="Secret Key" :value="$gateway->getCredential('secret_key') ? '*******' : ''" placeholder="sk_test_..." />
                            <x-admin.input type="password" name="credentials[webhook_secret]" label="Webhook Secret" :value="$gateway->getCredential('webhook_secret') ? '*******' : ''" placeholder="whsec_..." />
                            <x-admin.input type="text" name="metadata[currency]" label="Currency" :value="$gateway->metadata['currency'] ?? 'MAD'" placeholder="MAD" />
                        </div>
                    @elseif($gateway->gateway_id === 'cmi')
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-admin.input type="text" name="credentials[store_id]" label="Store ID" :value="$gateway->getCredential('store_id')" placeholder="CMI Store ID" />
                            <x-admin.input type="text" name="credentials[client_id]" label="Client ID" :value="$gateway->getCredential('client_id')" placeholder="CMI Client ID" />
                            <x-admin.input type="password" name="credentials[hash_key]" label="Hash Key" :value="$gateway->getCredential('hash_key') ? '*******' : ''" placeholder="HMAC-SHA512 secret" />
                            <x-admin.input type="text" name="credentials[terminal_id]" label="Terminal ID" :value="$gateway->getCredential('terminal_id')" placeholder="Optional terminal identifier" />
                            <x-admin.input type="text" name="metadata[currency_code]" label="Currency Code" :value="$gateway->metadata['currency_code'] ?? '504'" placeholder="504" />
                            <x-admin.select name="metadata[language]" label="Language">
                                <option value="fr" {{ ($gateway->metadata['language'] ?? 'fr') === 'fr' ? 'selected' : '' }}>Français</option>
                                <option value="ar" {{ ($gateway->metadata['language'] ?? '') === 'ar' ? 'selected' : '' }}>العربية</option>
                                <option value="en" {{ ($gateway->metadata['language'] ?? '') === 'en' ? 'selected' : '' }}>English</option>
                            </x-admin.select>
                        </div>
                    @elseif($gateway->gateway_id === 'payzone')
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-admin.input type="text" name="credentials[merchant_id]" label="Merchant ID" :value="$gateway->getCredential('merchant_id')" placeholder="Payzone Merchant ID" />
                            <x-admin.input type="password" name="credentials[api_key]" label="API Key" :value="$gateway->getCredential('api_key') ? '*******' : ''" placeholder="Payzone API key" />
                            <x-admin.input type="password" name="credentials[secret_key]" label="Secret Key" :value="$gateway->getCredential('secret_key') ? '*******' : ''" placeholder="HMAC signing secret" />
                            <x-admin.input type="text" name="metadata[currency]" label="Currency" :value="$gateway->metadata['currency'] ?? 'MAD'" placeholder="MAD" />
                        </div>
                    @endif

                    <div class="border-t border-[rgba(145,133,109,0.16)] bg-[#F7F2E8]/75 -mx-6 -mb-6 mt-6 px-6 py-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <span class="text-xs uppercase tracking-[0.18em] text-[#6E7D75]">{{ $gatewayConfigured ? 'Credentials present' : 'Awaiting credentials' }}</span>
                            <x-admin.button type="submit" variant="primary">Save Settings</x-admin.button>
                        </div>
                    </div>
                </form>
            </x-admin.card>
        @endforeach
    </div>
@endsection
