<div id="QUICK_EDIT_ROOT_DIV">
    {{-- Using x-on: instead of @ to avoid Blade directive conflicts (like @show) --}}
    <div x-data="{ open: false }" 
         x-on:show-quick-edit-modal.window="open = true"
         x-on:hide-quick-edit-modal.window="open = false">
         
        <div x-show="open" class="relative z-50">
            {{-- Backdrop --}}
            <div x-show="open" x-transition.opacity class="fixed inset-0 bg-slate-900/60 transition-opacity"></div>
            
            {{-- Slide-over panel --}}
            <div class="fixed inset-0 overflow-hidden">
                <div class="absolute inset-0 overflow-hidden">
                    <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10"
                         x-show="open"
                         x-transition:enter="transform transition ease-out duration-300"
                         x-transition:enter-start="translate-x-full"
                         x-transition:enter-end="translate-x-0"
                         x-transition:leave="transform transition ease-in duration-200"
                         x-transition:leave-start="translate-x-0"
                         x-transition:leave-end="translate-x-full">
                        
                        <div class="pointer-events-auto w-screen max-w-md">
                            <form wire:submit.prevent="save" class="flex h-full flex-col bg-surface border-l border-slate-300 shadow-xl">
                                
                                <!-- Header -->
                                <div class="px-6 py-5 border-b border-slate-300 bg-slate-50 flex items-center justify-between">
                                    <div>
                                        <h2 class="text-lg font-bold text-slate-900">Quick Edit Stock</h2>
                                        @if($stockItem)
                                            <p class="text-[10px] text-slate-500 uppercase mt-1 font-mono tracking-widest">{{ $stockItem->product->name ?? 'Product' }}</p>
                                        @endif
                                    </div>
                                    <button type="button" x-on:click="open = false" class="rounded p-2 text-slate-400 hover:text-slate-900 hover:bg-slate-200 transition-colors">
                                        <span class="material-symbols-outlined text-[1.2rem]">close</span>
                                    </button>
                                </div>
                                
                                {{-- Content --}}
                                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
                                    @if(session()->has('error'))
                                        <div class="p-3 rounded border border-red-500/20 bg-red-500/10 text-red-600 text-xs font-bold">{{ session('error') }}</div>
                                    @endif

                                    @if($stockItem)
                                        <div class="grid grid-cols-2 gap-4 p-4 rounded-lg bg-slate-50 border border-slate-300">
                                            <div>
                                                <p class="text-[10px] uppercase font-bold text-slate-500 tracking-widest">Available</p>
                                                <p class="text-xl font-mono font-bold text-slate-900">{{ $stockItem->available_quantity }}</p>
                                            </div>
                                            <div>
                                                <p class="text-[10px] uppercase font-bold text-slate-500 tracking-widest">Reserved</p>
                                                <p class="text-xl font-mono font-bold text-amber-600">{{ $stockItem->reserved_quantity }}</p>
                                            </div>
                                        </div>

                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Adjustment</label>
                                                <div class="bg-slate-100 p-1 rounded-md flex list-none">
                                                    <label class="flex-1 text-center cursor-pointer">
                                                        <input type="radio" wire:model="adjustment_type" value="add" class="peer sr-only">
                                                        <div class="py-2 text-xs font-bold rounded-sm text-slate-500 peer-checked:bg-white peer-checked:text-slate-900 transition-colors border border-transparent peer-checked:border-slate-300">Add Stock</div>
                                                    </label>
                                                    <label class="flex-1 text-center cursor-pointer mx-1">
                                                        <input type="radio" wire:model="adjustment_type" value="subtract" class="peer sr-only">
                                                        <div class="py-2 text-xs font-bold rounded-sm text-slate-500 peer-checked:bg-white peer-checked:text-red-600 transition-colors border border-transparent peer-checked:border-slate-300">Remove Stock</div>
                                                    </label>
                                                    <label class="flex-1 text-center cursor-pointer">
                                                        <input type="radio" wire:model="adjustment_type" value="set" class="peer sr-only">
                                                        <div class="py-2 text-xs font-bold rounded-sm text-slate-500 peer-checked:bg-white peer-checked:text-slate-900 transition-colors border border-transparent peer-checked:border-slate-300">Set Absolute</div>
                                                    </label>
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Quantity</label>
                                                <x-admin.input type="number" wire:model="quantity" min="0" required class="font-mono text-xl text-center" />
                                            </div>

                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Reason</label>
                                                <x-admin.select wire:model="reason" required>
                                                    <option value="restock">Restock / Received</option>
                                                    <option value="manual_adjustment">Manual Inventory Count</option>
                                                    <option value="sale">Direct Sale</option>
                                                    <option value="damage">Damage / Breakage</option>
                                                    <option value="shrinkage">Shrinkage / Loss</option>
                                                    <option value="return">Customer Return</option>
                                                </x-admin.select>
                                            </div>

                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Notes</label>
                                                <x-admin.textarea wire:model="notes" rows="2" placeholder="Optional audit notes..."></x-admin.textarea>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                
                                <!-- Footer -->
                                <div class="px-6 py-4 border-t border-slate-300 bg-surface flex justify-end gap-3">
                                    <x-nino.button type="button" variant="secondary" x-on:click="open = false">Cancel</x-nino.button>
                                    <x-nino.button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                                        <span wire:loading.remove wire:target="save" class="flex items-center">
                                            <span class="material-symbols-outlined mr-1.5 text-[1.1em]">save</span>
                                            Confirm Update
                                        </span>
                                        <span wire:loading wire:target="save" class="flex items-center">
                                            <span class="animate-spin material-symbols-outlined mr-1.5 text-[1.1em]">refresh</span>
                                            Saving...
                                        </span>
                                    </x-nino.button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
