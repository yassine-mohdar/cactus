@extends('admin.layouts.app')

@section('title', 'Settings')

@section('header')
    <div class="page-header">
        <div>
            <h1 class="page-title">Settings</h1>
            <p class="page-subtitle">Configure platform defaults, brand metadata, delivery settings, and security controls from one consolidated workspace.</p>
        </div>
        <span class="datatable-meta">{{ $groups[$activeTab] ?? 'Configuration' }}</span>
    </div>
@endsection

@section('content')
    @php
        $secureFieldCount = collect($fields)->where('encrypt', true)->count();
        $enabledIntegrations = collect($integrations)->where('is_enabled', true)->count();
        $fieldSections = collect($fields)->groupBy(fn ($field) => $field['section'] ?? 'Settings');
    @endphp

    @if(session('success'))
        <x-nino.inline-alert tone="success" title="Settings updated" class="mb-6">
            {{ session('success') }}
        </x-nino.inline-alert>
    @endif

    @if(session('error'))
        <x-nino.inline-alert tone="danger" title="Update failed" class="mb-6">
            {{ session('error') }}
        </x-nino.inline-alert>
    @endif

    @if(session('warning'))
        <x-nino.inline-alert tone="warning" title="Configuration check" class="mb-6">
            {{ session('warning') }}
        </x-nino.inline-alert>
    @endif

    <div class="stats-grid mb-6">
        <div class="stat-card">
            <p class="stat-label">Configuration Areas</p>
            <p class="stat-value">{{ number_format(count($groups)) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Fields In This Section</p>
            <p class="stat-value">{{ number_format(count($fields)) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Protected Fields</p>
            <p class="stat-value">{{ number_format($secureFieldCount) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Live Mail Providers</p>
            <p class="stat-value">{{ number_format($enabledIntegrations) }}</p>
        </div>
    </div>

    <div class="filter-toolbar mb-6">
        <x-nino.tab-strip label="Configuration areas" class="items-center">
            @foreach($groups as $groupKey => $groupLabel)
                <a
                    href="{{ route('admin.settings.index', ['tab' => $groupKey]) }}"
                    class="tab-pill {{ $activeTab === $groupKey ? 'tab-pill-active' : '' }}"
                >
                    {{ $groupLabel }}
                </a>
            @endforeach
        </x-nino.tab-strip>
    </div>

    @if($activeTab === 'mail')
        <div class="space-y-6">
            @forelse($integrations as $integration)
                @php $creds = $integration->credentials ?? []; @endphp

                <form action="{{ route('admin.notifications.integrations.update', $integration) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <x-nino.integration-card
                        :title="$integration->name"
                        :provider="$integration->provider"
                        :enabled="$integration->is_enabled">
                        <x-slot:header>
                            <label class="inline-flex items-center gap-3 self-start sm:self-center">
                                <span class="text-sm font-semibold text-[#17302A]">Enable Provider</span>
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
                            <div class="flex items-center justify-end">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('admin.notifications.integrations.test', $integration) }}" class="inline-flex items-center rounded-md border border-[rgba(120,112,95,0.16)] bg-white px-3 py-2 text-sm font-semibold text-[#1E2B27] transition-colors hover:bg-[#F7F4EF]">
                                        Safe Test
                                    </a>
                                    <x-nino.button type="submit" variant="primary">
                                        Save {{ $integration->name }}
                                    </x-nino.button>
                                </div>
                            </div>
                        </x-slot:footer>
                    </x-nino.integration-card>
                </form>
            @empty
                <div class="empty-state">
                    No notification integrations are configured yet.
                </div>
            @endforelse
        </div>
    @else
        <form action="{{ route('admin.settings.update') }}" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="group" value="{{ $activeTab }}">

            <x-nino.settings-panel :title="$groups[$activeTab] ?? 'Settings'" subtitle="Update the defaults and operational rules that drive this area of the platform.">
                <div class="space-y-6">
                    @foreach($fieldSections as $section => $sectionFields)
                        <div class="rounded-2xl border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] p-5 shadow-[0_1px_2px_rgba(17,24,39,0.05)]">
                            <div class="mb-5 border-b border-[rgba(120,112,95,0.12)] pb-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#7A8681]">{{ $groups[$activeTab] ?? 'Settings' }}</p>
                                <h2 class="mt-2 text-lg font-bold text-[#17302A]">{{ $section }}</h2>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-2">
                                @foreach($sectionFields as $field)
                                    <div class="rounded-xl border border-[rgba(120,112,95,0.14)] bg-white p-5 shadow-[0_1px_2px_rgba(17,24,39,0.05)]">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <label for="{{ $field['key'] }}" class="text-sm font-bold text-[#17302A]">{{ $field['label'] }}</label>
                                                <p class="mt-1 text-xs leading-5 text-[#708078]">
                                                    @if($field['encrypt'] ?? false)
                                                        Stored as a protected secret.
                                                    @elseif($field['type'] === 'boolean')
                                                        Toggle this operational rule on or off.
                                                    @else
                                                        {{ $field['placeholder'] !== '' ? 'Suggested value: '.$field['placeholder'] : 'Update this setting as needed for the current workflow.' }}
                                                    @endif
                                                </p>
                                            </div>

                                            @if($field['encrypt'] ?? false)
                                                <span class="inline-flex items-center rounded-full border border-[#ECD9A9] bg-[#FFF7E4] px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.22em] text-[#8A671E]">
                                                    Locked
                                                </span>
                                            @endif
                                        </div>

                                        <div class="mt-4">
                                            @if($field['type'] === 'boolean')
                                                <label class="inline-flex items-center gap-3">
                                                    <input type="hidden" name="{{ $field['key'] }}" value="0">
                                                    <span class="relative inline-flex h-7 w-[3.25rem] items-center">
                                                        <input
                                                            type="checkbox"
                                                            name="{{ $field['key'] }}"
                                                            value="1"
                                                            class="peer sr-only"
                                                            {{ ($values[$field['key']] ?? false) ? 'checked' : '' }}
                                                        >
                                                        <span class="toggle-track"></span>
                                                        <span class="toggle-thumb"></span>
                                                    </span>
                                                    <span class="text-sm font-semibold text-[#17302A]">
                                                        {{ ($values[$field['key']] ?? false) ? 'Enabled' : 'Disabled' }}
                                                    </span>
                                                </label>
                                            @elseif($field['type'] === 'secret')
                                                <input
                                                    type="password"
                                                    name="{{ $field['key'] }}"
                                                    id="{{ $field['key'] }}"
                                                    class="input-field"
                                                    placeholder="{{ isset($values[$field['key']]) ? '••••••••' : ($field['placeholder'] ?? '') }}"
                                                    autocomplete="off"
                                                >
                                                @if(isset($values[$field['key']]))
                                                    <p class="mt-2 text-xs text-[#708078]">Leave blank to keep the current secret.</p>
                                                @endif
                                            @elseif($field['type'] === 'textarea')
                                                <textarea
                                                    name="{{ $field['key'] }}"
                                                    id="{{ $field['key'] }}"
                                                    class="input-field min-h-[7rem]"
                                                    placeholder="{{ $field['placeholder'] ?? '' }}"
                                                >{{ old($field['key'], $values[$field['key']] ?? '') }}</textarea>
                                            @else
                                                <input
                                                    type="{{ $field['type'] === 'integer' ? 'number' : $field['type'] }}"
                                                    name="{{ $field['key'] }}"
                                                    id="{{ $field['key'] }}"
                                                    value="{{ old($field['key'], $values[$field['key']] ?? '') }}"
                                                    class="input-field"
                                                    placeholder="{{ $field['placeholder'] ?? '' }}"
                                                >
                                            @endif

                                            @error($field['key'])
                                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                <x-slot:footer>
                    <div class="flex items-center justify-end">
                        <x-nino.button type="submit" variant="primary">
                            Save {{ $groups[$activeTab] ?? 'Settings' }}
                        </x-nino.button>
                    </div>
                </x-slot:footer>
            </x-nino.settings-panel>
        </form>
    @endif
@endsection
