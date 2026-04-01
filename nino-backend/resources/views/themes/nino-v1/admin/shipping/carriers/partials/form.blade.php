<form action="{{ isset($carrier) ? route('admin.shipping.carriers.update', $carrier) : route('admin.shipping.carriers.store') }}" method="POST" class="bg-white border border-slate-200 rounded-md shadow-sm p-6 max-w-3xl space-y-5" x-data="{ provider: @js(old('provider', $carrier->provider ?? 'manual')) }">
    @csrf
    @if(isset($carrier)) @method('PUT') @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-semibold text-slate-900 mb-1">Name</label>
            <input type="text" name="name" value="{{ old('name', $carrier->name ?? '') }}" required class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Sendit">
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-900 mb-1">Code</label>
            <input type="text" name="code" value="{{ old('code', $carrier->code ?? '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="sendit">
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-900 mb-1">Provider</label>
            <select name="provider" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" x-model="provider">
                <option value="manual" @selected(old('provider', $carrier->provider ?? 'manual') === 'manual')>Manual</option>
                <option value="sendit" @selected(old('provider', $carrier->provider ?? 'manual') === 'sendit')>Sendit API</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-900 mb-1">Tracking URL Template</label>
            <input type="text" name="tracking_url_template" value="{{ old('tracking_url_template', $carrier->tracking_url_template ?? '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="https://carrier.example/track/{tracking_number}">
        </div>
    </div>

    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4" x-show="provider === 'sendit'" x-cloak>
        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900">Sendit Configuration</h2>
        <p class="mt-1 text-xs text-slate-500">Only required when the selected provider is Sendit.</p>

        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-slate-900 mb-1">Public Key</label>
                <input type="text" name="credentials[public_key]" value="{{ old('credentials.public_key', $carrier->credentials['public_key'] ?? '') }}" class="w-full rounded-lg border-slate-200 bg-white text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" x-bind:disabled="provider !== 'sendit'">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-900 mb-1">Secret Key</label>
                <input type="password" name="credentials[secret_key]" value="{{ old('credentials.secret_key', $carrier->credentials['secret_key'] ?? '') }}" class="w-full rounded-lg border-slate-200 bg-white text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" x-bind:disabled="provider !== 'sendit'">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-900 mb-1">Pickup District</label>
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
                        class="relative rounded-xl border border-slate-200 bg-white px-3 py-3 shadow-sm transition-all"
                        x-bind:class="{
                            'ring-2 ring-slate-900/10 border-slate-400 shadow-md': provider === 'sendit' && open,
                            'opacity-60': provider !== 'sendit' || options.length === 0,
                        }"
                    >
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Searchable district picker</p>
                            <div class="flex items-center gap-2 text-[11px] font-mono text-slate-400">
                                <span x-show="selectedOption" x-cloak>Code <span x-text="selectedOption?.id"></span></span>
                                <span x-show="! selectedOption && options.length > 0" x-cloak><span x-text="options.length"></span> loaded</span>
                            </div>
                        </div>

                        <div class="relative">
                            <input
                                type="text"
                                x-model="query"
                                x-bind:disabled="provider !== 'sendit' || options.length === 0"
                                x-on:focus="open = true; syncActiveIndex()"
                                x-on:input="open = true; if (! selectedOption || query !== selectedOption.label) { selectedId = ''; } syncActiveIndex()"
                                x-on:keydown.arrow-down.prevent="move(1)"
                                x-on:keydown.arrow-up.prevent="move(-1)"
                                x-on:keydown.enter.prevent="chooseActive()"
                                x-on:keydown.escape.prevent="open = false"
                                class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 shadow-none focus:ring-0 pr-24"
                                placeholder="Search by district, city, or Arabic name..."
                                autocomplete="off"
                            >

                            <div class="pointer-events-none absolute inset-y-0 right-16 flex items-center text-slate-400">
                                <span class="material-symbols-outlined text-base">search</span>
                            </div>

                            <button
                                type="button"
                                x-show="selectedId"
                                x-cloak
                                x-on:click="clear()"
                                class="absolute inset-y-0 right-0 inline-flex items-center text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500 transition-colors hover:text-slate-900"
                            >
                                Clear
                            </button>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2" x-show="selectedOption" x-cloak>
                            <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                <span class="material-symbols-outlined text-sm">location_on</span>
                                <span x-text="selectedOption?.label"></span>
                            </span>
                        </div>
                    </div>

                    <div
                        x-show="provider === 'sendit' && open"
                        x-cloak
                        class="absolute z-30 mt-2 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg"
                    >
                        <template x-if="options.length === 0">
                            <div class="px-4 py-3 text-sm text-slate-500">
                                Sync Sendit districts first. No districts are available yet.
                            </div>
                        </template>

                        <template x-if="options.length > 0">
                            <div>
                                <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-2">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">
                                        <span x-text="filteredOptions.length"></span> matching districts
                                    </p>
                                    <p class="text-[11px] text-slate-400">Use ↑ ↓ Enter</p>
                                </div>
                                <div class="max-h-72 overflow-y-auto">
                                    <template x-for="(option, index) in filteredOptions" :key="option.id">
                                        <button
                                            type="button"
                                            x-on:click="choose(option)"
                                            x-on:mouseenter="activeIndex = index"
                                            class="flex w-full items-start justify-between gap-3 border-b border-slate-100 px-4 py-3 text-left transition-colors last:border-b-0"
                                            x-bind:class="{
                                                'bg-slate-50': activeIndex === index,
                                                'bg-slate-100': selectedId === option.id && activeIndex !== index,
                                            }"
                                        >
                                            <span class="min-w-0">
                                                <span class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                                                    <span x-text="option.label"></span>
                                                    <span
                                                        x-show="selectedId === option.id"
                                                        x-cloak
                                                        class="rounded-full bg-slate-900 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.16em] text-white"
                                                    >
                                                        Selected
                                                    </span>
                                                </span>
                                                <span class="mt-1 block text-xs text-slate-500" x-text="option.meta || 'Sendit district'"></span>
                                            </span>
                                            <span class="font-mono text-[11px] text-slate-400" x-text="option.id"></span>
                                        </button>
                                    </template>

                                    <template x-if="filteredOptions.length === 0">
                                        <div class="px-4 py-3 text-sm text-slate-500">
                                            No districts match your search.
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                <p class="mt-1 text-xs text-slate-500">Type to search by district name, city, or Arabic label. Press Enter to confirm the highlighted district.</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-900 mb-1">Default Label Format</label>
                <select name="settings[default_label_format]" class="w-full rounded-lg border-slate-200 bg-white text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" x-bind:disabled="provider !== 'sendit'">
                    <option value="0" @selected((string) old('settings.default_label_format', data_get($carrier->settings ?? [], 'default_label_format', 0)) === '0')>A4</option>
                    <option value="1" @selected((string) old('settings.default_label_format', data_get($carrier->settings ?? [], 'default_label_format', 0)) === '1')>Thermal</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-900 mb-1">Webhook API Key</label>
                <input type="text" name="settings[webhook_api_key]" value="{{ old('settings.webhook_api_key', data_get($carrier->settings ?? [], 'webhook_api_key', '')) }}" class="w-full rounded-lg border-slate-200 bg-white text-xs font-mono text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Optional Sendit webhook API key" x-bind:disabled="provider !== 'sendit'">
                <p class="mt-1 text-xs text-slate-500">Optional second verification factor if Sendit sends a dedicated API key with webhook callbacks.</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-900 mb-1">Webhook Secret</label>
                <input type="text" name="settings[webhook_secret]" value="{{ old('settings.webhook_secret', data_get($carrier->settings ?? [], 'webhook_secret', '')) }}" class="w-full rounded-lg border-slate-200 bg-white text-xs font-mono text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Auto-generated secret" x-bind:disabled="provider !== 'sendit'">
                <p class="mt-1 text-xs text-slate-500">Leave blank on create to auto-generate a webhook secret.</p>
            </div>
            @if(isset($carrier) && ($carrier->provider ?? old('provider')) === 'sendit' && filled(old('settings.webhook_secret', data_get($carrier->settings ?? [], 'webhook_secret'))))
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-900 mb-1">Webhook Callback URL</label>
                    <input
                        type="text"
                        readonly
                        value="{{ route('shipping.sendit.webhook', ['carrier' => $carrier->code, 'secret' => old('settings.webhook_secret', data_get($carrier->settings ?? [], 'webhook_secret'))]) }}"
                        class="w-full rounded-lg border-slate-200 bg-white text-xs font-mono text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20"
                    >
                    <p class="mt-1 text-xs text-slate-500">Paste this URL into the Sendit dashboard webhook field if webhook callbacks are enabled.</p>
                </div>
            @endif
        </div>
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-900 mb-1">Notes</label>
        <textarea name="metadata[notes]" rows="4" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">{{ old('metadata.notes', data_get($carrier->metadata ?? [], 'notes')) }}</textarea>
    </div>

    <div class="flex items-center gap-3">
        <input type="checkbox" name="is_enabled" value="1" id="carrier_enabled" {{ old('is_enabled', $carrier->is_enabled ?? true) ? 'checked' : '' }} class="rounded border-slate-200 text-slate-900 focus:ring-slate-900/20">
        <label for="carrier_enabled" class="text-sm font-medium text-slate-900">Enabled</label>
    </div>

    @if(isset($carrier) && $carrier->isSendit())
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.shipping.carriers.sync-districts', $carrier) }}" class="px-4 py-2 text-xs font-medium bg-white border border-slate-200 text-slate-900 rounded-lg hover:bg-slate-50 transition-colors">Sync Sendit Districts</a>
            <span class="inline-flex items-center px-3 py-2 rounded-lg text-xs font-bold {{ $carrier->isConfigured() ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                {{ $carrier->isConfigured() ? 'Sendit configured' : 'Sendit incomplete' }}
            </span>
        </div>
    @endif

    <div class="flex justify-end pt-2">
        <button type="submit" class="px-5 py-2 text-sm font-medium bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition-colors shadow-sm">
            {{ isset($carrier) ? 'Update Carrier' : 'Create Carrier' }}
        </button>
    </div>
</form>
