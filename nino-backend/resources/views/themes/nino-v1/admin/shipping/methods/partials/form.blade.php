@php
    $selectedCarrierId = (string) old('shipping_carrier_id', $method->shipping_carrier_id ?? '');
    $districtOverrideValues = old('district_overrides', $districtOverrideMap ?? []);
    $selectedCountryCodes = old('metadata.allowed_countries', $selectedCountryCodes ?? []);
@endphp

<form
    action="{{ isset($method) ? route('admin.shipping.methods.update', $method) : route('admin.shipping.methods.store') }}"
    method="POST"
    class="bg-white border border-slate-200 rounded-md shadow-sm p-6 max-w-5xl space-y-5"
    x-data="shippingMethodPricingForm({
        carrierId: @js($selectedCarrierId),
        apiCarrierDirectory: @js($apiCarrierDirectory),
        districtOverrides: @js($districtOverrideValues),
        countryOptions: @js($countryOptions),
        selectedCountryCodes: @js($selectedCountryCodes),
    })"
>
    @csrf
    @if(isset($method)) @method('PUT') @endif

    <div>
        <label class="block text-sm font-semibold text-slate-900 mb-1">Name <span class="text-red-600">*</span></label>
        <input type="text" name="name" value="{{ old('name', $method->name ?? '') }}" required class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Standard Delivery">
        @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-900 mb-1">Carrier</label>
        <select name="shipping_carrier_id" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" x-model="carrierId" @change="onCarrierChange()">
            <option value="">Select a carrier</option>
            @foreach($carriers as $carrier)
                <option value="{{ $carrier->id }}" @selected($selectedCarrierId === (string) $carrier->id)>
                    {{ $carrier->name }} · {{ strtoupper($carrier->provider) }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">Manual carriers use method-level pricing. API carriers use synced district pricing with optional per-district overrides.</p>
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-900 mb-1">Description</label>
        <textarea name="description" rows="2" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Brief description shown to customers">{{ old('description', $method->description ?? '') }}</textarea>
    </div>

    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-4">
        <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_220px]">
            <div>
                <label class="block text-sm font-semibold text-slate-900 mb-1">Allowed Countries</label>
                <input type="text" class="w-full rounded-lg border-slate-200 bg-white text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Search by country, ISO code, or dial code" x-model.debounce.150ms="countrySearch">
            </div>
            <div class="text-xs text-slate-500 self-end">
                Leave empty to keep the method globally available. Manual order creation will only show methods that support the chosen shipping country.
            </div>
        </div>

        <div class="flex items-center justify-between gap-3">
            <p class="text-xs text-slate-500"><span x-text="filteredCountryCount"></span> countries match the current search.</p>
            <div class="flex items-center gap-2">
                <button type="button" class="px-3 py-2 text-xs font-medium rounded-lg border border-slate-200 text-slate-700 hover:bg-white" @click="selectAllFilteredCountries()">Select Visible</button>
                <button type="button" class="px-3 py-2 text-xs font-medium rounded-lg border border-slate-200 text-slate-700 hover:bg-white" @click="clearCountries()">Clear</button>
            </div>
        </div>

        <div class="max-h-80 overflow-y-auto rounded-xl border border-slate-200 bg-white p-3">
            <div class="grid gap-3 md:grid-cols-2">
                <template x-for="country in filteredCountries" :key="country.code">
                    <label class="flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 transition-colors hover:border-slate-300">
                        <input type="checkbox" :value="country.code" x-model="selectedCountryCodes" class="mt-1 rounded border-slate-200 text-slate-900 focus:ring-slate-900/20">
                        <div class="min-w-0">
                            <p class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                                <span x-text="country.flag"></span>
                                <span x-text="country.name"></span>
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                <span class="font-mono" x-text="country.code"></span>
                                <span>&middot;</span>
                                <span class="font-mono" x-text="country.dial_code"></span>
                            </p>
                        </div>
                    </label>
                </template>
            </div>
        </div>

        <div class="flex flex-wrap gap-2" x-show="selectedCountryCodes.length > 0" x-cloak>
            <template x-for="country in selectedCountries" :key="country.code">
                <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                    <span x-text="country.flag"></span>
                    <span x-text="country.code"></span>
                </span>
            </template>
        </div>

        <div class="hidden">
            <template x-for="countryCode in selectedCountryCodes" :key="`allowed-country-${countryCode}`">
                <input type="hidden" name="metadata[allowed_countries][]" :value="countryCode">
            </template>
        </div>
    </div>

    <div x-show="!selectedCarrierApi" x-cloak class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-slate-900 mb-1">Base Cost (MAD) <span class="text-red-600">*</span></label>
                <input type="number" step="0.01" name="base_cost" value="{{ old('base_cost', $method->base_cost ?? '0.00') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" x-bind:disabled="selectedCarrierApi">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-900 mb-1">Free Shipping Above (MAD)</label>
                <input type="number" step="0.01" name="free_shipping_threshold" value="{{ old('free_shipping_threshold', $method->free_shipping_threshold ?? '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Leave blank for none" x-bind:disabled="selectedCarrierApi">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-slate-900 mb-1">Estimated Delivery</label>
                <input type="text" name="estimated_days" value="{{ old('estimated_days', $method->estimated_days ?? '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="2-4 business days" x-bind:disabled="selectedCarrierApi">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-900 mb-1">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $method->sort_order ?? 0) }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
            </div>
        </div>
    </div>

    <div x-show="selectedCarrierApi" x-cloak class="space-y-4">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_220px]">
                <div>
                    <label class="block text-sm font-semibold text-slate-900 mb-1">Search Districts</label>
                    <input type="text" class="w-full rounded-lg border-slate-200 bg-white text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Search by district, city, or code" x-model.debounce.150ms="search" @input="page = 1">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-900 mb-1">Rows</label>
                        <select class="w-full rounded-lg border-slate-200 bg-white text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" x-model.number="pageSize" @change="page = 1">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-900 mb-1">Last Synced</label>
                        <div class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900" x-text="selectedCarrier?.synced_at || 'Not synced yet'"></div>
                    </div>
                </div>
            </div>

            <p class="mt-4 text-xs text-slate-500">For API carriers, leave Forced Price empty to use the provider price. Add a forced price to round or replace what customers see.</p>

            <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">API Pricing Directory</p>
                        <p class="text-xs text-slate-500"><span x-text="selectedCarrier?.name || 'API carrier'"></span> · <span x-text="filteredDistrictCount"></span> districts</p>
                    </div>
                    <p class="text-xs text-slate-500">Page <span x-text="safePage"></span> of <span x-text="pageCount"></span></p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full table-fixed text-sm text-slate-900">
                        <colgroup>
                            <col class="w-[26%]">
                            <col class="w-[18%]">
                            <col class="w-[10%]">
                            <col class="w-[16%]">
                            <col class="w-[16%]">
                            <col class="w-[14%]">
                        </colgroup>
                        <thead class="bg-slate-50 text-xs uppercase tracking-[0.14em] text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-left">District</th>
                                <th class="px-4 py-3 text-left">City</th>
                                <th class="px-4 py-3 text-left">API Price</th>
                                <th class="px-4 py-3 text-left">Forced Price</th>
                                <th class="px-4 py-3 text-left">Effective Price</th>
                                <th class="px-4 py-3 text-left">SLA / Updated</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <template x-if="paginatedDistricts.length === 0">
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No synced districts match the current search.</td>
                                </tr>
                            </template>
                            <template x-for="district in paginatedDistricts" :key="district.id">
                                <tr class="align-top">
                                    <td class="px-4 py-4">
                                        <p class="font-semibold text-slate-900" x-text="district.district"></p>
                                        <p class="mt-1 font-mono text-xs text-slate-500">Code <span x-text="district.id"></span></p>
                                    </td>
                                    <td class="px-4 py-4 text-slate-700" x-text="district.city || 'Unknown city'"></td>
                                    <td class="px-4 py-4 font-medium text-slate-900" x-text="formatMoney(district.api_price)"></td>
                                    <td class="px-4 py-4">
                                        <input type="number" step="0.01" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Use API price" x-model="districtOverrides[district.id]">
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                            <p class="font-semibold text-slate-900" x-text="formatMoney(effectivePrice(district))"></p>
                                            <p class="mt-1 text-xs text-slate-500" x-text="hasForcedPrice(district.id) ? 'Forced price active' : 'API price in use'"></p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <p class="font-medium text-slate-900" x-text="district.estimated_delivery"></p>
                                        <p class="mt-1 text-xs text-slate-500">Updated <span x-text="district.updated_at"></span></p>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between gap-3 border-t border-slate-200 px-4 py-3">
                    <p class="text-xs text-slate-500">Showing <span x-text="visibleRangeLabel"></span></p>
                    <div class="flex items-center gap-2">
                        <button type="button" class="px-3 py-2 text-xs font-medium rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 disabled:opacity-50" @click="previousPage()" x-bind:disabled="safePage <= 1">Previous</button>
                        <button type="button" class="px-3 py-2 text-xs font-medium rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 disabled:opacity-50" @click="nextPage()" x-bind:disabled="safePage >= pageCount">Next</button>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-slate-900 mb-1">Sort Order</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $method->sort_order ?? 0) }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
        </div>

        <div class="hidden">
            <template x-for="entry in overrideEntries" :key="`district-override-${entry.id}`">
                <input type="hidden" :name="`district_overrides[${entry.id}]`" :value="entry.value">
            </template>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <input type="checkbox" name="is_enabled" value="1" id="is_enabled" {{ old('is_enabled', $method->is_enabled ?? true) ? 'checked' : '' }} class="rounded border-slate-200 text-slate-900 focus:ring-slate-900/20">
        <label for="is_enabled" class="text-sm font-medium text-slate-900">Enabled</label>
    </div>

    @if(isset($method))
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-xs text-slate-600">
            <div class="flex justify-between gap-3">
                <span>Slug</span>
                <span class="font-mono text-slate-900">{{ $method->slug }}</span>
            </div>
            <div class="mt-2 flex justify-between gap-3">
                <span>Provider</span>
                <span class="font-mono text-slate-900">{{ strtoupper($method->shippingCarrier?->provider ?? 'manual') }}</span>
            </div>
        </div>
    @endif

    <div class="flex justify-end pt-2">
        <button type="submit" class="px-5 py-2 text-sm font-medium bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition-colors shadow-sm">
            {{ isset($method) ? 'Update Method' : 'Create Method' }}
        </button>
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
