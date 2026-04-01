@extends('admin.layouts.app')

@section('title', 'Payments & Gateways')

@php
    $selectedTab = $selectedTab ?? 'methods';
    $selectedGateway = $selectedGateway ?? null;
    $editingMethod = $editingMethod ?? null;
    $isCreatingMethod = request()->query('method') === 'new';
    $methodDraft = $editingMethod;

    if (! $methodDraft && $isCreatingMethod) {
        $methodDraft = new \App\Modules\Payments\Models\PaymentMethod([
            'channel' => \App\Modules\Payments\Models\PaymentMethod::CHANNEL_OFFLINE,
            'behavior' => \App\Modules\Payments\Models\PaymentMethod::BEHAVIOR_OFFLINE_MANUAL,
            'is_enabled' => true,
            'sort_order' => ($paymentMethods->max('sort_order') ?? 0) + 10,
            'metadata' => [
                'method_label' => '',
                'checkout_title' => '',
                'checkout_description' => '',
                'instructions' => '',
                'admin_instructions' => '',
                'payment_window_hours' => 48,
                'reference_prefix' => 'NINO',
                'require_receipt' => false,
            ],
        ]);
    }

    $enabledMethodsCount = $paymentMethods->where('is_enabled', true)->count();
    $offlineCount = $paymentMethods->where('channel', 'offline')->count();
    $carrierLinkedCount = $paymentMethods->filter(fn ($method) => $method->shippingCarriers->isNotEmpty())->count();
    $providerGateways = $gateways;
@endphp

@section('header')
    <div class="page-header">
        <div>
            <h1 class="page-title">Payments &amp; Gateways</h1>
            <p class="page-subtitle">Manage checkout-visible payment methods separately from fixed provider credentials, with carrier-linked COD and offline workflows.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <x-nino.button
                href="{{ route('admin.gateways.index', ['tab' => 'methods', 'method' => 'new']) }}"
                :navigate="false"
                variant="primary"
                icon="add_card">
                Add Offline Method
            </x-nino.button>
        </div>
    </div>
@endsection

@section('content')
    @if(session('success'))
        <x-nino.inline-alert tone="success" title="Payments workspace updated" class="mb-5">
            {{ session('success') }}
        </x-nino.inline-alert>
    @endif

    <div class="stats-grid mb-6">
        <div class="stat-card">
            <p class="stat-label">Checkout Methods</p>
            <p class="stat-value">{{ number_format($paymentMethods->count()) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Enabled</p>
            <p class="stat-value text-[#0F7A54]">{{ number_format($enabledMethodsCount) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Offline Methods</p>
            <p class="stat-value">{{ number_format($offlineCount) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Carrier Linked</p>
            <p class="stat-value text-[#8C5E2F]">{{ number_format($carrierLinkedCount) }}</p>
        </div>
    </div>

    <div class="mb-5 flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.gateways.index', ['tab' => 'methods']) }}"
           class="inline-flex items-center rounded-full border px-4 py-2 text-sm font-semibold transition {{ $selectedTab === 'methods' ? 'border-[#245848]/20 bg-[#EFF4F1] text-[#245848]' : 'border-[rgba(145,133,109,0.18)] bg-white text-[#617169] hover:text-[#17302A]' }}">
            Checkout Methods
        </a>
        <a href="{{ route('admin.gateways.index', ['tab' => 'providers', 'gateway' => $selectedGateway?->gateway_id ?? 'cmi']) }}"
           class="inline-flex items-center rounded-full border px-4 py-2 text-sm font-semibold transition {{ $selectedTab === 'providers' ? 'border-[#245848]/20 bg-[#EFF4F1] text-[#245848]' : 'border-[rgba(145,133,109,0.18)] bg-white text-[#617169] hover:text-[#17302A]' }}">
            Provider Connections
        </a>
    </div>

    @if($selectedTab === 'methods')
        <div class="grid gap-6 xl:grid-cols-[minmax(0,0.95fr)_minmax(24rem,0.9fr)]">
            <section class="page-section p-5">
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <p class="filter-label">Checkout Catalog</p>
                        <h2 class="mt-1 text-lg font-bold tracking-tight text-[#17302A]">Methods</h2>
                        <p class="mt-1 text-sm text-[#617169]">Search and filter the methods customers and operators can actually choose.</p>
                    </div>
                    <span class="datatable-meta">{{ number_format($paymentMethods->count()) }} visible</span>
                </div>

                <form method="GET" class="filter-toolbar mb-4">
                    <input type="hidden" name="tab" value="methods">
                    <div class="filter-grid xl:grid-cols-4">
                        <div class="filter-field xl:col-span-2">
                            <label class="filter-label" for="pm-search">Search</label>
                            <input id="pm-search" type="text" name="search" value="{{ request('search') }}" class="input-field" placeholder="Name, code, or behavior">
                        </div>
                        <div class="filter-field">
                            <label class="filter-label" for="pm-channel">Channel</label>
                            <select id="pm-channel" name="channel" class="input-field">
                                <option value="">All</option>
                                <option value="offline" @selected(request('channel') === 'offline')>Offline</option>
                                <option value="online" @selected(request('channel') === 'online')>Online</option>
                            </select>
                        </div>
                        <div class="filter-field">
                            <label class="filter-label" for="pm-behavior">Behavior</label>
                            <select id="pm-behavior" name="behavior" class="input-field">
                                <option value="">All</option>
                                <option value="gateway" @selected(request('behavior') === 'gateway')>Gateway</option>
                                <option value="offline_manual" @selected(request('behavior') === 'offline_manual')>Offline Manual</option>
                                <option value="cod" @selected(request('behavior') === 'cod')>COD Workflow</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <label class="inline-flex items-center gap-2 text-sm text-[#617169]">
                            <input type="checkbox" name="carrier_linked" value="1" @checked(request()->boolean('carrier_linked'))>
                            Carrier linked only
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-[#617169]">
                            <input type="checkbox" name="enabled" value="1" @checked(request()->filled('enabled') && request()->boolean('enabled'))>
                            Enabled only
                        </label>
                        <x-nino.button type="submit" size="sm" variant="secondary">Apply</x-nino.button>
                        @if(request()->hasAny(['search', 'channel', 'behavior', 'carrier_linked', 'enabled']))
                            <x-nino.button href="{{ route('admin.gateways.index', ['tab' => 'methods']) }}" :navigate="false" size="sm" variant="ghost">Clear</x-nino.button>
                        @endif
                    </div>
                </form>

                <div class="space-y-3">
                    @forelse($paymentMethods as $method)
                        @php
                            $isActive = (string) request('method') === (string) $method->id;
                            $isManagedByProvider = $method->isOnline();
                        @endphp
                        <div class="rounded-[1.25rem] border {{ $isActive ? 'border-[#245848]/18 bg-[#F7FBF8]' : 'border-[rgba(145,133,109,0.16)] bg-[#FCFBF8]' }} p-4 shadow-[0_18px_42px_-34px_rgba(23,48,42,0.18)]">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-semibold text-[#17302A]">{{ $method->checkoutLabel() }}</p>
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] {{ $method->is_enabled ? 'border-[#D7E5DB] bg-[#ECF4EE] text-[#245848]' : 'border-[rgba(145,133,109,0.18)] bg-white text-[#6E7D75]' }}">
                                            {{ $method->is_enabled ? 'Enabled' : 'Disabled' }}
                                        </span>
                                        <span class="inline-flex items-center rounded-full border border-[rgba(145,133,109,0.18)] bg-white px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-[#6E7D75]">
                                            {{ str($method->behavior)->replace('_', ' ')->upper() }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-xs uppercase tracking-[0.22em] text-[#8A7964]">{{ strtoupper($method->channel) }} · {{ $method->code }}</p>
                                    <p class="mt-2 text-sm text-[#617169]">{{ $method->checkoutDescription() ?: 'No checkout description configured yet.' }}</p>
                                </div>
                                <div class="flex shrink-0 flex-col items-end gap-2">
                                    @if($isManagedByProvider)
                                        <x-nino.button href="{{ route('admin.gateways.index', ['tab' => 'providers', 'gateway' => $method->providerGatewayId()]) }}" :navigate="false" size="sm" variant="secondary">
                                            Open Provider
                                        </x-nino.button>
                                    @else
                                        <x-nino.button href="{{ route('admin.gateways.index', ['tab' => 'methods', 'method' => $method->id]) }}" :navigate="false" size="sm" variant="{{ $isActive ? 'primary' : 'secondary' }}">
                                            Edit
                                        </x-nino.button>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap items-center gap-2 text-xs text-[#617169]">
                                @if($method->shippingCarriers->isNotEmpty())
                                    <span class="font-semibold text-[#17302A]">Carriers:</span>
                                    @foreach($method->shippingCarriers as $carrier)
                                        <span class="inline-flex items-center rounded-full bg-white px-2.5 py-1">{{ $carrier->name }}</span>
                                    @endforeach
                                @else
                                    <span class="inline-flex items-center rounded-full bg-white px-2.5 py-1">Available without carrier filter</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <x-nino.empty-state
                            title="No payment methods matched"
                            description="Try broader filters or clear the workspace search."
                            icon="payments" />
                    @endforelse
                </div>
            </section>

            <section class="page-section p-5">
                @if($methodDraft)
                    @php
                        $isProviderManaged = $methodDraft->exists && $methodDraft->isOnline();
                        $formAction = $methodDraft->exists
                            ? route('admin.gateways.methods.update', $methodDraft)
                            : route('admin.gateways.methods.store');
                        $selectedCarriers = old('carrier_ids', $methodDraft->exists ? $methodDraft->shippingCarriers->pluck('id')->all() : []);
                        $behaviorValue = old('behavior', $methodDraft->behavior ?: \App\Modules\Payments\Models\PaymentMethod::BEHAVIOR_OFFLINE_MANUAL);
                    @endphp

                    <div class="mb-4 flex items-start justify-between gap-4">
                        <div>
                            <p class="filter-label">{{ $methodDraft->exists ? 'Method Editor' : 'New Offline Method' }}</p>
                            <h2 class="mt-1 text-lg font-bold tracking-tight text-[#17302A]">
                                {{ $methodDraft->exists ? $methodDraft->checkoutLabel() : 'Create Offline Method' }}
                            </h2>
                            <p class="mt-1 text-sm text-[#617169]">
                                @if($isProviderManaged)
                                    Provider-backed methods inherit availability from their processor connection. Use provider connections for credentials and enablement.
                                @else
                                    Configure how this method appears in checkout, whether it uses COD workflow, and which carriers can expose it.
                                @endif
                            </p>
                        </div>
                        @if($methodDraft->exists && ! $isProviderManaged)
                            <form method="POST" action="{{ route('admin.gateways.methods.toggle', $methodDraft) }}">
                                @csrf
                                <x-nino.button type="submit" size="sm" variant="secondary">
                                    {{ $methodDraft->is_enabled ? 'Disable' : 'Enable' }}
                                </x-nino.button>
                            </form>
                        @endif
                    </div>

                    @if($isProviderManaged)
                        <div class="rounded-[1.25rem] border border-[rgba(145,133,109,0.16)] bg-[#FCFBF8] p-5">
                            <div class="space-y-3 text-sm text-[#617169]">
                                <p><span class="font-semibold text-[#17302A]">Method:</span> {{ $methodDraft->checkoutLabel() }}</p>
                                <p><span class="font-semibold text-[#17302A]">Behavior:</span> Provider connection ({{ strtoupper($methodDraft->providerGatewayId() ?? 'gateway') }})</p>
                                <p><span class="font-semibold text-[#17302A]">Status:</span> {{ $methodDraft->is_enabled ? 'Enabled from provider connection' : 'Disabled until provider connection is enabled' }}</p>
                            </div>
                            <div class="mt-5">
                                <x-nino.button href="{{ route('admin.gateways.index', ['tab' => 'providers', 'gateway' => $methodDraft->providerGatewayId()]) }}" :navigate="false" variant="primary">
                                    Manage Provider Connection
                                </x-nino.button>
                            </div>
                        </div>
                    @else
                        <form method="POST" action="{{ $formAction }}" class="space-y-5">
                            @csrf
                            @if($methodDraft->exists)
                                @method('PUT')
                            @endif

                            <div class="grid gap-4 md:grid-cols-2">
                                <x-admin.input type="text" name="name" label="Name" :value="old('name', $methodDraft->name)" placeholder="Bank Deposit" />
                                <x-admin.input type="text" name="code" label="Code" :value="old('code', $methodDraft->code)" placeholder="bank_deposit" />
                                <div>
                                    <x-admin.select name="behavior" label="Behavior">
                                        <option value="offline_manual" @selected($behaviorValue === 'offline_manual')>Offline Manual Verification</option>
                                        <option value="cod" @selected($behaviorValue === 'cod')>Enable COD Workflow</option>
                                    </x-admin.select>
                                </div>
                                <x-admin.input type="number" min="0" max="9999" name="sort_order" label="Sort Order" :value="old('sort_order', $methodDraft->sort_order ?? 0)" />
                            </div>

                            <label class="inline-flex items-center gap-3 rounded-[1.15rem] border border-[rgba(145,133,109,0.18)] bg-[#FCFBF8] px-4 py-3 text-sm font-medium text-[#17302A]">
                                <input type="checkbox" name="is_enabled" value="1" @checked((bool) old('is_enabled', $methodDraft->is_enabled))>
                                Enable this method for checkout
                            </label>

                            <div class="grid gap-4 md:grid-cols-2">
                                <x-admin.input type="text" name="metadata[method_label]" label="Checkout Label" :value="old('metadata.method_label', data_get($methodDraft->metadata, 'method_label'))" placeholder="Bank Deposit" />
                                <x-admin.input type="text" name="metadata[checkout_title]" label="Checkout Title" :value="old('metadata.checkout_title', data_get($methodDraft->metadata, 'checkout_title'))" placeholder="Payment instructions" />
                                <x-admin.input type="text" name="metadata[checkout_description]" label="Checkout Description" :value="old('metadata.checkout_description', data_get($methodDraft->metadata, 'checkout_description'))" placeholder="Explain when the customer should use this method." />
                                <x-admin.input type="number" min="1" max="336" name="metadata[payment_window_hours]" label="Payment Window (Hours)" :value="old('metadata.payment_window_hours', data_get($methodDraft->metadata, 'payment_window_hours', 48))" placeholder="48" />
                                <x-admin.input type="text" name="metadata[reference_prefix]" label="Reference Prefix" :value="old('metadata.reference_prefix', data_get($methodDraft->metadata, 'reference_prefix', 'NINO'))" placeholder="NINO" />
                                <div>
                                    <x-admin.select name="metadata[require_receipt]" label="Require Receipt">
                                        <option value="0" @selected(! (bool) old('metadata.require_receipt', data_get($methodDraft->metadata, 'require_receipt'))) >No</option>
                                        <option value="1" @selected((bool) old('metadata.require_receipt', data_get($methodDraft->metadata, 'require_receipt'))) >Yes</option>
                                    </x-admin.select>
                                </div>
                            </div>

                            <x-admin.textarea name="metadata[instructions]" label="Customer Instructions" rows="4" placeholder="Explain what the customer should do after choosing this method.">{{ old('metadata.instructions', data_get($methodDraft->metadata, 'instructions')) }}</x-admin.textarea>
                            <x-admin.textarea name="metadata[admin_instructions]" label="Admin Verification Notes" rows="4" placeholder="Internal operational guidance for support or finance.">{{ old('metadata.admin_instructions', data_get($methodDraft->metadata, 'admin_instructions')) }}</x-admin.textarea>

                            <div class="rounded-[1.25rem] border border-[rgba(145,133,109,0.18)] bg-[#FCFBF8] p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-[#17302A]">Linked Shipping Carriers</p>
                                        <p class="mt-1 text-sm text-[#617169]">Leave empty to show the method for any shipping carrier. COD workflow should be tied to the carriers that actually collect cash.</p>
                                    </div>
                                    @if($behaviorValue === 'cod')
                                        <span class="inline-flex items-center rounded-full border border-[#EFD7B6] bg-[#FBF6EA] px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-[#8C5E2F]">Required for COD</span>
                                    @endif
                                </div>
                                <div class="mt-4 grid gap-3 md:grid-cols-2">
                                    @foreach($carrierOptions as $carrier)
                                        <label class="inline-flex items-center gap-3 rounded-[1rem] border border-[rgba(145,133,109,0.15)] bg-white px-3 py-3 text-sm text-[#17302A]">
                                            <input type="checkbox" name="carrier_ids[]" value="{{ $carrier->id }}" @checked(in_array($carrier->id, $selectedCarriers, true))>
                                            <span>
                                                <span class="block font-semibold">{{ $carrier->name }}</span>
                                                <span class="block text-xs uppercase tracking-[0.18em] text-[#8A7964]">{{ $carrier->provider }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[rgba(145,133,109,0.16)] pt-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if($methodDraft->exists && ! in_array($methodDraft->code, ['cod', 'bank_transfer'], true))
                                        <form method="POST" action="{{ route('admin.gateways.methods.destroy', $methodDraft) }}" onsubmit="return confirm('Delete this payment method?');">
                                            @csrf
                                            @method('DELETE')
                                            <x-nino.button type="submit" size="sm" variant="ghost">Delete Method</x-nino.button>
                                        </form>
                                    @endif
                                    <x-nino.button href="{{ route('admin.gateways.index', ['tab' => 'methods']) }}" :navigate="false" size="sm" variant="secondary">Close Editor</x-nino.button>
                                </div>
                                <x-nino.button type="submit" variant="primary">
                                    {{ $methodDraft->exists ? 'Save Method' : 'Create Method' }}
                                </x-nino.button>
                            </div>
                        </form>
                    @endif
                @else
                    <x-nino.empty-state
                        title="Select or create a method"
                        description="Choose a method from the left list or add a new offline method to start configuring checkout behavior."
                        icon="payments" />
                @endif
            </section>
        </div>
    @else
        <div class="grid gap-6 xl:grid-cols-[minmax(0,0.82fr)_minmax(26rem,0.95fr)]">
            <section class="page-section p-5">
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <p class="filter-label">Fixed Processors</p>
                        <h2 class="mt-1 text-lg font-bold tracking-tight text-[#17302A]">Provider Connections</h2>
                        <p class="mt-1 text-sm text-[#617169]">Maintain processor credentials separately from the checkout method catalog.</p>
                    </div>
                    <span class="datatable-meta">{{ number_format($providerGateways->count()) }} providers</span>
                </div>

                <div class="space-y-3">
                    @foreach($providerGateways as $gateway)
                        <a href="{{ route('admin.gateways.index', ['tab' => 'providers', 'gateway' => $gateway->gateway_id]) }}"
                           class="block rounded-[1.25rem] border {{ $selectedGateway?->id === $gateway->id ? 'border-[#245848]/18 bg-[#F7FBF8]' : 'border-[rgba(145,133,109,0.16)] bg-[#FCFBF8]' }} p-4 shadow-[0_18px_42px_-34px_rgba(23,48,42,0.18)]">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-[#17302A]">{{ $gateway->name }}</p>
                                    <p class="mt-2 text-xs uppercase tracking-[0.22em] text-[#8A7964]">{{ strtoupper($gateway->gateway_id) }} · {{ strtoupper($gateway->mode) }}</p>
                                </div>
                                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] {{ $gateway->is_enabled ? 'border-[#D7E5DB] bg-[#ECF4EE] text-[#245848]' : 'border-[rgba(145,133,109,0.18)] bg-white text-[#6E7D75]' }}">
                                    {{ $gateway->is_enabled ? 'Enabled' : 'Disabled' }}
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>

            <section class="page-section p-5">
                @if($selectedGateway)
                    <div class="mb-4">
                        <p class="filter-label">Provider Editor</p>
                        <h2 class="mt-1 text-lg font-bold tracking-tight text-[#17302A]">{{ $selectedGateway->name }}</h2>
                        <p class="mt-1 text-sm text-[#617169]">Update the processor credentials, operating mode, and activation state for this provider only.</p>
                    </div>

                    <form method="POST" action="{{ route('admin.gateways.update', $selectedGateway) }}" class="space-y-5">
                        @csrf
                        @method('PUT')

                        <label class="inline-flex items-center gap-3 rounded-[1.15rem] border border-[rgba(145,133,109,0.18)] bg-[#FCFBF8] px-4 py-3 text-sm font-medium text-[#17302A]">
                            <input type="checkbox" name="is_enabled" value="1" {{ $selectedGateway->is_enabled ? 'checked' : '' }}>
                            Enable this provider connection
                        </label>

                        <div class="grid gap-4 md:grid-cols-2">
                            <x-admin.select name="mode" label="Environment">
                                <option value="test" {{ $selectedGateway->mode === 'test' ? 'selected' : '' }}>Test Mode</option>
                                <option value="live" {{ $selectedGateway->mode === 'live' ? 'selected' : '' }}>Live Production</option>
                            </x-admin.select>
                        </div>

                        @if($selectedGateway->gateway_id === 'stripe')
                            <div class="grid gap-4 md:grid-cols-2">
                                <x-admin.input type="text" name="metadata[publishable_key]" label="Publishable Key" :value="$selectedGateway->metadata['publishable_key'] ?? ''" placeholder="pk_test_..." />
                                <x-admin.input type="password" name="credentials[secret_key]" label="Secret Key" :value="$selectedGateway->getCredential('secret_key') ? '*******' : ''" placeholder="sk_test_..." />
                                <x-admin.input type="password" name="credentials[webhook_secret]" label="Webhook Secret" :value="$selectedGateway->getCredential('webhook_secret') ? '*******' : ''" placeholder="whsec_..." />
                                <x-admin.input type="text" name="metadata[currency]" label="Currency" :value="$selectedGateway->metadata['currency'] ?? 'MAD'" placeholder="MAD" />
                            </div>
                        @elseif($selectedGateway->gateway_id === 'cmi')
                            <div class="grid gap-4 md:grid-cols-2">
                                <x-admin.input type="text" name="credentials[store_id]" label="Store ID" :value="$selectedGateway->getCredential('store_id')" placeholder="CMI Store ID" />
                                <x-admin.input type="text" name="credentials[client_id]" label="Client ID" :value="$selectedGateway->getCredential('client_id')" placeholder="CMI Client ID" />
                                <x-admin.input type="password" name="credentials[hash_key]" label="Hash Key" :value="$selectedGateway->getCredential('hash_key') ? '*******' : ''" placeholder="HMAC-SHA512 secret" />
                                <x-admin.input type="text" name="credentials[terminal_id]" label="Terminal ID" :value="$selectedGateway->getCredential('terminal_id')" placeholder="Optional terminal identifier" />
                                <x-admin.input type="text" name="metadata[currency_code]" label="Currency Code" :value="$selectedGateway->metadata['currency_code'] ?? '504'" placeholder="504" />
                                <x-admin.select name="metadata[language]" label="Language">
                                    <option value="fr" {{ ($selectedGateway->metadata['language'] ?? 'fr') === 'fr' ? 'selected' : '' }}>Français</option>
                                    <option value="ar" {{ ($selectedGateway->metadata['language'] ?? '') === 'ar' ? 'selected' : '' }}>العربية</option>
                                    <option value="en" {{ ($selectedGateway->metadata['language'] ?? '') === 'en' ? 'selected' : '' }}>English</option>
                                </x-admin.select>
                            </div>
                        @elseif($selectedGateway->gateway_id === 'payzone')
                            <div class="grid gap-4 md:grid-cols-2">
                                <x-admin.input type="text" name="credentials[merchant_id]" label="Merchant ID" :value="$selectedGateway->getCredential('merchant_id')" placeholder="Payzone Merchant ID" />
                                <x-admin.input type="password" name="credentials[api_key]" label="API Key" :value="$selectedGateway->getCredential('api_key') ? '*******' : ''" placeholder="Payzone API key" />
                                <x-admin.input type="password" name="credentials[secret_key]" label="Secret Key" :value="$selectedGateway->getCredential('secret_key') ? '*******' : ''" placeholder="HMAC signing secret" />
                                <x-admin.input type="text" name="metadata[currency]" label="Currency" :value="$selectedGateway->metadata['currency'] ?? 'MAD'" placeholder="MAD" />
                            </div>
                        @elseif($selectedGateway->gateway_id === 'offline_transfer')
                            <div class="grid gap-4 md:grid-cols-2">
                                <x-admin.input type="text" name="metadata[method_label]" label="Fallback Method Label" :value="$selectedGateway->metadata['method_label'] ?? 'Bank Transfer'" placeholder="Bank Transfer" />
                                <x-admin.input type="text" name="metadata[checkout_title]" label="Fallback Checkout Title" :value="$selectedGateway->metadata['checkout_title'] ?? 'Bank transfer instructions'" placeholder="Bank transfer instructions" />
                                <x-admin.input type="number" min="1" max="168" name="metadata[payment_window_hours]" label="Fallback Payment Window (Hours)" :value="$selectedGateway->metadata['payment_window_hours'] ?? 48" placeholder="48" />
                                <x-admin.input type="text" name="metadata[reference_prefix]" label="Fallback Reference Prefix" :value="$selectedGateway->metadata['reference_prefix'] ?? 'NINO'" placeholder="NINO" />
                            </div>
                            <x-admin.textarea name="metadata[instructions]" label="Fallback Instructions" rows="4" placeholder="These are only used when a legacy offline order has no linked payment method.">{{ $selectedGateway->metadata['instructions'] ?? '' }}</x-admin.textarea>
                            <x-admin.textarea name="metadata[admin_instructions]" label="Fallback Admin Instructions" rows="3" placeholder="Legacy offline verification guidance.">{{ $selectedGateway->metadata['admin_instructions'] ?? '' }}</x-admin.textarea>
                        @endif

                        <div class="rounded-[1.15rem] border border-[rgba(145,133,109,0.18)] bg-[#FCFBF8] px-4 py-4 text-sm text-[#5D6F66]">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#6E7D75]">Workspace note</p>
                            <p class="mt-2">This tab only stores connection data for the processor. The methods customers see in checkout are managed from <strong>Checkout Methods</strong>.</p>
                        </div>

                        <div class="flex justify-end border-t border-[rgba(145,133,109,0.16)] pt-4">
                            <x-nino.button type="submit" variant="primary">Save Provider</x-nino.button>
                        </div>
                    </form>
                @endif
            </section>
        </div>
    @endif
@endsection
