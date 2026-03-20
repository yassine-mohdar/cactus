@section('title', 'Create Manual Order')

@section('header')
    <div class="page-header">
        <div>
            <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-1 text-xs font-bold uppercase tracking-[0.18em] text-[#617169] transition-colors hover:text-[#17302A]">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                Back to Orders
            </a>
            <h1 class="page-title mt-3">Create Manual Order</h1>
            <p class="page-subtitle">Build an order for phone, showroom, or support-assisted purchases without leaving the admin workspace.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="datatable-meta">Manual Checkout</span>
            <span class="datatable-meta">Currency: MAD</span>
        </div>
    </div>
@endsection

<div class="mx-auto max-w-[1480px] pb-2">
    <form wire:submit.prevent="save" class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(320px,0.7fr)]">
        <div class="space-y-6">
            <section class="page-section p-6">
                <div class="mb-5">
                    <p class="filter-label">Customer</p>
                    <h2 class="mt-2 text-xl font-bold tracking-tight text-[#17302A]">Customer Information</h2>
                    <p class="mt-1 text-sm text-[#617169]">Search by customer name or email, then prefill delivery details from the selected profile.</p>
                </div>

                <div class="relative">
                    <label class="filter-label" for="manual-order-customer">Search Customer</label>
                    <input
                        id="manual-order-customer"
                        type="text"
                        wire:model.live="search_customer"
                        class="input-field"
                        placeholder="Search by name or email..."
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
                </div>

                @error('customer_id')
                    <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                @enderror
            </section>

            <section class="page-section p-6">
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="filter-label">Order Items</p>
                        <h2 class="mt-2 text-xl font-bold tracking-tight text-[#17302A]">Basket Composition</h2>
                        <p class="mt-1 text-sm text-[#617169]">Add products, adjust quantities, and confirm the captured price before checkout.</p>
                    </div>

                    <x-nino.button type="button" variant="secondary" icon="add" wire:click="addItem">
                        Add Product
                    </x-nino.button>
                </div>

                <div class="space-y-4">
                    @foreach($items as $index => $item)
                        <div class="surface-panel p-4">
                            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_140px_110px_120px_auto]">
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
                                        <input type="number" step="0.01" wire:model="items.{{ $index }}.price" id="order-item-price-{{ $index }}" class="input-field pl-12">
                                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-xs font-semibold uppercase tracking-[0.18em] text-[#617169]">MAD</span>
                                    </div>
                                </div>

                                <div>
                                    <label class="filter-label" for="order-item-quantity-{{ $index }}">Qty</label>
                                    <input type="number" min="1" wire:model.live="items.{{ $index }}.quantity" id="order-item-quantity-{{ $index }}" class="input-field">
                                </div>

                                <div>
                                    <label class="filter-label">Line Total</label>
                                    <div class="flex h-[3rem] items-center justify-end rounded-md border border-[rgba(120,112,95,0.16)] bg-[#F6F2EC] px-4 text-sm font-bold text-[#17302A]">
                                        {{ number_format($item['price'] * $item['quantity'], 2) }} MAD
                                    </div>
                                </div>

                                <div class="flex items-end justify-end">
                                    <button
                                        type="button"
                                        wire:click="removeItem({{ $index }})"
                                        class="inline-flex items-center justify-center rounded-[1rem] border border-[#EFC5BE] bg-[#FCEDEA] px-3 py-3 text-[#C94B3C] transition-colors hover:bg-[#C94B3C] hover:text-white"
                                        aria-label="Remove item {{ $index + 1 }}"
                                    >
                                        <span class="material-symbols-outlined text-[1.15rem]">delete</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 flex items-center justify-between border-t border-[rgba(145,133,109,0.16)] pt-4">
                    <p class="text-sm text-[#617169]">Subtotal updates live as quantities and prices change.</p>
                    <p class="text-base font-bold text-[#17302A]">Subtotal: {{ number_format($this->subtotal, 2) }} MAD</p>
                </div>
            </section>

            <section class="page-section p-6">
                <div class="mb-5">
                    <p class="filter-label">Delivery</p>
                    <h2 class="mt-2 text-xl font-bold tracking-tight text-[#17302A]">Shipping Address</h2>
                    <p class="mt-1 text-sm text-[#617169]">Capture the delivery details exactly as they should appear on fulfillment documents.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="filter-label" for="shipping-first-name">First Name</label>
                        <input type="text" wire:model="shipping_address.first_name" id="shipping-first-name" class="input-field">
                    </div>
                    <div>
                        <label class="filter-label" for="shipping-last-name">Last Name</label>
                        <input type="text" wire:model="shipping_address.last_name" id="shipping-last-name" class="input-field">
                    </div>
                    <div>
                        <label class="filter-label" for="shipping-phone">Phone</label>
                        <input type="text" wire:model="shipping_address.phone" id="shipping-phone" class="input-field">
                    </div>
                    <div>
                        <label class="filter-label" for="shipping-postal">Postal Code</label>
                        <input type="text" wire:model="shipping_address.postal_code" id="shipping-postal" class="input-field">
                    </div>
                    <div class="md:col-span-2">
                        <label class="filter-label" for="shipping-address-line-1">Address Line 1</label>
                        <input type="text" wire:model="shipping_address.address_line_1" id="shipping-address-line-1" class="input-field">
                    </div>
                    <div class="md:col-span-2">
                        <label class="filter-label" for="shipping-address-line-2">Address Line 2</label>
                        <input type="text" wire:model="shipping_address.address_line_2" id="shipping-address-line-2" class="input-field">
                    </div>
                    <div>
                        <label class="filter-label" for="shipping-city">City</label>
                        <input type="text" wire:model="shipping_address.city" id="shipping-city" class="input-field">
                    </div>
                    <div>
                        <label class="filter-label" for="shipping-country">Country</label>
                        <input type="text" wire:model="shipping_address.country" id="shipping-country" class="input-field">
                    </div>
                </div>

                <label for="same_as_shipping" class="toggle-row mt-6">
                    <input type="checkbox" id="same_as_shipping" wire:model.live="same_as_shipping" class="h-4 w-4 rounded border-[rgba(145,133,109,0.28)] text-[#245848] focus:ring-[#245848]/25">
                    Billing address matches shipping address
                </label>
            </section>

            @if(! $same_as_shipping)
                <section class="page-section p-6">
                    <div class="mb-5">
                        <p class="filter-label">Billing</p>
                        <h2 class="mt-2 text-xl font-bold tracking-tight text-[#17302A]">Billing Address</h2>
                        <p class="mt-1 text-sm text-[#617169]">Use a separate billing record when payment details or invoice delivery differs from shipping.</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="filter-label" for="billing-first-name">First Name</label>
                            <input type="text" wire:model="billing_address.first_name" id="billing-first-name" class="input-field">
                        </div>
                        <div>
                            <label class="filter-label" for="billing-last-name">Last Name</label>
                            <input type="text" wire:model="billing_address.last_name" id="billing-last-name" class="input-field">
                        </div>
                        <div>
                            <label class="filter-label" for="billing-phone">Phone</label>
                            <input type="text" wire:model="billing_address.phone" id="billing-phone" class="input-field">
                        </div>
                        <div>
                            <label class="filter-label" for="billing-postal">Postal Code</label>
                            <input type="text" wire:model="billing_address.postal_code" id="billing-postal" class="input-field">
                        </div>
                        <div class="md:col-span-2">
                            <label class="filter-label" for="billing-address-line-1">Address Line 1</label>
                            <input type="text" wire:model="billing_address.address_line_1" id="billing-address-line-1" class="input-field">
                        </div>
                        <div class="md:col-span-2">
                            <label class="filter-label" for="billing-address-line-2">Address Line 2</label>
                            <input type="text" wire:model="billing_address.address_line_2" id="billing-address-line-2" class="input-field">
                        </div>
                        <div>
                            <label class="filter-label" for="billing-city">City</label>
                            <input type="text" wire:model="billing_address.city" id="billing-city" class="input-field">
                        </div>
                        <div>
                            <label class="filter-label" for="billing-country">Country</label>
                            <input type="text" wire:model="billing_address.country" id="billing-country" class="input-field">
                        </div>
                    </div>
                </section>
            @endif
        </div>

        <aside class="space-y-6">
            <section class="page-section sticky top-24 p-6">
                <div class="mb-5">
                    <p class="filter-label">Checkout</p>
                    <h2 class="mt-2 text-xl font-bold tracking-tight text-[#17302A]">Order Summary</h2>
                    <p class="mt-1 text-sm text-[#617169]">Review payment settings, admin notes, and the final captured amount before creating the order.</p>
                </div>

                <div class="surface-panel space-y-3 p-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-[#617169]">Subtotal</span>
                        <span class="font-semibold text-[#17302A]">{{ number_format($this->subtotal, 2) }} MAD</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-[#617169]">Tax</span>
                        <span class="font-semibold text-[#17302A]">0.00 MAD</span>
                    </div>
                    <div class="flex justify-between border-b border-[rgba(145,133,109,0.16)] pb-3 text-sm">
                        <span class="text-[#617169]">Shipping Estimate</span>
                        <span class="font-semibold text-[#17302A]">0.00 MAD</span>
                    </div>
                    <div class="flex justify-between pt-1 text-base">
                        <span class="font-bold uppercase tracking-[0.08em] text-[#17302A]">Grand Total</span>
                        <span class="font-black text-[#17302A]">{{ number_format($this->grand_total, 2) }} MAD</span>
                    </div>
                </div>

                <div class="mt-5 space-y-4">
                    <div>
                        <label class="filter-label" for="payment-method">Payment Method</label>
                        <select wire:model="payment_method" id="payment-method" class="input-field">
                            <option value="cod">Cash on Delivery</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="card">Credit Card (External)</option>
                        </select>
                    </div>

                    <div>
                        <label class="filter-label" for="admin-notes">Internal Admin Notes</label>
                        <textarea wire:model="admin_notes" id="admin-notes" rows="4" class="input-field" placeholder="Notes for the team..."></textarea>
                    </div>

                    @error('save')
                        <x-nino.inline-alert tone="danger" title="Order could not be created">
                            {{ $message }}
                        </x-nino.inline-alert>
                    @enderror

                    <button type="submit" class="btn-primary w-full justify-center gap-2 py-3">
                        <span wire:loading wire:target="save" class="material-symbols-outlined animate-spin text-[1.1rem]">refresh</span>
                        Place Manual Order
                    </button>

                    <p class="text-center text-xs leading-5 text-[#617169]">
                        Creating this order will generate the order record, line items, and associated address snapshots immediately.
                    </p>
                </div>
            </section>
        </aside>
    </form>
</div>
