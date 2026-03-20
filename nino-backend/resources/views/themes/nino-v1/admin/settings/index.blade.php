@extends('admin.layouts.app')

@section('header')
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-ink">Settings</h1>
    </div>
@endsection

@section('content')
    {{-- Tab Navigation --}}
    <div class="mb-6 border-b border-gray-200">
        <nav class="flex space-x-1 -mb-px" aria-label="Settings tabs">
            @foreach($groups as $groupKey => $groupLabel)
                <a href="{{ route('admin.settings.index', ['tab' => $groupKey]) }}"
                   class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors {{ $activeTab === $groupKey ? 'border-sage text-sage-dark' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    {{ $groupLabel }}
                </a>
            @endforeach
        </nav>
    </div>

    {{-- Settings Form --}}
    @if($activeTab === 'mail')
        <div class="space-y-6">
            @foreach($integrations as $integration)
            <form action="{{ route('admin.notifications.integrations.update', $integration) }}" method="POST" class="bg-white border border-gray-200 rounded-md shadow-sm overflow-hidden">
                @csrf @method('PUT')
                
                <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                    <div>
                        <h2 class="text-lg font-bold text-ink">{{ $integration->name }}</h2>
                        <p class="text-xs text-gray-500">Provider: <code class="bg-gray-100 px-1 rounded">{{ $integration->provider }}</code></p>
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_enabled" value="1" {{ $integration->is_enabled ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-sage/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-sage"></div>
                            <span class="ml-3 text-sm font-medium text-gray-700">Enabled</span>
                        </label>
                    </div>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @php $creds = $integration->credentials ?? []; @endphp

                    @if($integration->provider === 'smtp')
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">SMTP Host</label>
                            <input type="text" name="credentials[host]" value="{{ $creds['host'] ?? '' }}" class="input-field w-full" placeholder="smtp.gmail.com">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Port</label>
                            <input type="text" name="credentials[port]" value="{{ $creds['port'] ?? '587' }}" class="input-field w-full">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Username</label>
                            <input type="text" name="credentials[username]" value="{{ $creds['username'] ?? '' }}" class="input-field w-full">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Password</label>
                            <input type="password" name="credentials[password]" value="{{ !empty($creds['password']) ? '*******' : '' }}" class="input-field w-full">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Encryption</label>
                            <select name="credentials[encryption]" class="input-field w-full">
                                <option value="tls" {{ ($creds['encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                                <option value="ssl" {{ ($creds['encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                                <option value="" {{ empty($creds['encryption']) ? 'selected' : '' }}>None</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">From Address</label>
                            <input type="email" name="credentials[from_address]" value="{{ $creds['from_address'] ?? '' }}" class="input-field w-full" placeholder="noreply@ninoworld.com">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">From Name</label>
                            <input type="text" name="credentials[from_name]" value="{{ $creds['from_name'] ?? 'NinoWorld' }}" class="input-field w-full">
                        </div>

                    @elseif($integration->provider === 'twilio')
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Account SID</label>
                            <input type="text" name="credentials[account_sid]" value="{{ $creds['account_sid'] ?? '' }}" class="input-field w-full" placeholder="ACxxxxxxxx">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Auth Token</label>
                            <input type="password" name="credentials[auth_token]" value="{{ !empty($creds['auth_token']) ? '*******' : '' }}" class="input-field w-full">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">From Number</label>
                            <input type="text" name="credentials[from_number]" value="{{ $creds['from_number'] ?? '' }}" class="input-field w-full" placeholder="+1234567890">
                        </div>

                    @elseif($integration->provider === 'whatsapp_api')
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Access Token</label>
                            <input type="password" name="credentials[access_token]" value="{{ !empty($creds['access_token']) ? '*******' : '' }}" class="input-field w-full">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Phone Number ID</label>
                            <input type="text" name="credentials[phone_number_id]" value="{{ $creds['phone_number_id'] ?? '' }}" class="input-field w-full">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Business Account ID</label>
                            <input type="text" name="credentials[business_account_id]" value="{{ $creds['business_account_id'] ?? '' }}" class="input-field w-full">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">API Version</label>
                            <input type="text" name="credentials[api_version]" value="{{ $creds['api_version'] ?? 'v18.0' }}" class="input-field w-full">
                        </div>
                    @endif
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                    <button type="submit" class="btn-primary">Save {{ $integration->name }}</button>
                </div>
            </form>
            @endforeach
        </div>
    @else
        <div class="bg-white rounded-md border border-gray-200 shadow-sm">
            <form action="{{ route('admin.settings.update') }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="group" value="{{ $activeTab }}">

                <div class="p-6 space-y-6">
                    <h2 class="text-lg font-semibold text-ink border-b border-gray-100 pb-3">{{ $groups[$activeTab] ?? 'Settings' }}</h2>

                    @foreach($fields as $field)
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                            <label for="{{ $field['key'] }}" class="text-sm font-medium text-gray-700 pt-2">
                                {{ $field['label'] }}
                                @if($field['encrypt'] ?? false)
                                    <span class="ml-1 text-xs text-amber-500" title="This value is stored encrypted">🔒</span>
                                @endif
                            </label>
                            <div class="md:col-span-2">
                                @if($field['type'] === 'boolean')
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="{{ $field['key'] }}" value="0">
                                        <input type="checkbox" name="{{ $field['key'] }}" value="1"
                                               class="sr-only peer"
                                               {{ ($values[$field['key']] ?? false) ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-sage/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-sage"></div>
                                        <span class="ml-3 text-sm text-gray-600">{{ ($values[$field['key']] ?? false) ? 'Enabled' : 'Disabled' }}</span>
                                    </label>
                                @elseif($field['type'] === 'secret')
                                    <input type="password"
                                           name="{{ $field['key'] }}"
                                           id="{{ $field['key'] }}"
                                           class="input-field w-full"
                                           placeholder="{{ isset($values[$field['key']]) ? '••••••••' : ($field['placeholder'] ?? '') }}"
                                           autocomplete="off">
                                    @if(isset($values[$field['key']]))
                                        <p class="mt-1 text-xs text-gray-400">Leave blank to keep current value</p>
                                    @endif
                                @else
                                    <input type="{{ $field['type'] === 'integer' ? 'number' : 'text' }}"
                                           name="{{ $field['key'] }}"
                                           id="{{ $field['key'] }}"
                                           value="{{ old($field['key'], $values[$field['key']] ?? '') }}"
                                           class="input-field w-full"
                                           placeholder="{{ $field['placeholder'] ?? '' }}">
                                @endif

                                @error($field['key'])
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Save Button --}}
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-xl flex justify-end">
                    <button type="submit" class="btn-primary">
                        Save {{ $groups[$activeTab] ?? '' }} Settings
                    </button>
                </div>
            </form>
        </div>
    @endif
@endsection
