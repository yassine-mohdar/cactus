@extends('admin.layouts.app')

@section('header')
<div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold font-headline text-slate-900">Payment Gateways</h1>
    <p class="text-sm text-slate-500">Configure API keys and activation states for your connected payment providers.</p>
</div>
@endsection

@section('content')

@if(session('success'))
<div class="mb-6 rounded-lg border border-slate-200 bg-slate-900-container p-4 text-sm font-medium text-on-primary-container shadow-sm">
    {{ session('success') }}
</div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
    @foreach($gateways as $gateway)
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden flex flex-col">
        <div class="px-6 py-4 border-b border-slate-200 bg-white-container-low flex justify-between items-center">
            <h2 class="font-bold text-lg font-headline text-slate-900">{{ $gateway->name }}</h2>
            <span class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider rounded border {{ $gateway->is_enabled ? 'bg-slate-100 text-slate-900 border-slate-300' : 'bg-white-variant text-slate-500 border-slate-200' }}">
                {{ $gateway->is_enabled ? 'Active' : 'Disabled' }}
            </span>
        </div>
        
        <form method="POST" action="{{ route('admin.gateways.update', $gateway) }}" class="p-6 flex-1 flex flex-col gap-5">
            @csrf
            @method('PUT')

            <div class="flex items-center justify-between">
                <label class="text-sm font-semibold text-slate-900">Enable Gateway</label>
                <div class="relative inline-block w-12 align-middle select-none transition duration-200 ease-in">
                    <input type="checkbox" name="is_enabled" value="1" {{ $gateway->is_enabled ? 'checked' : '' }} class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer border-slate-200 transition-transform duration-200 ease-in-out {{ $gateway->is_enabled ? 'translate-x-6 border-slate-900' : '' }}"/>
                    <label class="toggle-label block overflow-hidden h-6 rounded-full bg-white-variant cursor-pointer {{ $gateway->is_enabled ? 'bg-slate-900-container' : '' }}"></label>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <label class="text-sm font-semibold text-slate-900">Environment</label>
                <select name="mode" class="rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring focus:ring-slate-900/20">
                    <option value="test" {{ $gateway->mode === 'test' ? 'selected' : '' }}>Test Mode</option>
                    <option value="live" {{ $gateway->mode === 'live' ? 'selected' : '' }}>Live Production</option>
                </select>
            </div>

            <hr class="border-slate-200 my-2">

            @if($gateway->gateway_id === 'offline_transfer')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-900 mb-1">Method Label</label>
                        <input type="text" name="metadata[method_label]" value="{{ $gateway->metadata['method_label'] ?? 'Bank Transfer' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Bank Transfer">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-1">Bank Name</label>
                            <input type="text" name="metadata[bank_name]" value="{{ $gateway->metadata['bank_name'] ?? '' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Attijariwafa Bank">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-1">Account Holder</label>
                            <input type="text" name="metadata[account_holder]" value="{{ $gateway->metadata['account_holder'] ?? '' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="NinoWorld SARL AU">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-1">Account Number</label>
                            <input type="text" name="metadata[account_number]" value="{{ $gateway->metadata['account_number'] ?? '' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="12345678901234567890">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-1">IBAN</label>
                            <input type="text" name="metadata[iban]" value="{{ $gateway->metadata['iban'] ?? '' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="MA64001122334455667788990011">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-1">SWIFT Code</label>
                            <input type="text" name="metadata[swift_code]" value="{{ $gateway->metadata['swift_code'] ?? '' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="BCMAMAMC">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-1">Payment Window (Hours)</label>
                            <input type="number" min="1" max="168" name="metadata[payment_window_hours]" value="{{ $gateway->metadata['payment_window_hours'] ?? 48 }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="48">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-1">Reference Prefix</label>
                            <input type="text" name="metadata[reference_prefix]" value="{{ $gateway->metadata['reference_prefix'] ?? 'NINO' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="NINO">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-1">Require Receipt Confirmation</label>
                            <select name="metadata[require_receipt]" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                                <option value="1" {{ (bool) ($gateway->metadata['require_receipt'] ?? true) ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ ! (bool) ($gateway->metadata['require_receipt'] ?? true) ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-900 mb-1">Bank Instructions (Displayed to Customer)</label>
                        <textarea name="metadata[instructions]" rows="4" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring focus:ring-slate-900/20" placeholder="e.g. Please wire funds to CIH Account 123456789. Send receipt to contact@ninoworld.com">{{ $gateway->metadata['instructions'] ?? '' }}</textarea>
                    </div>
                </div>
            @elseif($gateway->gateway_id === 'stripe')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-900 mb-1">Publishable Key</label>
                        <input type="text" name="metadata[publishable_key]" value="{{ $gateway->metadata['publishable_key'] ?? '' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="pk_test_...">
                        <p class="text-xs text-slate-500 mt-1">Safe to expose to frontend.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-900 mb-1">Secret Key <span class="text-red-600">*</span></label>
                        {{-- Encrypted column, intentionally obfuscated --}}
                        <input type="password" name="credentials[secret_key]" value="{{ $gateway->getCredential('secret_key') ? '*******' : '' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="sk_test_..." >
                        <p class="text-xs text-slate-500 mt-1">Leave blank unless updating. Encrypted restly.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-900 mb-1">Webhook Secret <span class="text-red-600">*</span></label>
                        <input type="password" name="credentials[webhook_secret]" value="{{ $gateway->getCredential('webhook_secret') ? '*******' : '' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="whsec_...">
                        <p class="text-xs text-slate-500 mt-1">Used to verify inbound Stripe webhooks.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-900 mb-1">Currency</label>
                        <input type="text" name="metadata[currency]" value="{{ $gateway->metadata['currency'] ?? 'MAD' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="MAD">
                    </div>
                </div>
            @elseif($gateway->gateway_id === 'cmi')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-900 mb-1">Store ID</label>
                        <input type="text" name="credentials[store_id]" value="{{ $gateway->getCredential('store_id') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="CMI Store ID">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-900 mb-1">Client ID</label>
                        <input type="text" name="credentials[client_id]" value="{{ $gateway->getCredential('client_id') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="CMI Client ID">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-900 mb-1">Hash Key <span class="text-red-600">*</span></label>
                        <input type="password" name="credentials[hash_key]" value="{{ $gateway->getCredential('hash_key') ? '*******' : '' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="HMAC-SHA512 Secret">
                        <p class="text-xs text-slate-500 mt-1">Leave blank unless updating.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-1">Currency Code</label>
                            <input type="text" name="metadata[currency_code]" value="{{ $gateway->metadata['currency_code'] ?? '504' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="504 = MAD">
                            <p class="text-xs text-slate-500 mt-1">ISO 4217 numeric</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-1">Language</label>
                            <select name="metadata[language]" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                                <option value="fr" {{ ($gateway->metadata['language'] ?? 'fr') === 'fr' ? 'selected' : '' }}>Français</option>
                                <option value="ar" {{ ($gateway->metadata['language'] ?? '') === 'ar' ? 'selected' : '' }}>العربية</option>
                                <option value="en" {{ ($gateway->metadata['language'] ?? '') === 'en' ? 'selected' : '' }}>English</option>
                            </select>
                        </div>
                    </div>
                </div>
            @elseif($gateway->gateway_id === 'payzone')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-900 mb-1">Merchant ID</label>
                        <input type="text" name="credentials[merchant_id]" value="{{ $gateway->getCredential('merchant_id') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Payzone Merchant ID">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-900 mb-1">API Key <span class="text-red-600">*</span></label>
                        <input type="password" name="credentials[api_key]" value="{{ $gateway->getCredential('api_key') ? '*******' : '' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Payzone API Key">
                        <p class="text-xs text-slate-500 mt-1">Leave blank unless updating.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-900 mb-1">Secret Key <span class="text-red-600">*</span></label>
                        <input type="password" name="credentials[secret_key]" value="{{ $gateway->getCredential('secret_key') ? '*******' : '' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="HMAC Signing Secret">
                        <p class="text-xs text-slate-500 mt-1">Leave blank unless updating.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-900 mb-1">Currency</label>
                        <input type="text" name="metadata[currency]" value="{{ $gateway->metadata['currency'] ?? 'MAD' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="MAD">
                    </div>
                </div>
            @endif

            <div class="mt-auto pt-4 flex justify-end">
                <button type="submit" class="px-5 py-2 text-sm font-medium bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition-colors shadow-sm focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">Save Settings</button>
            </div>
        </form>
    </div>
    @endforeach
</div>

<style>
/* Quick toggle CSS since standard Tailwind doesn't have an exact matching component natively without plugins */
.toggle-checkbox:checked { right: 0; border-color: var(--color-primary); }
.toggle-checkbox { transition: all 0.3s; z-index: 2; }
.toggle-label { transition: all 0.3s; z-index: 1; }
</style>
@endsection
