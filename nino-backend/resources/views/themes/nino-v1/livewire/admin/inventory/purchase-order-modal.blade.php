<div x-data="{ open: @entangle('show').live }" 
     x-show="open" 
     @open-purchase-order-modal.window="open = true" 
     class="fixed inset-0 z-[100] overflow-y-auto" 
     style="display: none;">
    
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Backdrop -->
        <div x-show="open" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0" 
             x-transition:enter-end="opacity-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100" 
             x-transition:leave-end="opacity-0" 
             class="fixed inset-0 transition-opacity bg-slate-900/60" 
             aria-hidden="true" 
             @click="open = false"></div>

        <!-- This element is to trick the browser into centering the modal contents. -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <!-- Modal panel -->
        <div x-show="open" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             class="inline-block relative px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-surface rounded-lg sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6 border border-slate-300">
            
            <div class="sm:flex sm:items-start">
                <div class="w-full text-center sm:text-left">
                    <h3 class="text-lg font-bold leading-6 text-slate-900 mb-4">New Purchase Order</h3>
                    
                    <form wire:submit.prevent="save" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Supplier</label>
                                <select wire:model="supplier_id" class="w-full px-3 py-2 bg-surface border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-slate-900 focus:border-slate-900 outline-none">
                                    <option value="">Select Supplier</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                                @error('supplier_id') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Branch</label>
                                <select wire:model="branch_id" class="w-full px-3 py-2 bg-surface border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-slate-900 focus:border-slate-900 outline-none">
                                    <option value="">Select Branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                                @error('branch_id') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="space-y-2">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">Items</label>
                            @foreach($items as $index => $item)
                                <div class="flex gap-2 items-start">
                                    <div class="flex-1">
                                        <select wire:model="items.{{ $index }}.product_id" class="w-full px-3 py-2 bg-surface border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-slate-900 focus:border-slate-900 outline-none">
                                            <option value="">Select Product</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('items.'.$index.'.product_id') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="w-24">
                                        <input type="number" wire:model="items.{{ $index }}.quantity" class="w-full px-3 py-2 bg-surface border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-slate-900 focus:border-slate-900 outline-none" placeholder="Qty">
                                        @error('items.'.$index.'.quantity') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                    </div>
                                    <button type="button" wire:click="removeItem({{ $index }})" class="p-2 text-slate-400 hover:text-red-600 transition-colors">
                                        <span class="material-symbols-outlined text-base">delete</span>
                                    </button>
                                </div>
                            @endforeach
                            <button type="button" wire:click="addItem" class="text-xs font-bold text-slate-800 hover:text-slate-800/80 flex items-center gap-1 mt-2">
                                <span class="material-symbols-outlined text-sm">add</span> Add Another Item
                            </button>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Reference #</label>
                                <input type="text" wire:model="reference_number" class="font-mono w-full px-3 py-2 bg-surface border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-slate-900 focus:border-slate-900 outline-none" placeholder="e.g. PO-12345">
                                @error('reference_number') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Notes</label>
                            <textarea wire:model="notes" rows="2" class="w-full px-3 py-2 bg-surface border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-slate-900 focus:border-slate-900 outline-none" placeholder="Optional notes..."></textarea>
                            @error('notes') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        @error('save')
                            <div class="p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-xs text-red-600">
                                {{ $message }}
                            </div>
                        @enderror

                        <div class="flex justify-end gap-3 pt-4">
                            <x-nino.button type="button" @click="open = false" variant="secondary">Cancel</x-nino.button>
                            <x-nino.button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                                <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                                    Process Purchase Order
                                </span>
                                <span wire:loading wire:target="save" class="flex items-center gap-2">
                                    <span class="animate-spin material-symbols-outlined text-[1.1em]">refresh</span>
                                    Process Purchase Order
                                </span>
                            </x-nino.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
