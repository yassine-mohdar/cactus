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
                        <input type="text" wire:model="shipping_address.first_name" 
                               class="w-full px-3 py-2 bg-canvas border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-slate-400 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Last Name</label>
                        <input type="text" wire:model="shipping_address.last_name" 
                               class="w-full px-3 py-2 bg-canvas border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-slate-400 outline-none">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 mb-1">Address Line 1</label>
                        <input type="text" wire:model="shipping_address.address_line_1" 
                               class="w-full px-3 py-2 bg-canvas border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-slate-400 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">City</label>
                        <input type="text" wire:model="shipping_address.city" 
                               class="w-full px-3 py-2 bg-canvas border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-slate-400 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Postal Code</label>
                        <input type="text" wire:model="shipping_address.postal_code" 
                               class="w-full px-3 py-2 bg-canvas border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-slate-400 outline-none">
                    </div>
                </div>

                <div class="mt-6 flex items-center gap-2">
                    <input type="checkbox" id="same_as_shipping" wire:model.live="same_as_shipping" 
                           class="rounded text-slate-800 focus:ring-slate-400 border-slate-200">
                    <label for="same_as_shipping" class="text-sm font-bold text-slate-900">Billing address same as shipping</label>
                </div>
            </div>
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
                    <div class="flex justify-between text-sm pb-3 border-b border-slate-200">
                        <span class="text-slate-500">Shipping Estimate</span>
                        <span class="font-bold">0.00 MAD</span>
                    </div>
                    <div class="flex justify-between text-base pt-2">
                        <span class="font-bold text-slate-900 uppercase tracking-tight">Grand Total</span>
                        <span class="font-black text-slate-800">{{ number_format($this->grand_total, 2) }} MAD</span>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Payment Method</label>
                        <select wire:model="payment_method" class="w-full px-3 py-2 bg-canvas border border-slate-200 rounded-lg text-sm focus:ring-1 focus:ring-slate-400 outline-none">
                            <option value="cod">Cash on Delivery</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="card">Credit Card (External)</option>
                        </select>
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
