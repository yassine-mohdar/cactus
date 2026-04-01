@php
    $selectedCarrierId = (string) old('shipping_carrier_id', $method->shipping_carrier_id ?? '');
    $districtOverrideValues = old('district_overrides', $districtOverrideMap ?? []);
    $selectedCountryCodes = old('metadata.allowed_countries', $selectedCountryCodes ?? []);
@endphp

@if($errors->any())
    <x-nino.inline-alert tone="danger" title="Please fix the highlighted fields" class="mb-6">
        {{ collect($errors->all())->join(' ') }}
    </x-nino.inline-alert>
@endif

<form
    action="{{ isset($method) ? route('admin.shipping.methods.update', $method) : route('admin.shipping.methods.store') }}"
    method="POST"
    class="form-layout"
    x-data="shippingMethodPricingForm({
        carrierId: @js($selectedCarrierId),
        apiCarrierDirectory: @js($apiCarrierDirectory),
        districtOverrides: @js($districtOverrideValues),
        countryOptions: @js($countryOptions),
        selectedCountryCodes: @js($selectedCountryCodes),
    })"
>
    @csrf
    @if(isset($method))
        @method('PUT')
    @endif

    <div class="form-main">
        <x-nino.entity-form-section title="Service Profile" subtitle="Name the shipping method, map it to a carrier, and describe the customer-facing promise.">
            <div class="entity-section-grid">
                <div>
                    <label class="filter-label" for="shipping-method-name">Name</label>
                    <input id="shipping-method-name" type="text" name="name" value="{{ old('name', $method->name ?? '') }}" required class="input-field" placeholder="Standard Delivery">
                </div>
                <div>
                    <label class="filter-label" for="shipping-method-carrier">Carrier</label>
                    <select id="shipping-method-carrier" name="shipping_carrier_id" class="input-field" x-model="carrierId" @change="onCarrierChange()">
                        <option value="">Select a carrier</option>
                        @foreach($carriers as $carrier)
                            <option value="{{ $carrier->id }}" @selected($selectedCarrierId === (string) $carrier->id)>
                                {{ $carrier->name }} · {{ strtoupper($carrier->provider) }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-[#617169]">Carriers are managed under Shipping &gt; Carriers. API carriers use synced district pricing and SLA instead of manual base-cost fields.</p>
                </div>
                <div class="md:col-span-2">
                    <label class="filter-label" for="shipping-method-description">Description</label>
                    <textarea id="shipping-method-description" name="description" rows="3" class="input-field" placeholder="Brief description shown to customers">{{ old('description', $method->description ?? '') }}</textarea>
                </div>
            </div>
        </x-nino.entity-form-section>

        <x-nino.entity-form-section title="Country Coverage" subtitle="Scope this method to the countries where the carrier actually operates. Leave empty to keep the method globally available.">
            <div class="space-y-5">
                <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_220px]">
                    <div>
                        <label class="filter-label" for="shipping-method-country-search">Search Countries</label>
                        <input
                            id="shipping-method-country-search"
                            type="text"
                            class="input-field"
                            placeholder="Search by country, ISO code, or dial code"
                            x-model.debounce.150ms="countrySearch"
                        >
                    </div>
                    <div class="form-note">
                        When the admin selects a shipping country on manual order creation, only compatible methods remain visible.
                    </div>
                </div>

                <div class="rounded-[28px] border border-[rgba(36,88,72,0.12)] bg-white shadow-[0_20px_45px_rgba(28,36,33,0.08)] overflow-hidden">
                    <div class="flex items-center justify-between gap-4 border-b border-[rgba(36,88,72,0.1)] px-6 py-4">
                        <div>
                            <h3 class="text-sm font-semibold tracking-[0.24em] text-[#245848] uppercase">Allowed Countries</h3>
                            <p class="mt-1 text-sm text-[#617169]"><span x-text="filteredCountryCount"></span> countries match the current search.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-nino.button type="button" variant="outline" size="sm" @click="selectAllFilteredCountries()">Select Visible</x-nino.button>
                            <x-nino.button type="button" variant="outline" size="sm" @click="clearCountries()">Clear</x-nino.button>
                        </div>
                    </div>

                    <div class="max-h-[24rem] overflow-y-auto px-6 py-4">
                        <div class="grid gap-3 md:grid-cols-2">
                            <template x-for="country in filteredCountries" :key="country.code">
                                <label class="flex items-start gap-3 rounded-[1.1rem] border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] px-4 py-3 transition-colors hover:border-[rgba(36,88,72,0.26)]">
                                    <input type="checkbox" :value="country.code" x-model="selectedCountryCodes" class="mt-1 h-4 w-4 rounded border-[rgba(120,112,95,0.28)] text-[#245848] focus:ring-[#245848]/25">
                                    <div class="min-w-0">
                                        <p class="flex items-center gap-2 text-sm font-semibold text-[#17302A]">
                                            <span x-text="country.flag"></span>
                                            <span x-text="country.name"></span>
                                        </p>
                                        <p class="mt-1 text-xs text-[#617169]">
                                            <span class="font-mono" x-text="country.code"></span>
                                            <span>&middot;</span>
                                            <span class="font-mono" x-text="country.dial_code"></span>
                                        </p>
                                    </div>
                                </label>
                            </template>
                        </div>
                    </div>

                    <div class="border-t border-[rgba(36,88,72,0.1)] px-6 py-4">
                        <div class="flex flex-wrap gap-2" x-show="selectedCountryCodes.length > 0" x-cloak>
                            <template x-for="country in selectedCountries" :key="country.code">
                                <span class="inline-flex items-center gap-2 rounded-full bg-[#EAF3EE] px-3 py-1 text-xs font-semibold text-[#245848]">
                                    <span x-text="country.flag"></span>
                                    <span x-text="country.code"></span>
                                </span>
                            </template>
                        </div>
                        <p class="text-xs text-[#617169]" x-show="selectedCountryCodes.length === 0">No countries selected. This method will remain available for all shipping countries.</p>
                    </div>
                </div>

                <div class="hidden">
                    <template x-for="countryCode in selectedCountryCodes" :key="`allowed-country-${countryCode}`">
                        <input type="hidden" name="metadata[allowed_countries][]" :value="countryCode">
                    </template>
                </div>
            </div>
        </x-nino.entity-form-section>

        <x-nino.entity-form-section x-show="!selectedCarrierApi" x-cloak title="Pricing and SLA" subtitle="Capture base cost, free-shipping threshold, and delivery expectations for manual carriers.">
            <div class="entity-section-grid">
                <div>
                    <label class="filter-label" for="shipping-method-base-cost">Base Cost (MAD)</label>
                    <input id="shipping-method-base-cost" type="number" step="0.01" name="base_cost" value="{{ old('base_cost', $method->base_cost ?? '0.00') }}" class="input-field" x-bind:disabled="selectedCarrierApi">
                </div>
                <div>
                    <label class="filter-label" for="shipping-method-free-threshold">Free Shipping Threshold</label>
                    <input id="shipping-method-free-threshold" type="number" step="0.01" name="free_shipping_threshold" value="{{ old('free_shipping_threshold', $method->free_shipping_threshold ?? '') }}" class="input-field" placeholder="Leave blank for none" x-bind:disabled="selectedCarrierApi">
                </div>
                <div>
                    <label class="filter-label" for="shipping-method-estimated-days">Estimated Delivery</label>
                    <input id="shipping-method-estimated-days" type="text" name="estimated_days" value="{{ old('estimated_days', $method->estimated_days ?? '') }}" class="input-field" placeholder="2-4 business days" x-bind:disabled="selectedCarrierApi">
                </div>
            </div>
        </x-nino.entity-form-section>

        <x-nino.entity-form-section x-show="selectedCarrierApi" x-cloak title="API Pricing and SLA" subtitle="District-level rates and delivery windows are read from the carrier sync. Forced prices override the API price shown to customers.">
            <div class="space-y-5">
                <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_220px]">
                    <div>
                        <label class="filter-label" for="shipping-method-district-search">Search Districts</label>
                        <input
                            id="shipping-method-district-search"
                            type="text"
                            class="input-field"
                            placeholder="Search by district, city, or external code"
                            x-model.debounce.150ms="search"
                            @input="page = 1"
                        >
                    </div>
                    <div class="grid gap-4 grid-cols-2">
                        <div>
                            <label class="filter-label" for="shipping-method-page-size">Rows</label>
                            <select id="shipping-method-page-size" class="input-field" x-model.number="pageSize" @change="page = 1">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                        <div>
                            <p class="filter-label">Last Synced</p>
                            <div class="input-field flex items-center text-sm text-[#1E2B27]" x-text="selectedCarrier?.synced_at || 'Not synced yet'"></div>
                        </div>
                    </div>
                </div>

                <div class="form-note">
                    API-managed carriers ignore the manual pricing inputs. Leave “Forced Price” empty to keep the API price, or enter a rounded commercial price such as `25.00` instead of the provider’s `19.00`.
                </div>

                <div class="rounded-[28px] border border-[rgba(36,88,72,0.12)] bg-white shadow-[0_20px_45px_rgba(28,36,33,0.08)] overflow-hidden">
                    <div class="flex items-center justify-between gap-4 border-b border-[rgba(36,88,72,0.1)] px-6 py-4">
                        <div>
                            <h3 class="text-sm font-semibold tracking-[0.24em] text-[#245848] uppercase">Carrier District Pricing</h3>
                            <p class="mt-1 text-sm text-[#617169]">
                                <span x-text="selectedCarrier?.name || 'API carrier'"></span>
                                <span>&middot;</span>
                                <span x-text="filteredDistrictCount"></span>
                                <span>districts</span>
                            </p>
                        </div>
                        <div class="text-right text-sm text-[#617169]">
                            <p>Page <span class="font-medium text-[#1E2B27]" x-text="safePage"></span> of <span class="font-medium text-[#1E2B27]" x-text="pageCount"></span></p>
                            <p class="mt-1">Showing live API price, SLA, and optional overrides.</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full table-fixed text-sm text-[#1E2B27]">
                            <colgroup>
                                <col class="w-[26%]">
                                <col class="w-[18%]">
                                <col class="w-[10%]">
                                <col class="w-[16%]">
                                <col class="w-[16%]">
                                <col class="w-[14%]">
                            </colgroup>
                            <thead class="bg-[#F5F2EA] text-[11px] uppercase tracking-[0.2em] text-[#617169]">
                                <tr>
                                    <th class="px-4 py-3 text-left">District</th>
                                    <th class="px-4 py-3 text-left">City</th>
                                    <th class="px-4 py-3 text-left">API Price</th>
                                    <th class="px-4 py-3 text-left">Forced Price</th>
                                    <th class="px-4 py-3 text-left">Effective Price</th>
                                    <th class="px-4 py-3 text-left">SLA / Updated</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[rgba(36,88,72,0.08)]">
                                <template x-if="paginatedDistricts.length === 0">
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-sm text-[#617169]">
                                            No synced districts match the current search. Sync the carrier districts first or adjust the search term.
                                        </td>
                                    </tr>
                                </template>

                                <template x-for="district in paginatedDistricts" :key="district.id">
                                    <tr class="align-top">
                                        <td class="px-4 py-4">
                                            <p class="font-semibold text-[#1E2B27]" x-text="district.district"></p>
                                            <p class="mt-1 font-mono text-xs text-[#617169]">Code <span x-text="district.id"></span></p>
                                        </td>
                                        <td class="px-4 py-4 text-[#43564E]" x-text="district.city || 'Unknown city'"></td>
                                        <td class="px-4 py-4 font-medium text-[#1E2B27]" x-text="formatMoney(district.api_price)"></td>
                                        <td class="px-4 py-4">
                                            <input
                                                type="number"
                                                step="0.01"
                                                class="input-field h-11"
                                                placeholder="Use API price"
                                                x-model="districtOverrides[district.id]"
                                            >
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="rounded-2xl border border-[rgba(36,88,72,0.12)] bg-[#F7FBF8] px-3 py-2">
                                                <p class="font-semibold text-[#245848]" x-text="formatMoney(effectivePrice(district))"></p>
                                                <p class="mt-1 text-xs text-[#617169]" x-text="hasForcedPrice(district.id) ? 'Forced price active' : 'API price in use'"></p>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <p class="font-medium text-[#1E2B27]" x-text="district.estimated_delivery"></p>
                                            <p class="mt-1 text-xs text-[#617169]">Updated <span x-text="district.updated_at"></span></p>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-between gap-4 border-t border-[rgba(36,88,72,0.1)] px-6 py-4">
                        <p class="text-sm text-[#617169]">
                            Showing <span class="font-medium text-[#1E2B27]" x-text="visibleRangeLabel"></span>
                        </p>
                        <div class="flex items-center gap-2">
                            <x-nino.button type="button" variant="outline" size="sm" @click="previousPage()" x-bind:disabled="safePage <= 1">Previous</x-nino.button>
                            <x-nino.button type="button" variant="outline" size="sm" @click="nextPage()" x-bind:disabled="safePage >= pageCount">Next</x-nino.button>
                        </div>
                    </div>
                </div>

                <div class="hidden">
                    <template x-for="entry in overrideEntries" :key="`district-override-${entry.id}`">
                        <input type="hidden" :name="`district_overrides[${entry.id}]`" :value="entry.value">
                    </template>
                </div>
            </div>
        </x-nino.entity-form-section>
    </div>

    <div class="form-sidebar">
        <x-nino.entity-form-section title="Operational Controls" subtitle="Enable the method, set routing order, and review provider behavior before saving.">
            <div class="space-y-4">
                <label for="shipping-method-enabled" class="toggle-row">
                    <input type="checkbox" id="shipping-method-enabled" name="is_enabled" value="1" {{ old('is_enabled', $method->is_enabled ?? true) ? 'checked' : '' }} class="h-4 w-4 rounded border-[rgba(120,112,95,0.28)] text-[#245848] focus:ring-[#245848]/25">
                    Method is enabled for checkout and warehouse routing
                </label>

                <div>
                    <label class="filter-label" for="shipping-method-sort-order">Sort Order</label>
                    <input id="shipping-method-sort-order" type="number" name="sort_order" value="{{ old('sort_order', $method->sort_order ?? 0) }}" class="input-field">
                </div>

                @if(isset($method))
                    <x-nino.entity-detail-grid>
                        <div>
                            <p class="detail-kicker">Current Slug</p>
                            <p class="detail-value-mono">{{ $method->slug }}</p>
                        </div>
                        <div>
                            <p class="detail-kicker">Linked Shipments</p>
                            <p class="detail-value-mono">{{ number_format($method->shipments_count ?? 0) }}</p>
                        </div>
                        <div>
                            <p class="detail-kicker">Carrier Provider</p>
                            <p class="detail-value">{{ strtoupper($method->shippingCarrier?->provider ?? 'manual') }}</p>
                        </div>
                    </x-nino.entity-detail-grid>
                @endif

                <div class="form-note">
                    Manual carriers use the method-level price and SLA. API carriers use synced district data, with optional commercial rounding through the forced-price column.
                </div>
            </div>

            <x-slot:footer>
                <div class="form-actions">
                    <x-nino.button href="{{ route('admin.shipping.methods.index') }}" variant="outline">Cancel</x-nino.button>
                    <x-nino.button type="submit" variant="primary">{{ isset($method) ? 'Update Method' : 'Create Method' }}</x-nino.button>
                </div>
            </x-slot:footer>
        </x-nino.entity-form-section>
    </div>
</form>

<script>
    if (! window.shippingMethodPricingForm) {
        window.shippingMethodPricingForm = function (config) {
            return {
                carrierId: config.carrierId ?? '',
                apiCarrierDirectory: config.apiCarrierDirectory ?? {},
                districtOverrides: config.districtOverrides ?? {},
                countryOptions: config.countryOptions ?? [],
                selectedCountryCodes: config.selectedCountryCodes ?? [],
                search: '',
                countrySearch: '',
                page: 1,
                pageSize: 10,
                onCarrierChange() {
                    this.page = 1;
                    this.search = '';
                },
                get selectedCarrier() {
                    return this.apiCarrierDirectory[String(this.carrierId)] ?? null;
                },
                get selectedCarrierApi() {
                    return this.selectedCarrier !== null;
                },
                get filteredCountries() {
                    const term = this.countrySearch.trim().toLowerCase();

                    if (term === '') {
                        return this.countryOptions;
                    }

                    return this.countryOptions.filter((country) => country.search.includes(term));
                },
                get filteredCountryCount() {
                    return this.filteredCountries.length;
                },
                get selectedCountries() {
                    return this.countryOptions.filter((country) => this.selectedCountryCodes.includes(country.code));
                },
                clearCountries() {
                    this.selectedCountryCodes = [];
                },
                selectAllFilteredCountries() {
                    const combined = [...this.selectedCountryCodes, ...this.filteredCountries.map((country) => country.code)];
                    this.selectedCountryCodes = [...new Set(combined)];
                },
                get filteredDistricts() {
                    const carrier = this.selectedCarrier;

                    if (! carrier) {
                        return [];
                    }

                    const term = this.search.trim().toLowerCase();

                    if (term === '') {
                        return carrier.districts;
                    }

                    return carrier.districts.filter((district) => {
                        return [
                            district.id,
                            district.district,
                            district.city,
                            district.estimated_delivery,
                        ].join(' ').toLowerCase().includes(term);
                    });
                },
                get filteredDistrictCount() {
                    return this.filteredDistricts.length;
                },
                get pageCount() {
                    return Math.max(1, Math.ceil(this.filteredDistricts.length / this.pageSize));
                },
                get safePage() {
                    if (this.page > this.pageCount) {
                        this.page = this.pageCount;
                    }

                    if (this.page < 1) {
                        this.page = 1;
                    }

                    return this.page;
                },
                get paginatedDistricts() {
                    const start = (this.safePage - 1) * this.pageSize;

                    return this.filteredDistricts.slice(start, start + this.pageSize);
                },
                get visibleRangeLabel() {
                    if (this.filteredDistricts.length === 0) {
                        return '0 of 0 districts';
                    }

                    const start = ((this.safePage - 1) * this.pageSize) + 1;
                    const end = Math.min(start + this.paginatedDistricts.length - 1, this.filteredDistricts.length);

                    return `${start}-${end} of ${this.filteredDistricts.length} districts`;
                },
                get overrideEntries() {
                    return Object.entries(this.districtOverrides)
                        .map(([id, value]) => ({ id, value: this.normalizedForcedPrice(value) }))
                        .filter((entry) => entry.value !== null);
                },
                previousPage() {
                    if (this.safePage > 1) {
                        this.page = this.safePage - 1;
                    }
                },
                nextPage() {
                    if (this.safePage < this.pageCount) {
                        this.page = this.safePage + 1;
                    }
                },
                hasForcedPrice(districtId) {
                    return this.normalizedForcedPrice(this.districtOverrides[districtId]) !== null;
                },
                effectivePrice(district) {
                    const forcedPrice = this.normalizedForcedPrice(this.districtOverrides[district.id]);

                    if (forcedPrice !== null) {
                        return forcedPrice;
                    }

                    return Number(district.api_price ?? 0);
                },
                normalizedForcedPrice(value) {
                    if (value === null || value === undefined) {
                        return null;
                    }

                    if (typeof value === 'string' && value.trim() === '') {
                        return null;
                    }

                    const numericValue = Number(value);

                    return Number.isFinite(numericValue) ? numericValue : null;
                },
                formatMoney(value) {
                    const amount = Number(value ?? 0);

                    return `${amount.toFixed(2)} MAD`;
                },
            };
        };
    }
</script>
