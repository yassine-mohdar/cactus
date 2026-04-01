@php
    $countryPickerOptions = collect($countryOptions)->map(fn ($country) => [
        'value' => $country['code'],
        'label' => $country['name'],
        'flag' => $country['flag'],
        'meta' => $country['dial_code'],
        'search' => $country['search'],
    ])->values()->all();

    $shippingDistrictPickerOptions = collect($availableShippingDestinations)->map(fn ($destination) => [
        'value' => $destination['id'],
        'label' => $destination['label'],
        'flag' => null,
        'meta' => $destination['city'] ?: 'Destination district',
        'search' => $destination['search'],
    ])->values()->all();

    $billingDistrictPickerOptions = $shippingDistrictPickerOptions;
    $shippingStatePickerOptions = collect($shippingStateOptions)->values()->all();
    $shippingGlobalCityPickerOptions = collect($shippingGlobalCityOptions)->values()->all();
    $billingStatePickerOptions = collect($billingStateOptions)->values()->all();
    $billingGlobalCityPickerOptions = collect($billingGlobalCityOptions)->values()->all();
@endphp

<div class="max-w-5xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <a href="{{ route('admin.orders.index') }}" class="text-xs font-bold text-slate-800 hover:text-slate-800/80 flex items-center gap-1">
            <span class="material-symbols-outlined text-sm">arrow_back</span> Back to Orders
        </a>
        <h1 class="text-2xl font-bold text-slate-900 mt-2 tracking-tight">Create Manual Order</h1>
    </div>

    <form wire:submit.prevent="save" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            
            {{-- Customer Selection --}}
            <div class="bg-white rounded-md border border-slate-200 shadow-sm p-6">
                <h2 class="text-sm font-bold text-outline uppercase tracking-wider mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">person</span> Customer information
                </h2>
                
                <div class="relative">
                    <label class="block text-xs font-bold text-slate-500 mb-1">Search Customer</label>
                    <input type="text" wire:model.live="search_customer" 
                           class="w-full px-4 py-2 bg-canvas border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-slate-400 focus:border-slate-400 outline-none" 
                           placeholder="Search by name or email...">
                    
                    @if(count($searchResults) > 0 && !$customer_id)
                        <div class="absolute z-10 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
                            @foreach($searchResults as $result)
                                <button type="button" wire:click="selectCustomer({{ $result->id }})" 
                                        class="w-full text-left px-4 py-2 text-sm hover:bg-canvas transition-colors flex justify-between">
                                    <span>{{ $result->name }}</span>
                                    <span class="text-xs text-slate-500">{{ $result->email }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
                @error('customer_id') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
            </div>

            {{-- Products --}}
            <div class="bg-white rounded-md border border-slate-200 shadow-sm p-6">
                <h2 class="text-sm font-bold text-outline uppercase tracking-wider mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">inventory_2</span> Order Items
                </h2>

                <div class="space-y-3">
                    @foreach($items as $index => $item)
                        <div class="flex flex-wrap md:flex-nowrap gap-3 items-end pb-3 border-b border-slate-200 last:border-0 last:pb-0">
                            <div class="flex-1 min-w-[200px]">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Product</label>
                                <select wire:model.live="items.{{ $index }}.product_id" 
                                        class="w-full px-3 py-2 bg-canvas border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-slate-400 outline-none">
                                    <option value="">Select Product</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="w-full md:w-32">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Price</label>
                                <div class="relative">
                                    <input type="number" step="0.01" wire:model="items.{{ $index }}.price" 
                                           class="w-full px-3 py-2 bg-canvas border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-slate-400 outline-none pl-8">
                                    <span class="absolute left-3 top-2.5 text-xs text-slate-500 font-bold">MAD</span>
                                </div>
                            </div>
                            <div class="w-full md:w-24">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Qty</label>
                                <input type="number" min="1" wire:model.live="items.{{ $index }}.quantity" 
                                       class="w-full px-3 py-2 bg-canvas border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-slate-400 outline-none">
                            </div>
                            <div class="w-full md:w-32 text-right">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Total</label>
                                <div class="py-2 text-sm font-bold text-slate-900">
                                    {{ number_format($item['price'] * $item['quantity'], 2) }}
                                </div>
                            </div>
                            <button type="button" wire:click="removeItem({{ $index }})" 
                                    class="p-2 text-outline hover:text-red-600 transition-colors mb-0.5">
                                <span class="material-symbols-outlined text-base">delete</span>
                            </button>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 flex justify-between items-center">
                    <button type="button" wire:click="addItem" 
                            class="text-xs font-bold text-slate-800 hover:text-slate-800/80 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">add</span> Add Another Product
                    </button>
                    <div class="text-sm font-bold text-slate-900">
                        Subtotal: {{ number_format($this->subtotal, 2) }} MAD
                    </div>
                </div>
            </div>

            {{-- Addresses --}}
            <div class="bg-white rounded-md border border-slate-200 shadow-sm p-6">
                <h2 class="text-sm font-bold text-outline uppercase tracking-wider mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">local_shipping</span> Shipping Address
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">First Name</label>
                        <input type="text" wire:model="shipping_address.first_name" class="w-full px-3 py-2 bg-canvas border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-slate-400 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Last Name</label>
                        <input type="text" wire:model="shipping_address.last_name" class="w-full px-3 py-2 bg-canvas border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-slate-400 outline-none">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 mb-1">Phone</label>
                        <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
                            <div class="relative" x-data="ninoAddressCombobox({ selectedValue: @entangle('shipping_phone_country').live, options: @js($countryPickerOptions), placeholder: 'Search country code...' })" x-on:click.outside="open = false">
                                <button type="button" x-on:click="open = !open; if (open) { query = selectedLabel; syncActiveIndex(); }" class="flex h-11 w-full items-center justify-between rounded-lg border border-slate-200 bg-white px-3 shadow-sm transition-all" x-bind:class="{ 'ring-2 ring-slate-900/10 border-slate-400 shadow-md': open }">
                                    <span class="flex min-w-0 items-center gap-2">
                                        <span class="text-base" x-text="selectedOption?.flag || '🌍'"></span>
                                        <span class="truncate font-mono text-sm font-semibold text-slate-900" x-text="selectedOption?.meta || '+212'"></span>
                                    </span>
                                    <span class="material-symbols-outlined text-sm text-slate-400">expand_more</span>
                                </button>
                                <div x-show="open" x-cloak class="absolute z-30 mt-2 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                                    <div class="border-b border-slate-200 p-3">
                                        <input type="text" x-model="query" x-on:input="syncActiveIndex()" x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="chooseActive()" x-on:keydown.escape.prevent="open = false" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Search country or code..." autocomplete="off">
                                    </div>
                                    <div class="max-h-72 overflow-y-auto">
                                        <template x-for="(option, index) in filteredOptions" :key="option.value">
                                            <button type="button" x-on:click="choose(option)" x-on:mouseenter="activeIndex = index" class="flex w-full items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 text-left transition-colors last:border-b-0" x-bind:class="{ 'bg-slate-50': activeIndex === index, 'bg-slate-100': selectedValue === option.value && activeIndex !== index }">
                                                <span class="flex items-center gap-2 text-sm font-semibold text-slate-900"><span x-text="option.flag"></span><span x-text="option.label"></span></span>
                                                <span class="font-mono text-[11px] text-slate-400" x-text="option.meta"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <input type="tel" wire:model.live="shipping_phone_local" class="w-full px-3 py-2 bg-canvas border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-slate-400 outline-none" placeholder="612345678">
                                <p class="mt-2 text-xs text-slate-500">Saved as <span class="font-mono">{{ $shipping_address['phone'] ?: 'international number pending' }}</span></p>
                            </div>
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 mb-1">Country</label>
                        <div class="relative" x-data="ninoAddressCombobox({ selectedValue: @entangle('shipping_address.country').live, options: @js($countryPickerOptions), placeholder: 'Search country...' })" x-on:click.outside="open = false">
                            <div class="relative rounded-xl border border-slate-200 bg-white px-3 py-3 shadow-sm transition-all" x-bind:class="{ 'ring-2 ring-slate-900/10 border-slate-400 shadow-md': open }">
                                <div class="mb-2 flex items-center justify-between gap-3">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Country</p>
                                    <span class="text-[11px] font-mono text-slate-400" x-show="selectedOption" x-text="selectedOption?.meta"></span>
                                </div>
                                <div class="relative">
                                    <input type="text" x-model="query" x-on:focus="open = true; syncActiveIndex()" x-on:input="open = true; if (query !== selectedLabel) { selectedValue = ''; } syncActiveIndex()" x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="chooseActive()" x-on:keydown.escape.prevent="open = false" class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 shadow-none focus:ring-0 pr-24" placeholder="Search country..." autocomplete="off">
                                    <div class="pointer-events-none absolute inset-y-0 right-16 flex items-center text-slate-400"><span class="material-symbols-outlined text-base">search</span></div>
                                    <button type="button" x-show="selectedValue" x-cloak x-on:click="clear()" class="absolute inset-y-0 right-0 inline-flex items-center text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500 transition-colors hover:text-slate-900">Clear</button>
                                </div>
                            </div>
                            <div x-show="open" x-cloak class="absolute z-30 mt-2 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                                <div class="max-h-72 overflow-y-auto">
                                    <template x-for="(option, index) in filteredOptions" :key="option.value">
                                        <button type="button" x-on:click="choose(option)" x-on:mouseenter="activeIndex = index" class="flex w-full items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 text-left transition-colors last:border-b-0" x-bind:class="{ 'bg-slate-50': activeIndex === index, 'bg-slate-100': selectedValue === option.value && activeIndex !== index }">
                                            <span class="flex items-center gap-2 text-sm font-semibold text-slate-900"><span x-text="option.flag"></span><span x-text="option.label"></span></span>
                                            <span class="font-mono text-[11px] text-slate-400" x-text="option.meta"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        @if($shippingIsMorocco)
                            <label class="block text-xs font-bold text-slate-500 mb-1">Destination District</label>
                            <div wire:key="shipping-destination-ma" class="relative" x-data="ninoAddressCombobox({ selectedValue: @entangle('shipping_destination_id').live, options: @js($shippingDistrictPickerOptions), placeholder: 'Search by district or city...' })" x-on:click.outside="open = false">
                                <div class="relative rounded-xl border border-slate-200 bg-white px-3 py-3 shadow-sm transition-all" x-bind:class="{ 'ring-2 ring-slate-900/10 border-slate-400 shadow-md': open }">
                                    <div class="mb-2 flex items-center justify-between gap-3">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Searchable district picker</p>
                                        <div class="flex items-center gap-2 text-[11px] font-mono text-slate-400">
                                            <span x-show="selectedValue" x-cloak>Selected</span>
                                            <span x-show="! selectedValue && options.length > 0" x-cloak><span x-text="options.length"></span> loaded</span>
                                        </div>
                                    </div>
                                    <div class="relative">
                                        <input type="text" x-model="query" x-on:focus="open = true; syncActiveIndex()" x-on:input="open = true; if (query !== selectedLabel) { selectedValue = ''; } syncActiveIndex()" x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="chooseActive()" x-on:keydown.escape.prevent="open = false" class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 shadow-none focus:ring-0 pr-24" placeholder="Search by district or city..." autocomplete="off">
                                            <div class="pointer-events-none absolute inset-y-0 right-16 flex items-center text-slate-400"><span class="material-symbols-outlined text-base">search</span></div>
                                            <button type="button" x-show="selectedValue" x-cloak x-on:click="clear()" class="absolute inset-y-0 right-0 inline-flex items-center text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500 transition-colors hover:text-slate-900">Clear</button>
                                        </div>
                                    </div>
                                    <div x-show="open" x-cloak class="absolute z-30 mt-2 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                                        <div class="max-h-72 overflow-y-auto">
                                            <template x-for="(option, index) in filteredOptions" :key="option.value">
                                                <button type="button" x-on:click="choose(option)" x-on:mouseenter="activeIndex = index" class="flex w-full items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 text-left transition-colors last:border-b-0" x-bind:class="{ 'bg-slate-50': activeIndex === index, 'bg-slate-100': selectedValue === option.value && activeIndex !== index }">
                                                    <span class="min-w-0">
                                                        <span class="block text-sm font-semibold text-slate-900" x-text="option.label"></span>
                                                        <span class="mt-1 block text-xs text-slate-400" x-show="option.meta" x-text="option.meta"></span>
                                                    </span>
                                                    <span class="material-symbols-outlined text-sm text-slate-400">local_shipping</span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <p class="mt-2 text-xs text-slate-500">Morocco orders use the synced Sendit district catalog before shipping-method selection.</p>
                            </div>
                        @else
                            <label class="block text-xs font-bold text-slate-500 mb-1">State / Province</label>
                            <div wire:key="shipping-state-global-{{ $shipping_address['country'] ?? 'none' }}" class="relative" x-data="ninoAddressCombobox({ selectedValue: @entangle('shipping_address.state').live, options: @js($shippingStatePickerOptions), placeholder: 'Search state or province...' })" x-on:click.outside="open = false">
                                <div class="relative rounded-xl border border-slate-200 bg-white px-3 py-3 shadow-sm transition-all" x-bind:class="{ 'ring-2 ring-slate-900/10 border-slate-400 shadow-md': open }">
                                    <div class="mb-2 flex items-center justify-between gap-3">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Searchable state picker</p>
                                        <div class="flex items-center gap-2 text-[11px] font-mono text-slate-400">
                                            <span x-show="selectedValue" x-cloak>Selected</span>
                                            <span x-show="! selectedValue && options.length > 0" x-cloak><span x-text="options.length"></span> loaded</span>
                                        </div>
                                    </div>
                                    <div class="relative">
                                        <input type="text" x-model="query" x-on:focus="open = true; syncActiveIndex()" x-on:input="open = true; if (query !== selectedLabel) { selectedValue = ''; } syncActiveIndex()" x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="chooseActive()" x-on:keydown.escape.prevent="open = false" class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 shadow-none focus:ring-0 pr-24" placeholder="Search state or province..." autocomplete="off">
                                            <div class="pointer-events-none absolute inset-y-0 right-16 flex items-center text-slate-400"><span class="material-symbols-outlined text-base">search</span></div>
                                            <button type="button" x-show="selectedValue" x-cloak x-on:click="clear()" class="absolute inset-y-0 right-0 inline-flex items-center text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500 transition-colors hover:text-slate-900">Clear</button>
                                        </div>
                                    </div>
                                    <div x-show="open" x-cloak class="absolute z-30 mt-2 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                                        <div class="max-h-72 overflow-y-auto">
                                            <template x-for="(option, index) in filteredOptions" :key="option.value">
                                                <button type="button" x-on:click="choose(option)" x-on:mouseenter="activeIndex = index" class="flex w-full items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 text-left transition-colors last:border-b-0" x-bind:class="{ 'bg-slate-50': activeIndex === index, 'bg-slate-100': selectedValue === option.value && activeIndex !== index }">
                                                    <span class="min-w-0">
                                                        <span class="block text-sm font-semibold text-slate-900" x-text="option.label"></span>
                                                        <span class="mt-1 block text-xs text-slate-400" x-show="option.meta" x-text="option.meta"></span>
                                                    </span>
                                                    <span class="material-symbols-outlined text-sm text-slate-400">map</span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <p class="mt-2 text-xs text-slate-500">International addresses use the local country, state, and city directory.</p>
                            </div>
                        @endif
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Postal Code</label>
                        <input type="text" wire:model="shipping_address.postal_code" class="w-full px-3 py-2 bg-canvas border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-slate-400 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">City</label>
                        @if($shippingIsMorocco)
                            <div class="flex h-11 items-center rounded-lg border border-slate-200 bg-canvas px-3 text-sm font-semibold text-slate-900">
                                {{ $shipping_address['city'] ?: 'Select a district to populate the city' }}
                            </div>
                        @else
                            <div wire:key="shipping-city-global-{{ ($shipping_address['country'] ?? 'none').'-'.md5((string) ($shipping_address['state'] ?? '')) }}" class="relative" x-data="ninoAddressCombobox({ selectedValue: @entangle('shipping_address.city').live, options: @js($shippingGlobalCityPickerOptions), placeholder: 'Search city...' })" x-on:click.outside="open = false">
                                <div class="relative rounded-xl border border-slate-200 bg-white px-3 py-3 shadow-sm transition-all" x-bind:class="{ 'ring-2 ring-slate-900/10 border-slate-400 shadow-md': open }">
                                    <div class="mb-2 flex items-center justify-between gap-3">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Searchable city picker</p>
                                        <div class="flex items-center gap-2 text-[11px] font-mono text-slate-400">
                                            <span x-show="selectedValue" x-cloak>Selected</span>
                                            <span x-show="! selectedValue && options.length > 0" x-cloak><span x-text="options.length"></span> loaded</span>
                                        </div>
                                    </div>
                                    <div class="relative">
                                        <input type="text" x-model="query" x-on:focus="open = true; syncActiveIndex()" x-on:input="open = true; if (query !== selectedLabel) { selectedValue = ''; } syncActiveIndex()" x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="chooseActive()" x-on:keydown.escape.prevent="open = false" class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 shadow-none focus:ring-0 pr-24" placeholder="Search city..." autocomplete="off">
                                            <div class="pointer-events-none absolute inset-y-0 right-16 flex items-center text-slate-400"><span class="material-symbols-outlined text-base">search</span></div>
                                            <button type="button" x-show="selectedValue" x-cloak x-on:click="clear()" class="absolute inset-y-0 right-0 inline-flex items-center text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500 transition-colors hover:text-slate-900">Clear</button>
                                        </div>
                                    </div>
                                    <div x-show="open" x-cloak class="absolute z-30 mt-2 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                                        <div class="max-h-72 overflow-y-auto">
                                            <template x-for="(option, index) in filteredOptions" :key="option.value">
                                                <button type="button" x-on:click="choose(option)" x-on:mouseenter="activeIndex = index" class="flex w-full items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 text-left transition-colors last:border-b-0" x-bind:class="{ 'bg-slate-50': activeIndex === index, 'bg-slate-100': selectedValue === option.value && activeIndex !== index }">
                                                    <span class="min-w-0">
                                                        <span class="block text-sm font-semibold text-slate-900" x-text="option.label"></span>
                                                        <span class="mt-1 block text-xs text-slate-400" x-show="option.meta" x-text="option.meta"></span>
                                                    </span>
                                                    <span class="material-symbols-outlined text-sm text-slate-400">location_city</span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 mb-1">Address Line 1</label>
                        <input type="text" wire:model="shipping_address.address_line_1" class="w-full px-3 py-2 bg-canvas border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-slate-400 outline-none">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 mb-1">Address Line 2</label>
                        <input type="text" wire:model="shipping_address.address_line_2" class="w-full px-3 py-2 bg-canvas border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-slate-400 outline-none">
                    </div>
                </div>

                <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Shipping Method</p>
                            <p class="mt-2 text-sm text-slate-600">Once the address is filled in, choose the method available for that destination and basket.</p>
                        </div>
                        @if($this->selectedShippingMethod)
                            <span class="rounded-full bg-white px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] text-slate-700 border border-slate-200">{{ $this->selectedShippingMethod->name }}</span>
                        @endif
                    </div>

                    <div class="mt-4 space-y-3">
                        @forelse($shippingMethods as $method)
                            @php($quote = $shippingMethodQuotes[$method->id] ?? ['cost' => 0, 'eta' => null, 'is_api' => false, 'has_match' => false, 'source' => 'none', 'message' => null])
                            <label class="block cursor-pointer rounded-lg border border-slate-200 bg-white p-4 hover:border-slate-300 transition-colors">
                                <div class="flex items-start gap-3">
                                    <input type="radio" wire:model.live="shipping_method_id" name="shipping_method_id" value="{{ $method->id }}" class="mt-1 rounded border-slate-300 text-slate-900 focus:ring-slate-900/20">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold text-slate-900">{{ $method->name }}</p>
                                                <p class="mt-1 text-xs text-slate-500">
                                                    {{ $method->carrierLabel() }}
                                                    @if($method->shippingCarrier?->provider)
                                                        <span class="uppercase tracking-[0.18em]">· {{ $method->shippingCarrier->provider }}</span>
                                                    @endif
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm font-black text-slate-900">
                                                    @if($quote['is_api'] && ! $quote['has_match'])
                                                        Select district
                                                    @else
                                                        {{ number_format((float) $quote['cost'], 2) }} MAD
                                                    @endif
                                                </p>
                                                <p class="mt-1 text-xs text-slate-500">
                                                    @if($quote['is_api'] && ! $quote['has_match'])
                                                        {{ $quote['message'] }}
                                                    @else
                                                        {{ $quote['eta'] ?: 'ETA not set' }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                        <div class="mt-3 flex flex-wrap gap-2 text-[11px] text-slate-500">
                                            @if($quote['is_api'])
                                                <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-700">{{ $quote['source'] === 'forced' ? 'Forced district price' : 'Live API district price' }}</span>
                                                <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-700">{{ $quote['has_match'] ? (($quote['district'] ?? $quote['city']).' · Updated '.($quote['updated_at'] ?? 'now')) : 'Waiting for destination district' }}</span>
                                            @else
                                                <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-700">Base {{ number_format((float) $method->base_cost, 2) }} MAD</span>
                                                <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-700">{{ $method->free_shipping_threshold ? 'Free above '.number_format((float) $method->free_shipping_threshold, 2).' MAD' : 'No free shipping threshold' }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </label>
                        @empty
                            <div class="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-5 text-sm text-slate-500">
                                No enabled shipping methods are configured yet.
                            </div>
                        @endforelse
                    </div>

                    @error('shipping_method_id')
                        <span class="mt-3 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mt-6 flex items-center gap-2">
                    <input type="checkbox" id="same_as_shipping" wire:model.live="same_as_shipping" 
                           class="rounded text-slate-800 focus:ring-slate-400 border-slate-200">
                    <label for="same_as_shipping" class="text-sm font-bold text-slate-900">Billing address same as shipping</label>
                </div>
            </div>

            @if(! $same_as_shipping)
                <div class="bg-white rounded-md border border-slate-200 shadow-sm p-6">
                    <h2 class="text-sm font-bold text-outline uppercase tracking-wider mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-base">receipt_long</span> Billing Address
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">First Name</label>
                            <input type="text" wire:model="billing_address.first_name" class="w-full px-3 py-2 bg-canvas border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-slate-400 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Last Name</label>
                            <input type="text" wire:model="billing_address.last_name" class="w-full px-3 py-2 bg-canvas border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-slate-400 outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 mb-1">Phone</label>
                            <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
                                <div class="relative" x-data="ninoAddressCombobox({ selectedValue: @entangle('billing_phone_country').live, options: @js($countryPickerOptions), placeholder: 'Search country code...' })" x-on:click.outside="open = false">
                                    <button type="button" x-on:click="open = !open; if (open) { query = selectedLabel; syncActiveIndex(); }" class="flex h-11 w-full items-center justify-between rounded-lg border border-slate-200 bg-white px-3 shadow-sm transition-all" x-bind:class="{ 'ring-2 ring-slate-900/10 border-slate-400 shadow-md': open }">
                                        <span class="flex min-w-0 items-center gap-2">
                                            <span class="text-base" x-text="selectedOption?.flag || '🌍'"></span>
                                            <span class="truncate font-mono text-sm font-semibold text-slate-900" x-text="selectedOption?.meta || '+212'"></span>
                                        </span>
                                        <span class="material-symbols-outlined text-sm text-slate-400">expand_more</span>
                                    </button>
                                    <div x-show="open" x-cloak class="absolute z-30 mt-2 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                                        <div class="border-b border-slate-200 p-3">
                                            <input type="text" x-model="query" x-on:input="syncActiveIndex()" x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="chooseActive()" x-on:keydown.escape.prevent="open = false" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Search country or code..." autocomplete="off">
                                        </div>
                                        <div class="max-h-72 overflow-y-auto">
                                            <template x-for="(option, index) in filteredOptions" :key="option.value">
                                                <button type="button" x-on:click="choose(option)" x-on:mouseenter="activeIndex = index" class="flex w-full items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 text-left transition-colors last:border-b-0" x-bind:class="{ 'bg-slate-50': activeIndex === index, 'bg-slate-100': selectedValue === option.value && activeIndex !== index }">
                                                    <span class="flex items-center gap-2 text-sm font-semibold text-slate-900"><span x-text="option.flag"></span><span x-text="option.label"></span></span>
                                                    <span class="font-mono text-[11px] text-slate-400" x-text="option.meta"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <input type="tel" wire:model.live="billing_phone_local" class="w-full px-3 py-2 bg-canvas border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-slate-400 outline-none" placeholder="612345678">
                                    <p class="mt-2 text-xs text-slate-500">Saved as <span class="font-mono">{{ $billing_address['phone'] ?: 'international number pending' }}</span></p>
                                </div>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 mb-1">Country</label>
                            <div class="relative" x-data="ninoAddressCombobox({ selectedValue: @entangle('billing_address.country').live, options: @js($countryPickerOptions), placeholder: 'Search country...' })" x-on:click.outside="open = false">
                                <div class="relative rounded-xl border border-slate-200 bg-white px-3 py-3 shadow-sm transition-all" x-bind:class="{ 'ring-2 ring-slate-900/10 border-slate-400 shadow-md': open }">
                                    <div class="mb-2 flex items-center justify-between gap-3">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Country</p>
                                        <span class="text-[11px] font-mono text-slate-400" x-show="selectedOption" x-text="selectedOption?.meta"></span>
                                    </div>
                                    <div class="relative">
                                        <input type="text" x-model="query" x-on:focus="open = true; syncActiveIndex()" x-on:input="open = true; if (query !== selectedLabel) { selectedValue = ''; } syncActiveIndex()" x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="chooseActive()" x-on:keydown.escape.prevent="open = false" class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 shadow-none focus:ring-0 pr-24" placeholder="Search country..." autocomplete="off">
                                        <div class="pointer-events-none absolute inset-y-0 right-16 flex items-center text-slate-400"><span class="material-symbols-outlined text-base">search</span></div>
                                        <button type="button" x-show="selectedValue" x-cloak x-on:click="clear()" class="absolute inset-y-0 right-0 inline-flex items-center text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500 transition-colors hover:text-slate-900">Clear</button>
                                    </div>
                                </div>
                                <div x-show="open" x-cloak class="absolute z-30 mt-2 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                                    <div class="max-h-72 overflow-y-auto">
                                        <template x-for="(option, index) in filteredOptions" :key="option.value">
                                            <button type="button" x-on:click="choose(option)" x-on:mouseenter="activeIndex = index" class="flex w-full items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 text-left transition-colors last:border-b-0" x-bind:class="{ 'bg-slate-50': activeIndex === index, 'bg-slate-100': selectedValue === option.value && activeIndex !== index }">
                                                <span class="flex items-center gap-2 text-sm font-semibold text-slate-900"><span x-text="option.flag"></span><span x-text="option.label"></span></span>
                                                <span class="font-mono text-[11px] text-slate-400" x-text="option.meta"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            @if($billingIsMorocco)
                                <label class="block text-xs font-bold text-slate-500 mb-1">Destination District</label>
                                <div wire:key="billing-destination-ma" class="relative" x-data="ninoAddressCombobox({ selectedValue: @entangle('billing_destination_id').live, options: @js($billingDistrictPickerOptions), placeholder: 'Search by district or city...' })" x-on:click.outside="open = false">
                                    <div class="relative rounded-xl border border-slate-200 bg-white px-3 py-3 shadow-sm transition-all" x-bind:class="{ 'ring-2 ring-slate-900/10 border-slate-400 shadow-md': open }">
                                        <div class="mb-2 flex items-center justify-between gap-3">
                                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Searchable district picker</p>
                                            <div class="flex items-center gap-2 text-[11px] font-mono text-slate-400">
                                                <span x-show="selectedValue" x-cloak>Selected</span>
                                                <span x-show="! selectedValue && options.length > 0" x-cloak><span x-text="options.length"></span> loaded</span>
                                            </div>
                                        </div>
                                        <div class="relative">
                                            <input type="text" x-model="query" x-on:focus="open = true; syncActiveIndex()" x-on:input="open = true; if (query !== selectedLabel) { selectedValue = ''; } syncActiveIndex()" x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="chooseActive()" x-on:keydown.escape.prevent="open = false" class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 shadow-none focus:ring-0 pr-24" placeholder="Search by district or city..." autocomplete="off">
                                            <div class="pointer-events-none absolute inset-y-0 right-16 flex items-center text-slate-400"><span class="material-symbols-outlined text-base">search</span></div>
                                            <button type="button" x-show="selectedValue" x-cloak x-on:click="clear()" class="absolute inset-y-0 right-0 inline-flex items-center text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500 transition-colors hover:text-slate-900">Clear</button>
                                        </div>
                                    </div>
                                    <div x-show="open" x-cloak class="absolute z-30 mt-2 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                                        <div class="max-h-72 overflow-y-auto">
                                            <template x-for="(option, index) in filteredOptions" :key="option.value">
                                                <button type="button" x-on:click="choose(option)" x-on:mouseenter="activeIndex = index" class="flex w-full items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 text-left transition-colors last:border-b-0" x-bind:class="{ 'bg-slate-50': activeIndex === index, 'bg-slate-100': selectedValue === option.value && activeIndex !== index }">
                                                    <span class="min-w-0">
                                                        <span class="block text-sm font-semibold text-slate-900" x-text="option.label"></span>
                                                        <span class="mt-1 block text-xs text-slate-400" x-show="option.meta" x-text="option.meta"></span>
                                                    </span>
                                                    <span class="material-symbols-outlined text-sm text-slate-400">local_shipping</span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <label class="block text-xs font-bold text-slate-500 mb-1">State / Province</label>
                                <div wire:key="billing-state-global-{{ $billing_address['country'] ?? 'none' }}" class="relative" x-data="ninoAddressCombobox({ selectedValue: @entangle('billing_address.state').live, options: @js($billingStatePickerOptions), placeholder: 'Search state or province...' })" x-on:click.outside="open = false">
                                    <div class="relative rounded-xl border border-slate-200 bg-white px-3 py-3 shadow-sm transition-all" x-bind:class="{ 'ring-2 ring-slate-900/10 border-slate-400 shadow-md': open }">
                                        <div class="mb-2 flex items-center justify-between gap-3">
                                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Searchable state picker</p>
                                            <div class="flex items-center gap-2 text-[11px] font-mono text-slate-400">
                                                <span x-show="selectedValue" x-cloak>Selected</span>
                                                <span x-show="! selectedValue && options.length > 0" x-cloak><span x-text="options.length"></span> loaded</span>
                                            </div>
                                        </div>
                                        <div class="relative">
                                            <input type="text" x-model="query" x-on:focus="open = true; syncActiveIndex()" x-on:input="open = true; if (query !== selectedLabel) { selectedValue = ''; } syncActiveIndex()" x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="chooseActive()" x-on:keydown.escape.prevent="open = false" class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 shadow-none focus:ring-0 pr-24" placeholder="Search state or province..." autocomplete="off">
                                            <div class="pointer-events-none absolute inset-y-0 right-16 flex items-center text-slate-400"><span class="material-symbols-outlined text-base">search</span></div>
                                            <button type="button" x-show="selectedValue" x-cloak x-on:click="clear()" class="absolute inset-y-0 right-0 inline-flex items-center text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500 transition-colors hover:text-slate-900">Clear</button>
                                        </div>
                                    </div>
                                    <div x-show="open" x-cloak class="absolute z-30 mt-2 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                                        <div class="max-h-72 overflow-y-auto">
                                            <template x-for="(option, index) in filteredOptions" :key="option.value">
                                                <button type="button" x-on:click="choose(option)" x-on:mouseenter="activeIndex = index" class="flex w-full items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 text-left transition-colors last:border-b-0" x-bind:class="{ 'bg-slate-50': activeIndex === index, 'bg-slate-100': selectedValue === option.value && activeIndex !== index }">
                                                    <span class="min-w-0">
                                                        <span class="block text-sm font-semibold text-slate-900" x-text="option.label"></span>
                                                        <span class="mt-1 block text-xs text-slate-400" x-show="option.meta" x-text="option.meta"></span>
                                                    </span>
                                                    <span class="material-symbols-outlined text-sm text-slate-400">map</span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Postal Code</label>
                            <input type="text" wire:model="billing_address.postal_code" class="w-full px-3 py-2 bg-canvas border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-slate-400 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">City</label>
                            @if($billingIsMorocco)
                                <div class="flex h-11 items-center rounded-lg border border-slate-200 bg-canvas px-3 text-sm font-semibold text-slate-900">
                                    {{ $billing_address['city'] ?: 'Select a district to populate the city' }}
                                </div>
                            @else
                                <div wire:key="billing-city-global-{{ ($billing_address['country'] ?? 'none').'-'.md5((string) ($billing_address['state'] ?? '')) }}" class="relative" x-data="ninoAddressCombobox({ selectedValue: @entangle('billing_address.city').live, options: @js($billingGlobalCityPickerOptions), placeholder: 'Search billing city...' })" x-on:click.outside="open = false">
                                    <div class="relative rounded-xl border border-slate-200 bg-white px-3 py-3 shadow-sm transition-all" x-bind:class="{ 'ring-2 ring-slate-900/10 border-slate-400 shadow-md': open }">
                                        <div class="mb-2 flex items-center justify-between gap-3">
                                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Searchable city picker</p>
                                            <div class="flex items-center gap-2 text-[11px] font-mono text-slate-400">
                                                <span x-show="selectedValue" x-cloak>Selected</span>
                                                <span x-show="! selectedValue && options.length > 0" x-cloak><span x-text="options.length"></span> loaded</span>
                                            </div>
                                        </div>
                                        <div class="relative">
                                            <input type="text" x-model="query" x-on:focus="open = true; syncActiveIndex()" x-on:input="open = true; if (query !== selectedLabel) { selectedValue = ''; } syncActiveIndex()" x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="chooseActive()" x-on:keydown.escape.prevent="open = false" class="w-full border-0 bg-transparent px-0 py-0 text-sm text-slate-900 shadow-none focus:ring-0 pr-24" placeholder="Search billing city..." autocomplete="off">
                                            <div class="pointer-events-none absolute inset-y-0 right-16 flex items-center text-slate-400"><span class="material-symbols-outlined text-base">search</span></div>
                                            <button type="button" x-show="selectedValue" x-cloak x-on:click="clear()" class="absolute inset-y-0 right-0 inline-flex items-center text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500 transition-colors hover:text-slate-900">Clear</button>
                                        </div>
                                    </div>
                                    <div x-show="open" x-cloak class="absolute z-30 mt-2 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                                        <div class="max-h-72 overflow-y-auto">
                                            <template x-for="(option, index) in filteredOptions" :key="option.value">
                                                <button type="button" x-on:click="choose(option)" x-on:mouseenter="activeIndex = index" class="flex w-full items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 text-left transition-colors last:border-b-0" x-bind:class="{ 'bg-slate-50': activeIndex === index, 'bg-slate-100': selectedValue === option.value && activeIndex !== index }">
                                                    <span class="min-w-0">
                                                        <span class="block text-sm font-semibold text-slate-900" x-text="option.label"></span>
                                                        <span class="mt-1 block text-xs text-slate-400" x-show="option.meta" x-text="option.meta"></span>
                                                    </span>
                                                    <span class="material-symbols-outlined text-sm text-slate-400">location_city</span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 mb-1">Address Line 1</label>
                            <input type="text" wire:model="billing_address.address_line_1" class="w-full px-3 py-2 bg-canvas border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-slate-400 outline-none">
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar Summary --}}
        <div class="space-y-6">
            <div class="bg-white rounded-md border border-slate-200 shadow-sm p-6 sticky top-20">
                <h2 class="text-sm font-bold text-outline uppercase tracking-wider mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">payments</span> Order Summary
                </h2>
                
                <div class="space-y-3 mb-6">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Subtotal</span>
                        <span class="font-bold">{{ number_format($this->subtotal, 2) }} MAD</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Tax</span>
                        <span class="font-bold">0.00 MAD</span>
                    </div>
                    @if($discountTotal > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Discount</span>
                            <span class="font-bold text-red-600">-{{ number_format($discountTotal, 2) }} MAD</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-sm pb-3 border-b border-slate-200">
                        <span class="text-slate-500">Shipping Estimate</span>
                        <span class="font-bold">{{ number_format($this->shipping_total, 2) }} MAD</span>
                    </div>
                    @if($this->shippingQuote['is_api'] && ! $this->shippingQuote['has_match'])
                        <p class="text-xs text-slate-500">{{ $this->shippingQuote['message'] }}</p>
                    @endif
                    <div class="flex justify-between text-base pt-2">
                        <span class="font-bold text-slate-900 uppercase tracking-tight">Grand Total</span>
                        <span class="font-black text-slate-800">{{ number_format($this->grand_total, 2) }} MAD</span>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Payment Method</label>
                        <select wire:model="payment_method" class="w-full px-3 py-2 bg-canvas border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-slate-400 outline-none">
                            @foreach($availablePaymentMethods as $paymentOption)
                                <option value="{{ $paymentOption['value'] }}">{{ $paymentOption['label'] }}</option>
                            @endforeach
                        </select>
                        @php
                            $selectedPaymentMethodMeta = collect($availablePaymentMethods)->firstWhere('value', $this->payment_method)['meta'] ?? null;
                        @endphp
                        @if(filled($selectedPaymentMethodMeta))
                            <p class="mt-2 text-[11px] text-slate-500">{{ $selectedPaymentMethodMeta }}</p>
                        @endif
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Coupon</label>
                        <div class="flex items-center gap-2">
                            <input type="text" wire:model.defer="coupon_code" class="w-full px-3 py-2 bg-canvas border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-slate-400 outline-none" placeholder="Enter coupon code">
                            @if(($appliedCoupon['valid'] ?? false) && filled($this->applied_coupon_code))
                                <button type="button" wire:click="removeCoupon" class="px-3 py-2 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:text-slate-900 transition">
                                    Remove
                                </button>
                            @else
                                <button type="button" wire:click="applyCoupon" class="px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition">
                                    Apply
                                </button>
                            @endif
                        </div>
                        @if(($appliedCoupon['valid'] ?? false) && $discountTotal > 0)
                            <p class="mt-2 text-[11px] text-emerald-700">
                                {{ $this->applied_coupon_code }} applied. Discount: {{ number_format($discountTotal, 2) }} MAD.
                            </p>
                        @elseif(filled($this->applied_coupon_code) && ! ($appliedCoupon['valid'] ?? false))
                            <p class="mt-2 text-[11px] text-red-600">
                                {{ $appliedCoupon['error'] ?? 'The applied coupon is no longer valid.' }}
                            </p>
                        @elseif(filled(data_get($couponFeedback, 'message')))
                            <p class="mt-2 text-[11px] {{ data_get($couponFeedback, 'tone') === 'success' ? 'text-emerald-700' : 'text-red-600' }}">
                                {{ data_get($couponFeedback, 'message') }}
                            </p>
                        @endif
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Internal Admin Notes</label>
                        <textarea wire:model="admin_notes" rows="3" 
                                  class="w-full px-3 py-2 bg-canvas border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-slate-400 outline-none" 
                                  placeholder="Notes for the team..."></textarea>
                    </div>

                    @error('save')
                        <div class="p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-xs text-red-600">
                            {{ $message }}
                        </div>
                    @enderror

                    <button type="submit" 
                            class="w-full py-3 bg-slate-800 text-white rounded-md text-sm font-bold hover:bg-slate-800/90 transition-all shadow-sm flex items-center justify-center gap-2">
                        <span wire:loading wire:target="save" class="animate-spin text-sm">refresh</span>
                        Place Manual Order
                    </button>
                    <p class="text-[10px] text-center text-slate-500 px-4">
                        By placing this order, stock items will be prepared according to active quantities.
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    if (! window.ninoAddressCombobox) {
        window.ninoAddressCombobox = function (config) {
            return {
                selectedValue: config.selectedValue,
                options: config.options ?? [],
                query: '',
                open: false,
                activeIndex: 0,
                init() {
                    this.query = this.selectedLabel;

                    this.$watch('selectedValue', () => {
                        if (! this.open) {
                            this.query = this.selectedLabel;
                        }
                    });
                },
                get selectedOption() {
                    return this.options.find(option => this.sameValue(option.value, this.selectedValue)) ?? null;
                },
                get selectedLabel() {
                    return this.selectedOption?.label ?? '';
                },
                get filteredOptions() {
                    const term = this.query.toLowerCase().trim();

                    if (term === '') {
                        return this.options.slice(0, 80);
                    }

                    return this.options.filter(option => (option.search ?? option.label ?? '').includes(term)).slice(0, 80);
                },
                syncActiveIndex() {
                    const index = this.filteredOptions.findIndex(option => this.sameValue(option.value, this.selectedValue));
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
                    this.selectedValue = option.value;
                    this.query = option.label;
                    this.open = false;
                },
                clear() {
                    this.selectedValue = '';
                    this.query = '';
                    this.activeIndex = 0;
                    this.open = false;
                },
                sameValue(left, right) {
                    return String(left ?? '') === String(right ?? '');
                },
            };
        };
    }
</script>
