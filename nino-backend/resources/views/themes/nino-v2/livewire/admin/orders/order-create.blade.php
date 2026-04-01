@section('title', 'Create Manual Order')

@section('header')
    <div class="page-header">
        <div>
            <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-1 text-xs font-bold uppercase tracking-[0.18em] text-[#617169] transition-colors hover:text-[#17302A]">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                Back to Orders
            </a>
            <h1 class="page-title mt-2">Create Manual Order</h1>
            <p class="page-subtitle mt-1">Build an order for phone, showroom, or support-assisted purchases without leaving the admin workspace.</p>
        </div>

        <div class="hidden flex-wrap items-center gap-2 md:flex">
            <span class="rounded-full border border-[rgba(120,112,95,0.12)] bg-[#FCFBF8] px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-[#8C7B65]">Manual Checkout</span>
            <span class="rounded-full border border-[rgba(120,112,95,0.12)] bg-[#FCFBF8] px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-[#8C7B65]">Currency: MAD</span>
        </div>
    </div>
@endsection

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
    $selectedCustomerName = $selectedCustomer?->full_name ?? $selectedCustomer?->name;
@endphp

<div class="mx-auto max-w-[1480px] pb-28 md:pb-8 xl:pb-2">
    <form id="manual-order-form" wire:submit.prevent="save" class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1.24fr)_360px]">
        <div class="space-y-5">
            <section class="page-section p-5">
                <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="filter-label">Customer</p>
                        <h2 class="mt-2 text-lg font-bold tracking-tight text-[#17302A]">Customer Information</h2>
                        <p class="mt-1 text-sm text-[#617169]">Search an existing customer or create a new profile inline, then let the form prefill delivery contact details.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if($selectedCustomer)
                            <span class="inline-flex items-center gap-2 rounded-full bg-[#EFF4F1] px-3 py-1 text-[11px] font-semibold text-[#245848]">
                                <span class="material-symbols-outlined text-[0.95rem]">person_check</span>
                                Profile linked
                            </span>
                        @else
                            <x-nino.button
                                type="button"
                                variant="{{ $show_create_customer_form ? 'outline' : 'secondary' }}"
                                icon="{{ $show_create_customer_form ? 'close' : 'person_add' }}"
                                wire:click="{{ $show_create_customer_form ? 'cancelCreatingCustomer' : 'startCreatingCustomer' }}"
                                class="!px-4 !py-2.5"
                            >
                                {{ $show_create_customer_form ? 'Cancel new customer' : 'Create new customer' }}
                            </x-nino.button>
                        @endif
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-[minmax(0,1.55fr)_minmax(250px,0.85fr)]">
                    <div class="relative">
                        <label class="filter-label" for="manual-order-customer">Search Customer</label>
                        <input
                            id="manual-order-customer"
                            type="text"
                            wire:model.live="search_customer"
                            class="input-field"
                            placeholder="Search by name, email, or phone..."
                        >

                        @if(count($searchResults) > 0 && ! $customer_id)
                            <div class="absolute z-20 mt-2 w-full overflow-hidden rounded-xl border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] shadow-[0_18px_38px_-22px_rgba(17,30,26,0.22)]">
                                @foreach($searchResults as $result)
                                    <button
                                        type="button"
                                        wire:click="selectCustomer({{ $result->id }})"
                                        class="flex w-full items-center justify-between gap-3 border-b border-[rgba(145,133,109,0.12)] px-4 py-3 text-left transition-colors last:border-b-0 hover:bg-[#F7F2E8]"
                                    >
                                        <span class="text-sm font-semibold text-[#17302A]">{{ $result->name }}</span>
                                        <span class="text-xs text-[#617169]">{{ $result->email }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        @if(! $customer_id && $search_customer && strlen($search_customer) > 2 && count($searchResults) === 0)
                            <p class="mt-2 text-xs leading-5 text-[#617169]">No customer matched. Create a new profile and keep working without leaving the order.</p>
                        @endif

                        @if($customer_form_feedback)
                            <div @class([
                                'mt-3 rounded-[1rem] border px-3 py-2 text-sm',
                                'border-[#CFE3D8] bg-[#F3FAF6] text-[#245848]' => ($customer_form_feedback['tone'] ?? 'success') === 'success',
                                'border-[#EFD7B6] bg-[#FBF6EA] text-[#8C5E2F]' => ($customer_form_feedback['tone'] ?? '') !== 'success',
                            ])>
                                {{ $customer_form_feedback['message'] ?? '' }}
                            </div>
                        @endif

                        @error('customer_id')
                            <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="rounded-[1.1rem] border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] px-4 py-4">
                        @if($selectedCustomer)
                            <p class="text-sm font-semibold text-[#17302A]">{{ $selectedCustomerName }}</p>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs text-[#617169]">
                                <span class="rounded-full bg-[#F6F2EC] px-3 py-1">{{ $selectedCustomer->email }}</span>
                                @if($selectedCustomer->phone)
                                    <span class="rounded-full bg-[#EFF4F1] px-3 py-1 text-[#245848]">{{ $selectedCustomer->phone }}</span>
                                @endif
                            </div>
                            <p class="mt-3 text-xs text-[#617169]">Delivery fields will use this profile when matching data exists.</p>
                        @else
                            <p class="text-sm font-semibold text-[#17302A]">No customer linked yet</p>
                            <p class="mt-2 text-xs leading-5 text-[#617169]">Search by name, email, or phone, or create a new customer without leaving this order.</p>
                        @endif
                    </div>
                </div>

                @if(! $selectedCustomer && $show_create_customer_form)
                    <div class="mt-4 rounded-[1.2rem] border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] p-4">
                        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="filter-label">New Customer</p>
                                <h3 class="mt-2 text-base font-bold text-[#17302A]">Create and Link Customer</h3>
                                <p class="mt-1 text-sm text-[#617169]">Create a real customer profile in place. Email is used for account setup and future order communication.</p>
                            </div>
                            <span class="inline-flex items-center gap-2 rounded-full bg-[#F8F4EC] px-3 py-1 text-[11px] font-semibold text-[#8C5E2F]">
                                <span class="material-symbols-outlined text-[0.95rem]">bolt</span>
                                Inline creation
                            </span>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="filter-label" for="new-customer-first-name">First Name</label>
                                <input id="new-customer-first-name" type="text" wire:model.blur="new_customer.first_name" class="input-field" placeholder="Sara">
                                @error('new_customer.first_name')
                                    <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="filter-label" for="new-customer-last-name">Last Name</label>
                                <input id="new-customer-last-name" type="text" wire:model.blur="new_customer.last_name" class="input-field" placeholder="El Idrissi">
                                @error('new_customer.last_name')
                                    <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="filter-label" for="new-customer-email">Email</label>
                                <input id="new-customer-email" type="email" wire:model.blur="new_customer.email" class="input-field" placeholder="customer@example.com">
                                @error('new_customer.email')
                                    <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="filter-label" for="new-customer-phone">Phone</label>
                                <input id="new-customer-phone" type="tel" wire:model.blur="new_customer.phone" class="input-field" placeholder="+212612345678">
                                @error('new_customer.phone')
                                    <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-[rgba(145,133,109,0.16)] pt-4">
                            <p class="text-xs leading-5 text-[#617169]">If this email already belongs to a customer, the existing profile will be linked automatically.</p>
                            <div class="flex flex-wrap gap-2">
                                <x-nino.button type="button" variant="outline" wire:click="cancelCreatingCustomer">Cancel</x-nino.button>
                                <x-nino.button type="button" icon="person_add" wire:click="createCustomer">Create and Link</x-nino.button>
                            </div>
                        </div>
                    </div>
                @endif
            </section>

            <section class="page-section p-5">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="filter-label">Basket</p>
                        <h2 class="mt-2 text-lg font-bold tracking-tight text-[#17302A]">Basket Composition</h2>
                        <p class="mt-1 text-sm text-[#617169]">Capture product, quantity, and operator-adjusted price without leaving the page.</p>
                    </div>

                    <x-nino.button type="button" variant="secondary" icon="add" wire:click="addItem" class="!px-4 !py-2.5">
                        Add Product
                    </x-nino.button>
                </div>

                <div class="space-y-3">
                    @foreach($items as $index => $item)
                        <div class="surface-panel p-3.5">
                            <div class="grid gap-3 lg:grid-cols-[minmax(0,1.7fr)_128px_86px_112px_auto]">
                                <div>
                                    <label class="filter-label" for="order-item-product-{{ $index }}">Product</label>
                                    <select wire:model.live="items.{{ $index }}.product_id" id="order-item-product-{{ $index }}" class="input-field">
                                        <option value="">Select Product</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="filter-label" for="order-item-price-{{ $index }}">Price</label>
                                    <div class="relative">
                                        <input type="number" step="0.01" wire:model.live.debounce.300ms="items.{{ $index }}.price" id="order-item-price-{{ $index }}" class="input-field pl-11">
                                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#617169]">MAD</span>
                                    </div>
                                </div>

                                <div>
                                    <label class="filter-label" for="order-item-quantity-{{ $index }}">Qty</label>
                                    <input type="number" min="1" wire:model.live="items.{{ $index }}.quantity" id="order-item-quantity-{{ $index }}" class="input-field">
                                </div>

                                <div>
                                    <label class="filter-label">Line Total</label>
                                    <div class="flex h-12 items-center justify-end rounded-[0.95rem] border border-[rgba(120,112,95,0.12)] bg-[#F8F4EC] px-4 text-sm font-semibold text-[#17302A]">
                                        {{ number_format($item['price'] * $item['quantity'], 2) }} MAD
                                    </div>
                                </div>

                                <div class="flex items-end justify-end">
                                    <button
                                        type="button"
                                        wire:click="removeItem({{ $index }})"
                                        class="inline-flex h-12 w-12 items-center justify-center rounded-[0.95rem] border border-[#EFC5BE] bg-[#FCEDEA] text-[#C94B3C] transition-colors hover:bg-[#C94B3C] hover:text-white"
                                        aria-label="Remove item {{ $index + 1 }}"
                                    >
                                        <span class="material-symbols-outlined text-[1.1rem]">delete</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 flex items-center justify-between border-t border-[rgba(145,133,109,0.16)] pt-3 text-sm">
                    <p class="text-[#617169]">Subtotal updates live as quantities and prices change.</p>
                    <p class="font-bold text-[#17302A]">Subtotal: {{ number_format($this->subtotal, 2) }} MAD</p>
                </div>
            </section>

            <section class="page-section p-5">
                <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="filter-label">Delivery</p>
                        <h2 class="mt-2 text-lg font-bold tracking-tight text-[#17302A]">Shipping Address</h2>
                        <p class="mt-1 text-sm text-[#617169]">Capture the destination first, then choose the matching shipping method below.</p>
                    </div>
                    <span @class([
                        'inline-flex items-center gap-2 rounded-full px-3 py-1 text-[11px] font-semibold',
                        'bg-[#EFF4F1] text-[#245848]' => $shippingDestinationStatus['tone'] === 'ready',
                        'bg-[#F8F4EC] text-[#8C5E2F]' => $shippingDestinationStatus['tone'] !== 'ready',
                    ])>
                        <span class="material-symbols-outlined text-[0.95rem]">{{ $shippingDestinationStatus['tone'] === 'ready' ? 'task_alt' : 'edit_location_alt' }}</span>
                        {{ $shippingDestinationStatus['title'] }}
                    </span>
                </div>

                <div class="grid gap-4 lg:grid-cols-12">
                    <div class="lg:col-span-4">
                        <label class="filter-label" for="shipping-country">Country</label>
                        <div class="relative" x-data="ninoAddressCombobox({ selectedValue: @entangle('shipping_address.country').live, options: @js($countryPickerOptions), placeholder: 'Search country...', badgeLabel: 'Selected country', emptyLabel: 'No countries available.' })" x-on:click.outside="open = false">
                            <div class="rounded-[1.2rem] border border-[rgba(120,112,95,0.16)] bg-[#FCFBF8] px-3 py-3 shadow-[0_10px_24px_-20px_rgba(23,48,42,0.35)] transition-all" x-bind:class="{ 'ring-2 ring-[#245848]/15 border-[#245848]/35 shadow-[0_18px_34px_-24px_rgba(23,48,42,0.45)]': open }">
                                <div class="mb-2 flex items-center justify-between gap-3">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-[#8C7B65]">Country</p>
                                    <span class="text-[11px] font-mono text-[#8C7B65]" x-show="selectedOption" x-text="selectedOption?.meta"></span>
                                </div>
                                <div class="relative">
                                    <input id="shipping-country" type="text" x-model="query" x-on:focus="open = true; syncActiveIndex()" x-on:input="open = true; if (query !== selectedLabel) { selectedValue = ''; } syncActiveIndex()" x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="chooseActive()" x-on:keydown.escape.prevent="open = false" class="input-field border-0 bg-transparent px-0 py-0 pr-24 shadow-none focus:ring-0" placeholder="Search country..." autocomplete="off">
                                    <div class="pointer-events-none absolute inset-y-0 right-16 flex items-center text-[#8C7B65]"><span class="material-symbols-outlined text-[1rem]">search</span></div>
                                    <button type="button" x-show="selectedValue" x-cloak x-on:click="clear()" class="absolute inset-y-0 right-0 inline-flex items-center text-xs font-bold uppercase tracking-[0.18em] text-[#8C7B65] transition-colors hover:text-[#17302A]">Clear</button>
                                </div>
                            </div>
                            <div x-show="open" x-cloak class="absolute z-30 mt-2 w-full overflow-hidden rounded-[1.1rem] border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] shadow-[0_18px_38px_-22px_rgba(17,30,26,0.22)]">
                                <div class="max-h-72 overflow-y-auto">
                                    <template x-for="(option, index) in filteredOptions" :key="option.value">
                                        <button type="button" x-on:click="choose(option)" x-on:mouseenter="activeIndex = index" class="flex w-full items-center justify-between gap-3 border-b border-[rgba(145,133,109,0.08)] px-4 py-3 text-left transition-colors last:border-b-0" x-bind:class="{ 'bg-[#F7F2E8]': activeIndex === index, 'bg-[#EFF4F1]': selectedValue === option.value && activeIndex !== index }">
                                            <span class="flex min-w-0 items-center gap-2 text-sm font-semibold text-[#17302A]">
                                                <span x-text="option.flag"></span>
                                                <span x-text="option.label"></span>
                                            </span>
                                            <span class="font-mono text-[11px] text-[#8C7B65]" x-text="option.meta"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-4">
                        <label class="filter-label" for="shipping-first-name">First Name</label>
                        <input type="text" wire:model.live.debounce.300ms="shipping_address.first_name" id="shipping-first-name" class="input-field">
                    </div>

                    <div class="lg:col-span-4">
                        <label class="filter-label" for="shipping-last-name">Last Name</label>
                        <input type="text" wire:model.blur="shipping_address.last_name" id="shipping-last-name" class="input-field">
                    </div>

                    <div class="lg:col-span-5">
                        @if($shippingIsMorocco)
                            <label class="filter-label" for="shipping-city">Destination District</label>
                            <div wire:key="shipping-destination-ma" class="relative" x-data="ninoAddressCombobox({ selectedValue: @entangle('shipping_destination_id').live, options: @js($shippingDistrictPickerOptions), placeholder: 'Search by district or city...', badgeLabel: 'Selected district', emptyLabel: 'No synced shipping districts are available yet.' })" x-on:click.outside="open = false">
                                <div class="rounded-[1.2rem] border border-[rgba(120,112,95,0.16)] bg-[#FCFBF8] px-3 py-3 shadow-[0_10px_24px_-20px_rgba(23,48,42,0.35)] transition-all" x-bind:class="{ 'ring-2 ring-[#245848]/15 border-[#245848]/35 shadow-[0_18px_34px_-24px_rgba(23,48,42,0.45)]': open }">
                                    <div class="mb-2 flex items-center justify-between gap-3">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-[#8C7B65]">District</p>
                                        <div class="flex items-center gap-2 text-[11px] font-mono text-[#8C7B65]">
                                            <span x-show="selectedValue" x-cloak>Selected</span>
                                            <span x-show="! selectedValue && options.length > 0" x-cloak><span x-text="options.length"></span> loaded</span>
                                        </div>
                                    </div>
                                    <div class="relative">
                                        <input id="shipping-city" type="text" x-model="query" x-on:focus="open = true; syncActiveIndex()" x-on:input="open = true; if (query !== selectedLabel) { selectedValue = ''; } syncActiveIndex()" x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="chooseActive()" x-on:keydown.escape.prevent="open = false" class="input-field border-0 bg-transparent px-0 py-0 pr-24 shadow-none focus:ring-0" placeholder="Search by district or city..." autocomplete="off">
                                        <div class="pointer-events-none absolute inset-y-0 right-16 flex items-center text-[#8C7B65]"><span class="material-symbols-outlined text-[1rem]">search</span></div>
                                        <button type="button" x-show="selectedValue" x-cloak x-on:click="clear()" class="absolute inset-y-0 right-0 inline-flex items-center text-xs font-bold uppercase tracking-[0.18em] text-[#8C7B65] transition-colors hover:text-[#17302A]">Clear</button>
                                    </div>
                                </div>
                                <div x-show="open" x-cloak class="absolute z-30 mt-2 w-full overflow-hidden rounded-[1.1rem] border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] shadow-[0_18px_38px_-22px_rgba(17,30,26,0.22)]">
                                    <div class="max-h-72 overflow-y-auto">
                                        <template x-for="(option, index) in filteredOptions" :key="option.value">
                                            <button type="button" x-on:click="choose(option)" x-on:mouseenter="activeIndex = index" class="flex w-full items-center justify-between gap-3 border-b border-[rgba(145,133,109,0.08)] px-4 py-3 text-left transition-colors last:border-b-0" x-bind:class="{ 'bg-[#F7F2E8]': activeIndex === index, 'bg-[#EFF4F1]': selectedValue === option.value && activeIndex !== index }">
                                                <span class="min-w-0">
                                                    <span class="block text-sm font-semibold text-[#17302A]" x-text="option.label"></span>
                                                    <span class="mt-1 block text-xs text-[#8C7B65]" x-show="option.meta" x-text="option.meta"></span>
                                                </span>
                                                <span class="material-symbols-outlined text-[1rem] text-[#8C7B65]">local_shipping</span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        @else
                            <label class="filter-label" for="shipping-state">State / Province</label>
                            <div wire:key="shipping-state-global-{{ $shipping_address['country'] ?? 'none' }}" class="relative" x-data="ninoAddressCombobox({ selectedValue: @entangle('shipping_address.state').live, options: @js($shippingStatePickerOptions), placeholder: 'Search state or province...', badgeLabel: 'Selected state', emptyLabel: 'No states or provinces available for the selected country.' })" x-on:click.outside="open = false">
                                <div class="rounded-[1.2rem] border border-[rgba(120,112,95,0.16)] bg-[#FCFBF8] px-3 py-3 shadow-[0_10px_24px_-20px_rgba(23,48,42,0.35)] transition-all" x-bind:class="{ 'ring-2 ring-[#245848]/15 border-[#245848]/35 shadow-[0_18px_34px_-24px_rgba(23,48,42,0.45)]': open }">
                                    <div class="mb-2 flex items-center justify-between gap-3">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-[#8C7B65]">State / Province</p>
                                        <div class="flex items-center gap-2 text-[11px] font-mono text-[#8C7B65]">
                                            <span x-show="selectedValue" x-cloak>Selected</span>
                                            <span x-show="! selectedValue && options.length > 0" x-cloak><span x-text="options.length"></span> loaded</span>
                                        </div>
                                    </div>
                                    <div class="relative">
                                        <input id="shipping-state" type="text" x-model="query" x-on:focus="open = true; syncActiveIndex()" x-on:input="open = true; if (query !== selectedLabel) { selectedValue = ''; } syncActiveIndex()" x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="chooseActive()" x-on:keydown.escape.prevent="open = false" class="input-field border-0 bg-transparent px-0 py-0 pr-24 shadow-none focus:ring-0" placeholder="Search state or province..." autocomplete="off">
                                        <div class="pointer-events-none absolute inset-y-0 right-16 flex items-center text-[#8C7B65]"><span class="material-symbols-outlined text-[1rem]">search</span></div>
                                        <button type="button" x-show="selectedValue" x-cloak x-on:click="clear()" class="absolute inset-y-0 right-0 inline-flex items-center text-xs font-bold uppercase tracking-[0.18em] text-[#8C7B65] transition-colors hover:text-[#17302A]">Clear</button>
                                    </div>
                                </div>
                                <div x-show="open" x-cloak class="absolute z-30 mt-2 w-full overflow-hidden rounded-[1.1rem] border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] shadow-[0_18px_38px_-22px_rgba(17,30,26,0.22)]">
                                    <div class="max-h-72 overflow-y-auto">
                                        <template x-for="(option, index) in filteredOptions" :key="option.value">
                                            <button type="button" x-on:click="choose(option)" x-on:mouseenter="activeIndex = index" class="flex w-full items-center justify-between gap-3 border-b border-[rgba(145,133,109,0.08)] px-4 py-3 text-left transition-colors last:border-b-0" x-bind:class="{ 'bg-[#F7F2E8]': activeIndex === index, 'bg-[#EFF4F1]': selectedValue === option.value && activeIndex !== index }">
                                                <span class="min-w-0">
                                                    <span class="block text-sm font-semibold text-[#17302A]" x-text="option.label"></span>
                                                    <span class="mt-1 block text-xs text-[#8C7B65]" x-show="option.meta" x-text="option.meta"></span>
                                                </span>
                                                <span class="material-symbols-outlined text-[1rem] text-[#8C7B65]">map</span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <p class="mt-2 text-xs text-[#617169]">{{ $shippingDestinationStatus['message'] }}</p>
                    </div>

                    <div class="lg:col-span-4">
                        <label class="filter-label" for="shipping-city">City</label>
                        @if($shippingIsMorocco)
                            <div class="rounded-[1.1rem] border border-[rgba(120,112,95,0.12)] bg-[#F8F4EC] px-4 py-3">
                                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#8C7B65]">Derived city</p>
                                <p class="mt-1 text-sm font-semibold text-[#17302A]">
                                    {{ $shipping_address['city'] ? 'City: '.$shipping_address['city'] : 'Select a district to derive the city' }}
                                </p>
                            </div>
                        @else
                            <div wire:key="shipping-city-global-{{ ($shipping_address['country'] ?? 'none').'-'.md5((string) ($shipping_address['state'] ?? '')) }}" class="relative" x-data="ninoAddressCombobox({ selectedValue: @entangle('shipping_address.city').live, options: @js($shippingGlobalCityPickerOptions), placeholder: 'Search city...', badgeLabel: 'Selected city', emptyLabel: 'No cities available for the selected state.' })" x-on:click.outside="open = false">
                                <div class="rounded-[1.2rem] border border-[rgba(120,112,95,0.16)] bg-[#FCFBF8] px-3 py-3 shadow-[0_10px_24px_-20px_rgba(23,48,42,0.35)] transition-all" x-bind:class="{ 'ring-2 ring-[#245848]/15 border-[#245848]/35 shadow-[0_18px_34px_-24px_rgba(23,48,42,0.45)]': open }">
                                    <div class="mb-2 flex items-center justify-between gap-3">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-[#8C7B65]">City</p>
                                        <div class="flex items-center gap-2 text-[11px] font-mono text-[#8C7B65]">
                                            <span x-show="selectedValue" x-cloak>Selected</span>
                                            <span x-show="! selectedValue && options.length > 0" x-cloak><span x-text="options.length"></span> loaded</span>
                                        </div>
                                    </div>
                                    <div class="relative">
                                        <input id="shipping-city" type="text" x-model="query" x-on:focus="open = true; syncActiveIndex()" x-on:input="open = true; if (query !== selectedLabel) { selectedValue = ''; } syncActiveIndex()" x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="chooseActive()" x-on:keydown.escape.prevent="open = false" class="input-field border-0 bg-transparent px-0 py-0 pr-24 shadow-none focus:ring-0" placeholder="Search city..." autocomplete="off">
                                        <div class="pointer-events-none absolute inset-y-0 right-16 flex items-center text-[#8C7B65]"><span class="material-symbols-outlined text-[1rem]">search</span></div>
                                        <button type="button" x-show="selectedValue" x-cloak x-on:click="clear()" class="absolute inset-y-0 right-0 inline-flex items-center text-xs font-bold uppercase tracking-[0.18em] text-[#8C7B65] transition-colors hover:text-[#17302A]">Clear</button>
                                    </div>
                                </div>
                                <div x-show="open" x-cloak class="absolute z-30 mt-2 w-full overflow-hidden rounded-[1.1rem] border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] shadow-[0_18px_38px_-22px_rgba(17,30,26,0.22)]">
                                    <div class="max-h-72 overflow-y-auto">
                                        <template x-for="(option, index) in filteredOptions" :key="option.value">
                                            <button type="button" x-on:click="choose(option)" x-on:mouseenter="activeIndex = index" class="flex w-full items-center justify-between gap-3 border-b border-[rgba(145,133,109,0.08)] px-4 py-3 text-left transition-colors last:border-b-0" x-bind:class="{ 'bg-[#F7F2E8]': activeIndex === index, 'bg-[#EFF4F1]': selectedValue === option.value && activeIndex !== index }">
                                                <span class="min-w-0">
                                                    <span class="block text-sm font-semibold text-[#17302A]" x-text="option.label"></span>
                                                    <span class="mt-1 block text-xs text-[#8C7B65]" x-show="option.meta" x-text="option.meta"></span>
                                                </span>
                                                <span class="material-symbols-outlined text-[1rem] text-[#8C7B65]">location_city</span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="lg:col-span-3">
                        <label class="filter-label" for="shipping-postal">Postal Code</label>
                        <input type="text" wire:model.blur="shipping_address.postal_code" id="shipping-postal" class="input-field">
                    </div>

                    <div class="lg:col-span-8">
                        <label class="filter-label" for="shipping-address-line-1">Address Line 1</label>
                        <input type="text" wire:model.live.debounce.300ms="shipping_address.address_line_1" id="shipping-address-line-1" class="input-field">
                    </div>

                    <div class="lg:col-span-4">
                        <label class="filter-label" for="shipping-phone">Phone</label>
                        <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
                            <div
                                class="relative"
                                x-data="ninoAddressCombobox({
                                    selectedValue: @entangle('shipping_phone_country').live,
                                    options: @js($countryPickerOptions),
                                    placeholder: 'Search country code...',
                                    badgeLabel: 'Country code',
                                    emptyLabel: 'No calling codes available.',
                                })"
                                x-on:click.outside="open = false"
                            >
                                <button
                                    type="button"
                                    id="shipping-phone"
                                    x-on:click="open = !open; if (open) { query = selectedLabel; syncActiveIndex(); }"
                                    class="flex h-12 w-full items-center justify-between rounded-[1rem] border border-[rgba(120,112,95,0.16)] bg-[#FCFBF8] px-3 shadow-[0_10px_24px_-20px_rgba(23,48,42,0.35)] transition-all"
                                    x-bind:class="{ 'ring-2 ring-[#245848]/15 border-[#245848]/35 shadow-[0_18px_34px_-24px_rgba(23,48,42,0.45)]': open }"
                                >
                                    <span class="flex min-w-0 items-center gap-2">
                                        <span class="text-base" x-text="selectedOption?.flag || '🌍'"></span>
                                        <span class="truncate font-mono text-sm font-semibold text-[#17302A]" x-text="selectedOption?.meta || '+212'"></span>
                                    </span>
                                    <span class="material-symbols-outlined text-[1rem] text-[#8C7B65]">expand_more</span>
                                </button>
                                <div x-show="open" x-cloak class="absolute z-30 mt-2 w-full overflow-hidden rounded-[1.1rem] border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] shadow-[0_18px_38px_-22px_rgba(17,30,26,0.22)]">
                                    <div class="border-b border-[rgba(145,133,109,0.12)] p-3">
                                        <input type="text" x-model="query" x-on:input="syncActiveIndex()" x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="chooseActive()" x-on:keydown.escape.prevent="open = false" class="input-field" placeholder="Search country or code..." autocomplete="off">
                                    </div>
                                    <div class="max-h-72 overflow-y-auto">
                                        <template x-for="(option, index) in filteredOptions" :key="option.value">
                                            <button type="button" x-on:click="choose(option)" x-on:mouseenter="activeIndex = index" class="flex w-full items-center justify-between gap-3 border-b border-[rgba(145,133,109,0.08)] px-4 py-3 text-left transition-colors last:border-b-0" x-bind:class="{ 'bg-[#F7F2E8]': activeIndex === index, 'bg-[#EFF4F1]': selectedValue === option.value && activeIndex !== index }">
                                                <span class="flex min-w-0 items-center gap-2 text-sm font-semibold text-[#17302A]">
                                                    <span x-text="option.flag"></span>
                                                    <span x-text="option.label"></span>
                                                </span>
                                                <span class="font-mono text-[11px] text-[#8C7B65]" x-text="option.meta"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <input type="tel" wire:model.live="shipping_phone_local" class="input-field" placeholder="612345678">
                                <p class="mt-2 text-[11px] text-[#617169]">
                                    <span class="font-medium">Stored as:</span>
                                    <span class="font-mono">{{ $shipping_address['phone'] ?: 'international number pending' }}</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-12">
                        <label class="filter-label" for="shipping-address-line-2">Address Line 2</label>
                        <input type="text" wire:model.blur="shipping_address.address_line_2" id="shipping-address-line-2" class="input-field">
                    </div>
                </div>

                <label for="same_as_shipping" class="toggle-row mt-5">
                    <input type="checkbox" id="same_as_shipping" wire:model.live="same_as_shipping" class="h-4 w-4 rounded border-[rgba(145,133,109,0.28)] text-[#245848] focus:ring-[#245848]/25">
                    Billing address matches shipping address
                </label>
            </section>

            <div class="xl:hidden">
                @include('themes.nino-v2.livewire.admin.orders.partials.shipping-methods-panel')
            </div>

            @if(! $same_as_shipping)
                <section class="page-section p-5">
                    <div class="mb-4">
                        <p class="filter-label">Billing</p>
                        <h2 class="mt-2 text-lg font-bold tracking-tight text-[#17302A]">Billing Address</h2>
                        <p class="mt-1 text-sm text-[#617169]">Only use a separate billing record when the invoice destination differs from shipping.</p>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-12">
                        <div class="lg:col-span-4">
                            <label class="filter-label" for="billing-country">Country</label>
                            <div class="relative" x-data="ninoAddressCombobox({ selectedValue: @entangle('billing_address.country').live, options: @js($countryPickerOptions), placeholder: 'Search country...', badgeLabel: 'Selected country', emptyLabel: 'No countries available.' })" x-on:click.outside="open = false">
                                <div class="rounded-[1.2rem] border border-[rgba(120,112,95,0.16)] bg-[#FCFBF8] px-3 py-3 shadow-[0_10px_24px_-20px_rgba(23,48,42,0.35)] transition-all" x-bind:class="{ 'ring-2 ring-[#245848]/15 border-[#245848]/35 shadow-[0_18px_34px_-24px_rgba(23,48,42,0.45)]': open }">
                                    <div class="mb-2 flex items-center justify-between gap-3">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-[#8C7B65]">Country</p>
                                        <span class="text-[11px] font-mono text-[#8C7B65]" x-show="selectedOption" x-text="selectedOption?.meta"></span>
                                    </div>
                                    <div class="relative">
                                        <input id="billing-country" type="text" x-model="query" x-on:focus="open = true; syncActiveIndex()" x-on:input="open = true; if (query !== selectedLabel) { selectedValue = ''; } syncActiveIndex()" x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="chooseActive()" x-on:keydown.escape.prevent="open = false" class="input-field border-0 bg-transparent px-0 py-0 pr-24 shadow-none focus:ring-0" placeholder="Search country..." autocomplete="off">
                                        <div class="pointer-events-none absolute inset-y-0 right-16 flex items-center text-[#8C7B65]"><span class="material-symbols-outlined text-[1rem]">search</span></div>
                                        <button type="button" x-show="selectedValue" x-cloak x-on:click="clear()" class="absolute inset-y-0 right-0 inline-flex items-center text-xs font-bold uppercase tracking-[0.18em] text-[#8C7B65] transition-colors hover:text-[#17302A]">Clear</button>
                                    </div>
                                </div>
                                <div x-show="open" x-cloak class="absolute z-30 mt-2 w-full overflow-hidden rounded-[1.1rem] border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] shadow-[0_18px_38px_-22px_rgba(17,30,26,0.22)]">
                                    <div class="max-h-72 overflow-y-auto">
                                        <template x-for="(option, index) in filteredOptions" :key="option.value">
                                            <button type="button" x-on:click="choose(option)" x-on:mouseenter="activeIndex = index" class="flex w-full items-center justify-between gap-3 border-b border-[rgba(145,133,109,0.08)] px-4 py-3 text-left transition-colors last:border-b-0" x-bind:class="{ 'bg-[#F7F2E8]': activeIndex === index, 'bg-[#EFF4F1]': selectedValue === option.value && activeIndex !== index }">
                                                <span class="flex min-w-0 items-center gap-2 text-sm font-semibold text-[#17302A]">
                                                    <span x-text="option.flag"></span>
                                                    <span x-text="option.label"></span>
                                                </span>
                                                <span class="font-mono text-[11px] text-[#8C7B65]" x-text="option.meta"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-4">
                            <label class="filter-label" for="billing-first-name">First Name</label>
                            <input type="text" wire:model.blur="billing_address.first_name" id="billing-first-name" class="input-field">
                        </div>

                        <div class="lg:col-span-4">
                            <label class="filter-label" for="billing-last-name">Last Name</label>
                            <input type="text" wire:model.blur="billing_address.last_name" id="billing-last-name" class="input-field">
                        </div>

                        <div class="lg:col-span-5">
                            @if($billingIsMorocco)
                                <label class="filter-label" for="billing-destination">Destination District</label>
                                <div wire:key="billing-destination-ma" class="relative" x-data="ninoAddressCombobox({ selectedValue: @entangle('billing_destination_id').live, options: @js($billingDistrictPickerOptions), placeholder: 'Search by district or city...', badgeLabel: 'Selected district', emptyLabel: 'No synced Morocco districts are available yet.' })" x-on:click.outside="open = false">
                                    <div class="rounded-[1.2rem] border border-[rgba(120,112,95,0.16)] bg-[#FCFBF8] px-3 py-3 shadow-[0_10px_24px_-20px_rgba(23,48,42,0.35)] transition-all" x-bind:class="{ 'ring-2 ring-[#245848]/15 border-[#245848]/35 shadow-[0_18px_34px_-24px_rgba(23,48,42,0.45)]': open }">
                                        <div class="mb-2 flex items-center justify-between gap-3">
                                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-[#8C7B65]">District</p>
                                            <div class="flex items-center gap-2 text-[11px] font-mono text-[#8C7B65]">
                                                <span x-show="selectedValue" x-cloak>Selected</span>
                                                <span x-show="! selectedValue && options.length > 0" x-cloak><span x-text="options.length"></span> loaded</span>
                                            </div>
                                        </div>
                                        <div class="relative">
                                            <input id="billing-destination" type="text" x-model="query" x-on:focus="open = true; syncActiveIndex()" x-on:input="open = true; if (query !== selectedLabel) { selectedValue = ''; } syncActiveIndex()" x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="chooseActive()" x-on:keydown.escape.prevent="open = false" class="input-field border-0 bg-transparent px-0 py-0 pr-24 shadow-none focus:ring-0" placeholder="Search by district or city..." autocomplete="off">
                                            <div class="pointer-events-none absolute inset-y-0 right-16 flex items-center text-[#8C7B65]"><span class="material-symbols-outlined text-[1rem]">search</span></div>
                                            <button type="button" x-show="selectedValue" x-cloak x-on:click="clear()" class="absolute inset-y-0 right-0 inline-flex items-center text-xs font-bold uppercase tracking-[0.18em] text-[#8C7B65] transition-colors hover:text-[#17302A]">Clear</button>
                                        </div>
                                    </div>
                                    <div x-show="open" x-cloak class="absolute z-30 mt-2 w-full overflow-hidden rounded-[1.1rem] border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] shadow-[0_18px_38px_-22px_rgba(17,30,26,0.22)]">
                                        <div class="max-h-72 overflow-y-auto">
                                            <template x-for="(option, index) in filteredOptions" :key="option.value">
                                                <button type="button" x-on:click="choose(option)" x-on:mouseenter="activeIndex = index" class="flex w-full items-center justify-between gap-3 border-b border-[rgba(145,133,109,0.08)] px-4 py-3 text-left transition-colors last:border-b-0" x-bind:class="{ 'bg-[#F7F2E8]': activeIndex === index, 'bg-[#EFF4F1]': selectedValue === option.value && activeIndex !== index }">
                                                    <span class="min-w-0">
                                                        <span class="block text-sm font-semibold text-[#17302A]" x-text="option.label"></span>
                                                        <span class="mt-1 block text-xs text-[#8C7B65]" x-show="option.meta" x-text="option.meta"></span>
                                                    </span>
                                                    <span class="material-symbols-outlined text-[1rem] text-[#8C7B65]">local_shipping</span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <label class="filter-label" for="billing-state">State / Province</label>
                                <div wire:key="billing-state-global-{{ $billing_address['country'] ?? 'none' }}" class="relative" x-data="ninoAddressCombobox({ selectedValue: @entangle('billing_address.state').live, options: @js($billingStatePickerOptions), placeholder: 'Search state or province...', badgeLabel: 'Selected state', emptyLabel: 'No states or provinces available for the selected country.' })" x-on:click.outside="open = false">
                                    <div class="rounded-[1.2rem] border border-[rgba(120,112,95,0.16)] bg-[#FCFBF8] px-3 py-3 shadow-[0_10px_24px_-20px_rgba(23,48,42,0.35)] transition-all" x-bind:class="{ 'ring-2 ring-[#245848]/15 border-[#245848]/35 shadow-[0_18px_34px_-24px_rgba(23,48,42,0.45)]': open }">
                                        <div class="mb-2 flex items-center justify-between gap-3">
                                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-[#8C7B65]">State / Province</p>
                                            <div class="flex items-center gap-2 text-[11px] font-mono text-[#8C7B65]">
                                                <span x-show="selectedValue" x-cloak>Selected</span>
                                                <span x-show="! selectedValue && options.length > 0" x-cloak><span x-text="options.length"></span> loaded</span>
                                            </div>
                                        </div>
                                        <div class="relative">
                                            <input id="billing-state" type="text" x-model="query" x-on:focus="open = true; syncActiveIndex()" x-on:input="open = true; if (query !== selectedLabel) { selectedValue = ''; } syncActiveIndex()" x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="chooseActive()" x-on:keydown.escape.prevent="open = false" class="input-field border-0 bg-transparent px-0 py-0 pr-24 shadow-none focus:ring-0" placeholder="Search state or province..." autocomplete="off">
                                            <div class="pointer-events-none absolute inset-y-0 right-16 flex items-center text-[#8C7B65]"><span class="material-symbols-outlined text-[1rem]">search</span></div>
                                            <button type="button" x-show="selectedValue" x-cloak x-on:click="clear()" class="absolute inset-y-0 right-0 inline-flex items-center text-xs font-bold uppercase tracking-[0.18em] text-[#8C7B65] transition-colors hover:text-[#17302A]">Clear</button>
                                        </div>
                                    </div>
                                    <div x-show="open" x-cloak class="absolute z-30 mt-2 w-full overflow-hidden rounded-[1.1rem] border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] shadow-[0_18px_38px_-22px_rgba(17,30,26,0.22)]">
                                        <div class="max-h-72 overflow-y-auto">
                                            <template x-for="(option, index) in filteredOptions" :key="option.value">
                                                <button type="button" x-on:click="choose(option)" x-on:mouseenter="activeIndex = index" class="flex w-full items-center justify-between gap-3 border-b border-[rgba(145,133,109,0.08)] px-4 py-3 text-left transition-colors last:border-b-0" x-bind:class="{ 'bg-[#F7F2E8]': activeIndex === index, 'bg-[#EFF4F1]': selectedValue === option.value && activeIndex !== index }">
                                                    <span class="min-w-0">
                                                        <span class="block text-sm font-semibold text-[#17302A]" x-text="option.label"></span>
                                                        <span class="mt-1 block text-xs text-[#8C7B65]" x-show="option.meta" x-text="option.meta"></span>
                                                    </span>
                                                    <span class="material-symbols-outlined text-[1rem] text-[#8C7B65]">map</span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="lg:col-span-4">
                            <label class="filter-label" for="billing-city">City</label>
                            @if($billingIsMorocco)
                                <div class="rounded-[1.1rem] border border-[rgba(120,112,95,0.12)] bg-[#F8F4EC] px-4 py-3">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#8C7B65]">Derived city</p>
                                    <p class="mt-1 text-sm font-semibold text-[#17302A]">
                                        {{ $billing_address['city'] ? 'City: '.$billing_address['city'] : 'Select a district to derive the city' }}
                                    </p>
                                </div>
                            @else
                                <div wire:key="billing-city-global-{{ ($billing_address['country'] ?? 'none').'-'.md5((string) ($billing_address['state'] ?? '')) }}" class="relative" x-data="ninoAddressCombobox({ selectedValue: @entangle('billing_address.city').live, options: @js($billingGlobalCityPickerOptions), placeholder: 'Search billing city...', badgeLabel: 'Selected city', emptyLabel: 'No cities available for the selected state.' })" x-on:click.outside="open = false">
                                    <div class="rounded-[1.2rem] border border-[rgba(120,112,95,0.16)] bg-[#FCFBF8] px-3 py-3 shadow-[0_10px_24px_-20px_rgba(23,48,42,0.35)] transition-all" x-bind:class="{ 'ring-2 ring-[#245848]/15 border-[#245848]/35 shadow-[0_18px_34px_-24px_rgba(23,48,42,0.45)]': open }">
                                        <div class="mb-2 flex items-center justify-between gap-3">
                                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-[#8C7B65]">City</p>
                                            <div class="flex items-center gap-2 text-[11px] font-mono text-[#8C7B65]">
                                                <span x-show="selectedValue" x-cloak>Selected</span>
                                                <span x-show="! selectedValue && options.length > 0" x-cloak><span x-text="options.length"></span> loaded</span>
                                            </div>
                                        </div>
                                        <div class="relative">
                                            <input id="billing-city" type="text" x-model="query" x-on:focus="open = true; syncActiveIndex()" x-on:input="open = true; if (query !== selectedLabel) { selectedValue = ''; } syncActiveIndex()" x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="chooseActive()" x-on:keydown.escape.prevent="open = false" class="input-field border-0 bg-transparent px-0 py-0 pr-24 shadow-none focus:ring-0" placeholder="Search billing city..." autocomplete="off">
                                            <div class="pointer-events-none absolute inset-y-0 right-16 flex items-center text-[#8C7B65]"><span class="material-symbols-outlined text-[1rem]">search</span></div>
                                            <button type="button" x-show="selectedValue" x-cloak x-on:click="clear()" class="absolute inset-y-0 right-0 inline-flex items-center text-xs font-bold uppercase tracking-[0.18em] text-[#8C7B65] transition-colors hover:text-[#17302A]">Clear</button>
                                        </div>
                                    </div>
                                    <div x-show="open" x-cloak class="absolute z-30 mt-2 w-full overflow-hidden rounded-[1.1rem] border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] shadow-[0_18px_38px_-22px_rgba(17,30,26,0.22)]">
                                        <div class="max-h-72 overflow-y-auto">
                                            <template x-for="(option, index) in filteredOptions" :key="option.value">
                                                <button type="button" x-on:click="choose(option)" x-on:mouseenter="activeIndex = index" class="flex w-full items-center justify-between gap-3 border-b border-[rgba(145,133,109,0.08)] px-4 py-3 text-left transition-colors last:border-b-0" x-bind:class="{ 'bg-[#F7F2E8]': activeIndex === index, 'bg-[#EFF4F1]': selectedValue === option.value && activeIndex !== index }">
                                                    <span class="min-w-0">
                                                        <span class="block text-sm font-semibold text-[#17302A]" x-text="option.label"></span>
                                                        <span class="mt-1 block text-xs text-[#8C7B65]" x-show="option.meta" x-text="option.meta"></span>
                                                    </span>
                                                    <span class="material-symbols-outlined text-[1rem] text-[#8C7B65]">location_city</span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="lg:col-span-3">
                            <label class="filter-label" for="billing-postal">Postal Code</label>
                            <input type="text" wire:model.blur="billing_address.postal_code" id="billing-postal" class="input-field">
                        </div>

                        <div class="lg:col-span-8">
                            <label class="filter-label" for="billing-address-line-1">Address Line 1</label>
                            <input type="text" wire:model.blur="billing_address.address_line_1" id="billing-address-line-1" class="input-field">
                        </div>

                        <div class="lg:col-span-4">
                            <label class="filter-label" for="billing-phone">Phone</label>
                            <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
                                <div class="relative" x-data="ninoAddressCombobox({ selectedValue: @entangle('billing_phone_country').live, options: @js($countryPickerOptions), placeholder: 'Search country code...', badgeLabel: 'Country code', emptyLabel: 'No calling codes available.' })" x-on:click.outside="open = false">
                                    <button type="button" id="billing-phone" x-on:click="open = !open; if (open) { query = selectedLabel; syncActiveIndex(); }" class="flex h-12 w-full items-center justify-between rounded-[1rem] border border-[rgba(120,112,95,0.16)] bg-[#FCFBF8] px-3 shadow-[0_10px_24px_-20px_rgba(23,48,42,0.35)] transition-all" x-bind:class="{ 'ring-2 ring-[#245848]/15 border-[#245848]/35 shadow-[0_18px_34px_-24px_rgba(23,48,42,0.45)]': open }">
                                        <span class="flex min-w-0 items-center gap-2">
                                            <span class="text-base" x-text="selectedOption?.flag || '🌍'"></span>
                                            <span class="truncate font-mono text-sm font-semibold text-[#17302A]" x-text="selectedOption?.meta || '+212'"></span>
                                        </span>
                                        <span class="material-symbols-outlined text-[1rem] text-[#8C7B65]">expand_more</span>
                                    </button>
                                    <div x-show="open" x-cloak class="absolute z-30 mt-2 w-full overflow-hidden rounded-[1.1rem] border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] shadow-[0_18px_38px_-22px_rgba(17,30,26,0.22)]">
                                        <div class="border-b border-[rgba(145,133,109,0.12)] p-3">
                                            <input type="text" x-model="query" x-on:input="syncActiveIndex()" x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="chooseActive()" x-on:keydown.escape.prevent="open = false" class="input-field" placeholder="Search country or code..." autocomplete="off">
                                        </div>
                                        <div class="max-h-72 overflow-y-auto">
                                            <template x-for="(option, index) in filteredOptions" :key="option.value">
                                                <button type="button" x-on:click="choose(option)" x-on:mouseenter="activeIndex = index" class="flex w-full items-center justify-between gap-3 border-b border-[rgba(145,133,109,0.08)] px-4 py-3 text-left transition-colors last:border-b-0" x-bind:class="{ 'bg-[#F7F2E8]': activeIndex === index, 'bg-[#EFF4F1]': selectedValue === option.value && activeIndex !== index }">
                                                    <span class="flex min-w-0 items-center gap-2 text-sm font-semibold text-[#17302A]">
                                                        <span x-text="option.flag"></span>
                                                        <span x-text="option.label"></span>
                                                    </span>
                                                    <span class="font-mono text-[11px] text-[#8C7B65]" x-text="option.meta"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <input type="tel" wire:model.live="billing_phone_local" class="input-field" placeholder="612345678">
                                    <p class="mt-2 text-[11px] text-[#617169]">
                                        <span class="font-medium">Stored as:</span>
                                        <span class="font-mono">{{ $billing_address['phone'] ?: 'international number pending' }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-12">
                            <label class="filter-label" for="billing-address-line-2">Address Line 2</label>
                            <input type="text" wire:model.blur="billing_address.address_line_2" id="billing-address-line-2" class="input-field">
                        </div>
                    </div>
                </section>
            @endif

            <div class="xl:hidden">
                @include('themes.nino-v2.livewire.admin.orders.partials.checkout-summary', ['mode' => 'panel'])
            </div>
        </div>

        <aside class="hidden xl:block xl:sticky xl:top-24 xl:self-start">
            <div class="space-y-5">
                @include('themes.nino-v2.livewire.admin.orders.partials.shipping-methods-panel', ['compact' => true])
                @include('themes.nino-v2.livewire.admin.orders.partials.checkout-summary', ['mode' => 'sidebar'])
            </div>
        </aside>
    </form>

    <div class="fixed inset-x-0 bottom-0 z-20 border-t border-[rgba(120,112,95,0.16)] bg-[rgba(252,251,248,0.96)] px-4 py-3 shadow-[0_-18px_34px_-28px_rgba(23,48,42,0.32)] backdrop-blur md:hidden">
        <div class="mx-auto flex max-w-[1480px] items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#8C7B65]">{{ $checkoutStatus['title'] }}</p>
                <p class="mt-1 text-base font-black text-[#17302A]">{{ $checkoutStatus['grand_total_display'] }}</p>
            </div>

            <button
                type="submit"
                form="manual-order-form"
                @disabled(! $canSubmit)
                class="btn-primary shrink-0 justify-center gap-2 px-4 py-3 disabled:cursor-not-allowed disabled:opacity-60"
            >
                Place Order
            </button>
        </div>
    </div>
</div>

<script>
    if (! window.ninoAddressCombobox) {
        window.ninoAddressCombobox = function (config) {
            return {
                selectedValue: config.selectedValue,
                options: config.options ?? [],
                placeholder: config.placeholder ?? 'Search...',
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
