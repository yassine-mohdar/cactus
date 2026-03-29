@extends('admin.layouts.app')

@section('title', 'Channel Integrations')

@section('header')
    <x-nino.page-header
        title="Channel Integrations"
        subtitle="Manage provider credentials, enablement, and fallback readiness for every outbound channel.">
        <x-slot:actions>
            <x-nino.button href="{{ route('admin.notifications.templates.index') }}" variant="secondary" icon="description">Templates</x-nino.button>
            <x-nino.button href="{{ route('admin.notifications.logs.index') }}" variant="secondary" icon="history">Logs</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    @if(session('success'))
        <x-nino.inline-alert tone="success" title="Integration updated" class="mb-6">
            {{ session('success') }}
        </x-nino.inline-alert>
    @endif

    @if(session('error'))
        <x-nino.inline-alert tone="danger" title="Integration update failed" class="mb-6">
            {{ session('error') }}
        </x-nino.inline-alert>
    @endif

    @if(session('warning'))
        <x-nino.inline-alert tone="warning" title="Integration check" class="mb-6">
            {{ session('warning') }}
        </x-nino.inline-alert>
    @endif

    <div class="stats-grid mb-6">
        <div class="stat-card">
            <p class="stat-label">Channels</p>
            <p class="stat-value">{{ number_format($stats['channels']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Enabled</p>
            <p class="stat-value text-[#1F7A4E]">{{ number_format($stats['enabled']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Configured</p>
            <p class="stat-value text-[#3B6F95]">{{ number_format($stats['configured']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Credential Fields</p>
            <p class="stat-value text-[#B8802F]">{{ number_format($stats['credential_fields']) }}</p>
        </div>
    </div>

    <div class="filter-toolbar mb-6">
        <x-nino.tab-strip label="Notification workspace">
            <a href="{{ route('admin.notifications.integrations.index') }}" class="tab-pill tab-pill-active">Integrations</a>
            <a href="{{ route('admin.notifications.templates.index') }}" class="tab-pill">Templates</a>
            <a href="{{ route('admin.notifications.logs.index') }}" class="tab-pill">Delivery Logs</a>
        </x-nino.tab-strip>
    </div>

    <div class="space-y-6">
        @foreach($integrations as $integration)
            @php $creds = $integration->credentials ?? []; @endphp

            <form action="{{ route('admin.notifications.integrations.update', $integration) }}" method="POST">
                @csrf
                @method('PUT')

                <x-nino.integration-card
                    :title="$integration->name"
                    :provider="$integration->provider"
                    :enabled="$integration->is_enabled"
                    subtitle="Channel credentials stay masked on reload and are preserved until a real replacement value is submitted.">
                    <x-slot:header>
                        <label class="inline-flex items-center gap-3 self-start sm:self-center">
                            <span class="text-sm font-semibold text-[#17302A]">Enable Channel</span>
                            <span class="relative inline-flex h-7 w-[3.25rem] items-center">
                                <input type="checkbox" name="is_enabled" value="1" {{ $integration->is_enabled ? 'checked' : '' }} class="peer sr-only">
                                <span class="toggle-track"></span>
                                <span class="toggle-thumb"></span>
                            </span>
                        </label>
                    </x-slot:header>

                    <div class="entity-section-grid">
                        @if($integration->provider === 'smtp')
                            <div>
                                <label class="filter-label" for="smtp-host-{{ $integration->id }}">SMTP Host</label>
                                <input id="smtp-host-{{ $integration->id }}" type="text" name="credentials[host]" value="{{ $creds['host'] ?? '' }}" class="input-field" placeholder="smtp.gmail.com">
                            </div>
                            <div>
                                <label class="filter-label" for="smtp-port-{{ $integration->id }}">Port</label>
                                <input id="smtp-port-{{ $integration->id }}" type="text" name="credentials[port]" value="{{ $creds['port'] ?? '587' }}" class="input-field">
                            </div>
                            <div>
                                <label class="filter-label" for="smtp-user-{{ $integration->id }}">Username</label>
                                <input id="smtp-user-{{ $integration->id }}" type="text" name="credentials[username]" value="{{ $creds['username'] ?? '' }}" class="input-field">
                            </div>
                            <div>
                                <label class="filter-label" for="smtp-password-{{ $integration->id }}">Password</label>
                                <input id="smtp-password-{{ $integration->id }}" type="password" name="credentials[password]" value="{{ !empty($creds['password']) ? '*******' : '' }}" class="input-field">
                            </div>
                            <div>
                                <label class="filter-label" for="smtp-encryption-{{ $integration->id }}">Encryption</label>
                                <select id="smtp-encryption-{{ $integration->id }}" name="credentials[encryption]" class="input-field">
                                    <option value="tls" {{ ($creds['encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                                    <option value="ssl" {{ ($creds['encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                                    <option value="" {{ empty($creds['encryption']) ? 'selected' : '' }}>None</option>
                                </select>
                            </div>
                            <div>
                                <label class="filter-label" for="smtp-from-address-{{ $integration->id }}">From Address</label>
                                <input id="smtp-from-address-{{ $integration->id }}" type="email" name="credentials[from_address]" value="{{ $creds['from_address'] ?? '' }}" class="input-field" placeholder="noreply@ninoworld.com">
                            </div>
                            <div class="md:col-span-2">
                                <label class="filter-label" for="smtp-from-name-{{ $integration->id }}">From Name</label>
                                <input id="smtp-from-name-{{ $integration->id }}" type="text" name="credentials[from_name]" value="{{ $creds['from_name'] ?? 'NinoWorld' }}" class="input-field">
                            </div>
                        @elseif($integration->provider === 'twilio')
                            <div>
                                <label class="filter-label" for="twilio-sid-{{ $integration->id }}">Account SID</label>
                                <input id="twilio-sid-{{ $integration->id }}" type="text" name="credentials[account_sid]" value="{{ $creds['account_sid'] ?? '' }}" class="input-field" placeholder="ACxxxxxxxx">
                            </div>
                            <div>
                                <label class="filter-label" for="twilio-token-{{ $integration->id }}">Auth Token</label>
                                <input id="twilio-token-{{ $integration->id }}" type="password" name="credentials[auth_token]" value="{{ !empty($creds['auth_token']) ? '*******' : '' }}" class="input-field">
                            </div>
                            <div class="md:col-span-2">
                                <label class="filter-label" for="twilio-from-{{ $integration->id }}">From Number</label>
                                <input id="twilio-from-{{ $integration->id }}" type="text" name="credentials[from_number]" value="{{ $creds['from_number'] ?? '' }}" class="input-field" placeholder="+1234567890">
                            </div>
                        @elseif($integration->provider === 'whatsapp_api')
                            <div>
                                <label class="filter-label" for="wa-token-{{ $integration->id }}">Access Token</label>
                                <input id="wa-token-{{ $integration->id }}" type="password" name="credentials[access_token]" value="{{ !empty($creds['access_token']) ? '*******' : '' }}" class="input-field">
                            </div>
                            <div>
                                <label class="filter-label" for="wa-phone-id-{{ $integration->id }}">Phone Number ID</label>
                                <input id="wa-phone-id-{{ $integration->id }}" type="text" name="credentials[phone_number_id]" value="{{ $creds['phone_number_id'] ?? '' }}" class="input-field">
                            </div>
                            <div>
                                <label class="filter-label" for="wa-business-id-{{ $integration->id }}">Business Account ID</label>
                                <input id="wa-business-id-{{ $integration->id }}" type="text" name="credentials[business_account_id]" value="{{ $creds['business_account_id'] ?? '' }}" class="input-field">
                            </div>
                            <div>
                                <label class="filter-label" for="wa-api-version-{{ $integration->id }}">API Version</label>
                                <input id="wa-api-version-{{ $integration->id }}" type="text" name="credentials[api_version]" value="{{ $creds['api_version'] ?? 'v18.0' }}" class="input-field">
                            </div>
                        @endif
                    </div>

                    <x-slot:footer>
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs text-[#61706B]">Masked secrets stay unchanged until you submit a different value.</p>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.notifications.integrations.test', $integration) }}" class="inline-flex items-center rounded-md border border-[rgba(120,112,95,0.16)] bg-white px-3 py-2 text-sm font-semibold text-[#1E2B27] transition-colors hover:bg-[#F7F4EF]">
                                    Safe Test
                                </a>
                                <x-nino.button type="submit" variant="primary">Save {{ $integration->name }}</x-nino.button>
                            </div>
                        </div>
                    </x-slot:footer>
                </x-nino.integration-card>
            </form>
        @endforeach
    </div>
@endsection
