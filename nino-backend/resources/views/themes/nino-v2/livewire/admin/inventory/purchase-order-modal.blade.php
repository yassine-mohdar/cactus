<div x-data="{ open: @entangle('show').live }"
     x-show="open"
     x-on:keydown.escape.window="open = false"
     @open-purchase-order-modal.window="open = true"
     class="fixed inset-0 z-[100] overflow-y-auto"
     style="display: none;">

    <div class="flex min-h-screen items-center justify-center px-4 py-6 sm:px-6">
        <div x-show="open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="modal-backdrop"
             aria-hidden="true"
             @click="open = false"></div>

        <div x-show="open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="modal-panel z-10 max-w-4xl overflow-hidden">

            <div class="modal-header">
                <div>
                    <div class="modal-kicker">
                        <span class="material-symbols-outlined text-sm">local_shipping</span>
                        Purchase replenishment
                    </div>
                    <h3 class="modal-title">New Purchase Order</h3>
                    <p class="modal-subtitle">Receive incoming stock with the right supplier, branch destination, and audit notes before inventory is adjusted.</p>
                </div>

                <div class="flex items-start gap-3">
                    <span class="datatable-meta">{{ count($items) }} line{{ count($items) === 1 ? '' : 's' }}</span>
                    <button type="button" @click="open = false" class="icon-button-surface bg-[#FCFBF8]/80">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>
            </div>

            <form wire:submit.prevent="save">
                <div class="modal-body space-y-6">
                    <div class="grid gap-4 lg:grid-cols-3">
                        <x-admin.select wire:model="supplier_id" label="Supplier" :error="$errors->first('supplier_id')">
                            <option value="">Select supplier</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </x-admin.select>

                        <x-admin.select wire:model="branch_id" label="Destination branch" :error="$errors->first('branch_id')">
                            <option value="">Select branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </x-admin.select>

                        <x-admin.input wire:model="reference_number" label="Reference number" placeholder="e.g. PO-12345" class="font-mono" :error="$errors->first('reference_number')" />
                    </div>

                    <section class="space-y-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#6E7D75]">Line items</p>
                                <p class="mt-1 text-sm text-[#5D6F66]">Select the products being received and the quantity for each line.</p>
                            </div>

                            <x-admin.button type="button" variant="secondary" wire:click="addItem" class="gap-2">
                                <span class="material-symbols-outlined text-[18px]">add</span>
                                Add line item
                            </x-admin.button>
                        </div>

                        <div class="space-y-3">
                            @foreach($items as $index => $item)
                                <div wire:key="purchase-order-item-{{ $index }}" class="rounded-[1.4rem] border border-[rgba(145,133,109,0.18)] bg-[#FCFBF8]/80 p-4 shadow-[0_16px_34px_-30px_rgba(23,48,42,0.4)]">
                                    <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_140px_auto]">
                                        <x-admin.select wire:model="items.{{ $index }}.product_id" label="Product" :error="$errors->first('items.'.$index.'.product_id')">
                                            <option value="">Select product</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                                            @endforeach
                                        </x-admin.select>

                                        <x-admin.input type="number" min="1" wire:model="items.{{ $index }}.quantity" label="Quantity" placeholder="Qty" class="font-mono" :error="$errors->first('items.'.$index.'.quantity')" />

                                        <div class="flex items-end">
                                            <button type="button" wire:click="removeItem({{ $index }})" class="inline-flex h-12 w-12 items-center justify-center rounded-[1rem] border border-[rgba(201,75,60,0.18)] bg-[#FCEDEA] text-[#C94B3C] transition hover:bg-[#C94B3C] hover:text-white" aria-label="Remove line item">
                                                <span class="material-symbols-outlined text-[20px]">delete</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <x-admin.textarea wire:model="notes" label="Receiving notes" rows="3" placeholder="Optional audit notes, shipment references, or handling instructions." :error="$errors->first('notes')" />

                    @error('save')
                        <div class="rounded-[1.25rem] border border-[#EFC5BE] bg-[#FCEDEA] px-4 py-3 text-sm text-[#C94B3C]">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="modal-footer">
                    <div class="form-actions">
                        <x-admin.button type="button" variant="secondary" @click="open = false">Cancel</x-admin.button>
                        <x-admin.button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save" class="gap-2">
                            <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                                Process Purchase Order
                            </span>
                            <span wire:loading wire:target="save" class="flex items-center gap-2">
                                <span class="animate-spin material-symbols-outlined text-[18px]">refresh</span>
                                Processing...
                            </span>
                        </x-admin.button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
