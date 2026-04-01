@if($errors->any())
    <x-nino.inline-alert tone="danger" title="Please fix the highlighted fields" class="mb-6">
        {{ collect($errors->all())->join(' ') }}
    </x-nino.inline-alert>
@endif

<form
    action="{{ isset($carrier) ? route('admin.shipping.carriers.update', $carrier) : route('admin.shipping.carriers.store') }}"
    method="POST"
    class="form-layout"
    x-data="{ provider: @js(old('provider', $carrier->provider ?? 'manual')) }"
>
    @csrf
    @if(isset($carrier))
        @method('PUT')
    @endif

    <div class="form-main">
        <x-nino.entity-form-section title="Carrier Identity" subtitle="Register partner carriers as first-class operational records instead of free-text labels.">
            <div class="entity-section-grid">
                <div>
                    <label class="filter-label" for="carrier-name">Name</label>
                    <input id="carrier-name" type="text" name="name" value="{{ old('name', $carrier->name ?? '') }}" required class="input-field" placeholder="Sendit">
                </div>
                <div>
                    <label class="filter-label" for="carrier-code">Code</label>
                    <input id="carrier-code" type="text" name="code" value="{{ old('code', $carrier->code ?? '') }}" class="input-field" placeholder="sendit">
                </div>
                <div>
                    <label class="filter-label" for="carrier-provider">Provider</label>
                    <select id="carrier-provider" name="provider" class="input-field" x-model="provider">
                        <option value="manual" @selected(old('provider', $carrier->provider ?? 'manual') === 'manual')>Manual</option>
                        <option value="sendit" @selected(old('provider', $carrier->provider ?? 'manual') === 'sendit')>Sendit API</option>
                    </select>
                </div>
                <div>
                    <label class="filter-label" for="tracking-url-template">Tracking URL Template</label>
                    <input id="tracking-url-template" type="text" name="tracking_url_template" value="{{ old('tracking_url_template', $carrier->tracking_url_template ?? '') }}" class="input-field" placeholder="https://carrier.example/track/{tracking_number}">
                </div>
            </div>
        </x-nino.entity-form-section>

        <x-nino.entity-form-section
            title="Sendit Configuration"
            subtitle="These credentials and routing defaults are only required when the provider is Sendit."
            x-show="provider === 'sendit'"
            x-cloak
        >
            <div class="entity-section-grid">
                <div>
                    <label class="filter-label" for="sendit-public-key">Public Key</label>
                    <input id="sendit-public-key" type="text" name="credentials[public_key]" value="{{ old('credentials.public_key', $carrier->credentials['public_key'] ?? '') }}" class="input-field" placeholder="Public key" x-bind:disabled="provider !== 'sendit'">
                </div>
                <div>
                    <label class="filter-label" for="sendit-secret-key">Secret Key</label>
                    <input id="sendit-secret-key" type="password" name="credentials[secret_key]" value="{{ old('credentials.secret_key', $carrier->credentials['secret_key'] ?? '') }}" class="input-field" placeholder="Secret key" x-bind:disabled="provider !== 'sendit'">
                </div>
                <div>
                    <label class="filter-label" for="pickup-district-id">Pickup District</label>
                    @php
                        $pickupDistrictOptions = $pickupDistricts->map(fn ($district) => [
                            'id' => (string) $district->external_id,
                            'label' => trim($district->district_name.' · '.$district->city),
                            'meta' => trim(collect([$district->city, $district->arabic_name])->filter()->join(' · ')),
                            'search' => mb_strtolower(trim(collect([
                                $district->district_name,
                                $district->city,
                                $district->arabic_name,
                            ])->filter()->join(' '))),
                        ])->values();
                    @endphp
                    <div
                        class="relative"
                        x-data="{
                            open: false,
                            query: '',
                            selectedId: @js((string) old('settings.pickup_district_id', data_get($carrier->settings ?? [], 'pickup_district_id', ''))),
                            activeIndex: 0,
                            options: @js($pickupDistrictOptions),
                            init() {
                                if (this.selectedOption) {
                                    this.query = this.selectedOption.label;
                                }
                            },
                            get selectedOption() {
                                return this.options.find(option => option.id === String(this.selectedId)) ?? null;
                            },
                            get filteredOptions() {
                                const term = this.query.toLowerCase().trim();
                                if (term === '') {
                                    return this.options.slice(0, 80);
                                }

                                return this.options
                                    .filter(option => option.search.includes(term))
                                    .slice(0, 80);
                            },
                            syncActiveIndex() {
                                const index = this.filteredOptions.findIndex(option => option.id === String(this.selectedId));
                                this.activeIndex = index >= 0 ? index : 0;
                            },
                            move(step) {
                                if (! this.open) {
                                    this.open = true;
                                    this.syncActiveIndex();
                                    return;
                                }

                                if (this.filteredOptions.length === 0) {
                                    this.activeIndex = 0;
                                    return;
                                }

                                this.activeIndex = (this.activeIndex + step + this.filteredOptions.length) % this.filteredOptions.length;
                            },
                            chooseActive() {
                                const option = this.filteredOptions[this.activeIndex] ?? this.filteredOptions[0] ?? null;
                                if (option) {
                                    this.choose(option);
                                }
                            },
                            choose(option) {
                                this.selectedId = option.id;
                                this.query = option.label;
                                this.activeIndex = this.filteredOptions.findIndex(item => item.id === option.id);
                                this.open = false;
                            },
                            clear() {
                                this.selectedId = '';
                                this.query = '';
                                this.activeIndex = 0;
                                this.open = false;
                            },
                        }"
                        x-on:click.outside="open = false"
                    >
                        <input type="hidden" name="settings[pickup_district_id]" x-model="selectedId" x-bind:disabled="provider !== 'sendit'">

                        <div
                            class="relative rounded-[1.35rem] border border-[rgba(120,112,95,0.16)] bg-[#FCFBF8] px-3 py-3 shadow-[0_10px_24px_-20px_rgba(23,48,42,0.35)] transition-all"
                            x-bind:class="{
                                'ring-2 ring-[#245848]/15 border-[#245848]/35 shadow-[0_18px_34px_-24px_rgba(23,48,42,0.45)]': provider === 'sendit' && open,
                                'opacity-60': provider !== 'sendit' || options.length === 0,
                            }"
                        >
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-[#8C7B65]">Searchable district picker</p>
                                <div class="flex items-center gap-2 text-[11px] font-mono text-[#8C7B65]">
                                    <span x-show="selectedOption" x-cloak>Code <span x-text="selectedOption?.id"></span></span>
                                    <span x-show="! selectedOption && options.length > 0" x-cloak><span x-text="options.length"></span> loaded</span>
                                </div>
                            </div>

                            <div class="relative">
                                <input
                                    id="pickup-district-id"
                                    type="text"
                                    x-model="query"
                                    x-bind:disabled="provider !== 'sendit' || options.length === 0"
                                    x-on:focus="open = true; syncActiveIndex()"
                                    x-on:input="open = true; if (! selectedOption || query !== selectedOption.label) { selectedId = ''; } syncActiveIndex()"
                                    x-on:keydown.arrow-down.prevent="move(1)"
                                    x-on:keydown.arrow-up.prevent="move(-1)"
                                    x-on:keydown.enter.prevent="chooseActive()"
                                    x-on:keydown.escape.prevent="open = false"
                                    class="input-field border-0 bg-transparent px-0 py-0 pr-24 shadow-none focus:ring-0"
                                    placeholder="Search by district, city, or Arabic name..."
                                    autocomplete="off"
                                >

                                <div class="pointer-events-none absolute inset-y-0 right-16 flex items-center text-[#8C7B65]">
                                    <span class="material-symbols-outlined text-[1rem]">search</span>
                                </div>

                                <button
                                    type="button"
                                    x-show="selectedId"
                                    x-cloak
                                    x-on:click="clear()"
                                    class="absolute inset-y-0 right-0 inline-flex items-center text-xs font-bold uppercase tracking-[0.18em] text-[#8C7B65] transition-colors hover:text-[#17302A]"
                                >
                                    Clear
                                </button>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2" x-show="selectedOption" x-cloak>
                                <span class="inline-flex items-center gap-2 rounded-full bg-[#EAF3EE] px-3 py-1 text-xs font-semibold text-[#245848]">
                                    <span class="material-symbols-outlined text-[0.95rem]">location_on</span>
                                    <span x-text="selectedOption?.label"></span>
                                </span>
                            </div>
                        </div>

                        <div
                            x-show="provider === 'sendit' && open"
                            x-cloak
                            class="absolute z-30 mt-2 w-full overflow-hidden rounded-[1.1rem] border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] shadow-[0_18px_38px_-22px_rgba(17,30,26,0.22)]"
                        >
                            <template x-if="options.length === 0">
                                <div class="px-4 py-3 text-sm text-[#617169]">
                                    Sync Sendit districts first. No districts are available yet.
                                </div>
                            </template>

                            <template x-if="options.length > 0">
                                <div>
                                    <div class="flex items-center justify-between gap-3 border-b border-[rgba(145,133,109,0.12)] px-4 py-2">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-[#8C7B65]">
                                            <span x-text="filteredOptions.length"></span> matching districts
                                        </p>
                                        <p class="text-[11px] text-[#617169]">Use ↑ ↓ Enter</p>
                                    </div>

                                    <div class="max-h-72 overflow-y-auto">
                                        <template x-for="(option, index) in filteredOptions" :key="option.id">
                                            <button
                                                type="button"
                                                x-on:click="choose(option)"
                                                x-on:mouseenter="activeIndex = index"
                                                class="flex w-full items-start justify-between gap-3 border-b border-[rgba(145,133,109,0.08)] px-4 py-3 text-left transition-colors last:border-b-0"
                                                x-bind:class="{
                                                    'bg-[#F7F2E8]': activeIndex === index,
                                                    'bg-[#EFF4F1]': selectedId === option.id && activeIndex !== index,
                                                }"
                                            >
                                                <span class="min-w-0">
                                                    <span class="flex items-center gap-2 text-sm font-semibold text-[#17302A]">
                                                        <span x-text="option.label"></span>
                                                        <span
                                                            x-show="selectedId === option.id"
                                                            x-cloak
                                                            class="rounded-full bg-[#245848] px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.16em] text-white"
                                                        >
                                                            Selected
                                                        </span>
                                                    </span>
                                                    <span class="mt-1 block text-xs text-[#617169]" x-text="option.meta || 'Sendit district'"></span>
                                                </span>
                                                <span class="font-mono text-[11px] text-[#8C7B65]" x-text="option.id"></span>
                                            </button>
                                        </template>

                                        <template x-if="filteredOptions.length === 0">
                                            <div class="px-4 py-3 text-sm text-[#617169]">
                                                No districts match your search.
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-[#617169]">Type to search by district name, city, or Arabic label. Press Enter to confirm the highlighted district.</p>
                </div>
                <div>
                    <label class="filter-label" for="default-label-format">Default Label Format</label>
                    <select id="default-label-format" name="settings[default_label_format]" class="input-field" x-bind:disabled="provider !== 'sendit'">
                        <option value="0" @selected((string) old('settings.default_label_format', data_get($carrier->settings ?? [], 'default_label_format', 0)) === '0')>A4</option>
                        <option value="1" @selected((string) old('settings.default_label_format', data_get($carrier->settings ?? [], 'default_label_format', 0)) === '1')>Thermal</option>
                    </select>
                </div>
                <div>
                    <label class="filter-label" for="sendit-webhook-api-key">Webhook API Key</label>
                    <input id="sendit-webhook-api-key" type="text" name="settings[webhook_api_key]" value="{{ old('settings.webhook_api_key', data_get($carrier->settings ?? [], 'webhook_api_key', '')) }}" class="input-field font-mono text-xs" placeholder="Optional Sendit webhook API key" x-bind:disabled="provider !== 'sendit'">
                    <p class="mt-2 text-xs text-[#617169]">Optional second verification factor if Sendit sends a dedicated API key with webhook callbacks.</p>
                </div>
                <div>
                    <label class="filter-label" for="sendit-webhook-secret">Webhook Secret</label>
                    <input id="sendit-webhook-secret" type="text" name="settings[webhook_secret]" value="{{ old('settings.webhook_secret', data_get($carrier->settings ?? [], 'webhook_secret', '')) }}" class="input-field font-mono text-xs" placeholder="Auto-generated secret" x-bind:disabled="provider !== 'sendit'">
                    <p class="mt-2 text-xs text-[#617169]">Used to verify webhook callbacks from Sendit. Leave blank on create to auto-generate one.</p>
                </div>
                @if(isset($carrier) && ($carrier->provider ?? old('provider')) === 'sendit' && filled(old('settings.webhook_secret', data_get($carrier->settings ?? [], 'webhook_secret'))))
                    <div class="md:col-span-2">
                        <label class="filter-label" for="sendit-webhook-url">Webhook Callback URL</label>
                        <input
                            id="sendit-webhook-url"
                            type="text"
                            readonly
                            value="{{ route('shipping.sendit.webhook', ['carrier' => $carrier->code, 'secret' => old('settings.webhook_secret', data_get($carrier->settings ?? [], 'webhook_secret'))]) }}"
                            class="input-field font-mono text-xs"
                        >
                        <p class="mt-2 text-xs text-[#617169]">Paste this exact URL into the Sendit dashboard webhook field if webhook callbacks are enabled for your account.</p>
                    </div>
                @endif
                <div class="md:col-span-2">
                    <label class="filter-label" for="carrier-notes">Notes</label>
                    <textarea id="carrier-notes" name="metadata[notes]" rows="4" class="input-field" placeholder="Internal routing or carrier setup notes...">{{ old('metadata.notes', data_get($carrier->metadata ?? [], 'notes')) }}</textarea>
                </div>
            </div>
        </x-nino.entity-form-section>
    </div>

    <div class="form-sidebar">
        <x-nino.entity-form-section title="Operational Controls" subtitle="Enable the carrier, review readiness, and sync Sendit districts when needed.">
            <div class="space-y-4">
                <label for="carrier-enabled" class="toggle-row">
                    <input type="checkbox" id="carrier-enabled" name="is_enabled" value="1" {{ old('is_enabled', $carrier->is_enabled ?? true) ? 'checked' : '' }} class="h-4 w-4 rounded border-[rgba(120,112,95,0.28)] text-[#245848] focus:ring-[#245848]/25">
                    Carrier is enabled for method assignment
                </label>

                @if(isset($carrier))
                    <x-nino.entity-detail-grid>
                        <div>
                            <p class="detail-kicker">Provider</p>
                            <p class="detail-value">{{ strtoupper($carrier->provider) }}</p>
                        </div>
                        <div>
                            <p class="detail-kicker">Methods Linked</p>
                            <p class="detail-value-mono">{{ number_format($carrier->shipping_methods_count ?? 0) }}</p>
                        </div>
                        <div>
                            <p class="detail-kicker">District Records</p>
                            <p class="detail-value-mono">{{ number_format($carrier->districts_count ?? 0) }}</p>
                        </div>
                        <div>
                            <p class="detail-kicker">Ready</p>
                            <p class="detail-value">{{ $carrier->isConfigured() ? 'Configured' : 'Needs setup' }}</p>
                        </div>
                    </x-nino.entity-detail-grid>

                    @if($carrier->isSendit())
                        <div class="space-y-3">
                            <x-nino.status-badge :tone="$carrier->isConfigured() ? 'success' : 'warning'" size="sm">
                                {{ $carrier->isConfigured() ? 'Sendit configured' : 'Sendit incomplete' }}
                            </x-nino.status-badge>

                            <form action="{{ route('admin.shipping.carriers.sync-districts', $carrier) }}" method="POST">
                                @csrf
                                <x-nino.button type="submit" variant="secondary" icon="sync">
                                    Sync Sendit Districts
                                </x-nino.button>
                            </form>
                        </div>
                    @endif
                @endif

                <div class="form-note">
                    Manual carriers cover Amana, Aramex, and FedEx. Switch to the Sendit provider only when the API credentials and pickup district are ready.
                </div>
            </div>

            <x-slot:footer>
                <div class="form-actions">
                    <x-nino.button href="{{ route('admin.shipping.carriers.index') }}" variant="outline">Cancel</x-nino.button>
                    <x-nino.button type="submit" variant="primary">{{ isset($carrier) ? 'Update Carrier' : 'Create Carrier' }}</x-nino.button>
                </div>
            </x-slot:footer>
        </x-nino.entity-form-section>
    </div>
</form>
