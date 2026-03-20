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
                        <x-admin.textarea name="metadata[instructions]" label="Bank instructions" rows="5" placeholder="Please wire funds to the listed company account and send the receipt to the finance team.">{{ $gateway->metadata['instructions'] ?? '' }}</x-admin.textarea>
                    @elseif($gateway->gateway_id === 'stripe')
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-admin.input type="text" name="metadata[publishable_key]" label="Publishable Key" :value="$gateway->metadata['publishable_key'] ?? ''" placeholder="pk_test_..." />
                            <x-admin.input type="password" name="credentials[secret_key]" label="Secret Key" :value="$gateway->getCredential('secret_key') ? '*******' : ''" placeholder="sk_test_..." />
                        </div>
                    @elseif($gateway->gateway_id === 'cmi')
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-admin.input type="text" name="credentials[store_id]" label="Store ID" :value="$gateway->getCredential('store_id')" placeholder="CMI Store ID" />
                            <x-admin.input type="text" name="credentials[client_id]" label="Client ID" :value="$gateway->getCredential('client_id')" placeholder="CMI Client ID" />
                            <x-admin.input type="password" name="credentials[hash_key]" label="Hash Key" :value="$gateway->getCredential('hash_key') ? '*******' : ''" placeholder="HMAC-SHA512 secret" />
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
