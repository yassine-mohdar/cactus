@extends('admin.layouts.app')

@section('title', 'Integration Settings')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Channel Integrations</h1>
    <p class="text-sm text-slate-500 mt-1">Configure SMTP, Twilio SMS, and WhatsApp API credentials.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm border border-green-200">{{ session('success') }}</div>
@endif

@if(session('warning'))
    <div class="mb-4 p-3 rounded-lg bg-amber-50 text-amber-700 text-sm border border-amber-200">{{ session('warning') }}</div>
@endif

@if(session('error'))
    <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm border border-red-200">{{ session('error') }}</div>
@endif

<div class="space-y-6">
    @foreach($integrations as $integration)
    <form action="{{ route('admin.notifications.integrations.update', $integration) }}" method="POST" class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
        @csrf @method('PUT')

        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900">{{ $integration->name }}</h2>
                <p class="text-xs text-slate-500">Provider: <code class="bg-white-dim px-1 rounded">{{ $integration->provider }}</code></p>
            </div>
            <div class="flex items-center gap-3">
                <input type="checkbox" name="is_enabled" value="1" id="enabled-{{ $integration->provider }}" {{ $integration->is_enabled ? 'checked' : '' }} class="rounded border-slate-200 text-slate-900 focus:ring-slate-900/20">
                <label for="enabled-{{ $integration->provider }}" class="text-sm font-medium text-slate-900">Enabled</label>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @php $creds = $integration->credentials ?? []; @endphp

            @if($integration->provider === 'smtp')
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">SMTP Host</label>
                    <input type="text" name="credentials[host]" value="{{ $creds['host'] ?? '' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="smtp.gmail.com">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Port</label>
                    <input type="text" name="credentials[port]" value="{{ $creds['port'] ?? '587' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Username</label>
                    <input type="text" name="credentials[username]" value="{{ $creds['username'] ?? '' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Password</label>
                    <input type="password" name="credentials[password]" value="{{ !empty($creds['password']) ? '*******' : '' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Encryption</label>
                    <select name="credentials[encryption]" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                        <option value="tls" {{ ($creds['encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                        <option value="ssl" {{ ($creds['encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                        <option value="" {{ empty($creds['encryption']) ? 'selected' : '' }}>None</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">From Address</label>
                    <input type="email" name="credentials[from_address]" value="{{ $creds['from_address'] ?? '' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="noreply@ninoworld.com">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-500 mb-1">From Name</label>
                    <input type="text" name="credentials[from_name]" value="{{ $creds['from_name'] ?? 'NinoWorld' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                </div>

            @elseif($integration->provider === 'twilio')
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Account SID</label>
                    <input type="text" name="credentials[account_sid]" value="{{ $creds['account_sid'] ?? '' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="ACxxxxxxxx">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Auth Token</label>
                    <input type="password" name="credentials[auth_token]" value="{{ !empty($creds['auth_token']) ? '*******' : '' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-500 mb-1">From Number</label>
                    <input type="text" name="credentials[from_number]" value="{{ $creds['from_number'] ?? '' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="+1234567890">
                </div>

            @elseif($integration->provider === 'whatsapp_api')
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Access Token</label>
                    <input type="password" name="credentials[access_token]" value="{{ !empty($creds['access_token']) ? '*******' : '' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Phone Number ID</label>
                    <input type="text" name="credentials[phone_number_id]" value="{{ $creds['phone_number_id'] ?? '' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Business Account ID</label>
                    <input type="text" name="credentials[business_account_id]" value="{{ $creds['business_account_id'] ?? '' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">API Version</label>
                    <input type="text" name="credentials[api_version]" value="{{ $creds['api_version'] ?? 'v18.0' }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                </div>
            @endif
        </div>

        <div class="flex justify-end gap-3 mt-4">
            <a href="{{ route('admin.notifications.integrations.test', $integration) }}" class="px-5 py-2 text-sm font-medium border border-slate-200 text-slate-900 rounded-lg hover:bg-slate-50 transition-colors">Safe Test</a>
            <button type="submit" class="px-5 py-2 text-sm font-medium bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition-colors shadow-sm">Save {{ $integration->name }}</button>
        </div>
    </form>
    @endforeach
</div>
@endsection
