<div id="QUICK_EDIT_ROOT_DIV">
    <div x-data="{ open: false }"
         x-on:show-quick-edit-modal.window="open = true"
         x-on:hide-quick-edit-modal.window="open = false"
         x-on:keydown.escape.window="open = false">

        <div x-show="open" class="relative z-50">
            <div x-show="open" x-transition.opacity class="modal-backdrop" x-on:click="open = false"></div>

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
                        
                        <div class="modal-slideover">
                            <form wire:submit.prevent="save" class="flex h-full flex-col">
                                <div class="modal-header">
                                    <div>
                                        <div class="modal-kicker">
                                            <span class="material-symbols-outlined text-sm">tune</span>
                                            Inventory adjustment
                                        </div>
                                        <h2 class="modal-title">Quick Edit Stock</h2>
                                        @if($stockItem)
                                            <p class="modal-subtitle">{{ $stockItem->product->name ?? 'Product' }}</p>
                                        @else
                                            <p class="modal-subtitle">Choose the correct adjustment path, quantity, and audit reason.</p>
                                        @endif
                                    </div>

                                    <button type="button" x-on:click="open = false" class="icon-button-surface bg-[#FCFBF8]/80">
                                        <span class="material-symbols-outlined text-[20px]">close</span>
                                    </button>
                                </div>

                                <div class="modal-body flex-1 space-y-6 overflow-y-auto">
                                    @if(session()->has('error'))
                                        <div class="rounded-[1.25rem] border border-[#EFC5BE] bg-[#FCEDEA] px-4 py-3 text-sm font-semibold text-[#C94B3C]">
                                            {{ session('error') }}
                                        </div>
                                    @endif

                                    @if($stockItem)
                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <div class="metric-tile">
                                                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#6E7D75]">Available</p>
                                                <p class="mt-3 text-3xl font-bold tracking-[-0.04em] text-[#17302A]">{{ $stockItem->available_quantity }}</p>
                                            </div>
                                            <div class="metric-tile">
                                                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#6E7D75]">Reserved</p>
                                                <p class="mt-3 text-3xl font-bold tracking-[-0.04em] text-[#B97A22]">{{ $stockItem->reserved_quantity }}</p>
                                            </div>
                                        </div>

                                        <div class="space-y-4">
                                            <div>
                                                <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.24em] text-[#6E7D75]">Adjustment mode</label>
                                                <div class="grid grid-cols-3 gap-2 rounded-[1.25rem] border border-[rgba(145,133,109,0.18)] bg-[#F7F2E8]/90 p-2">
                                                    <label class="cursor-pointer">
                                                        <input type="radio" wire:model="adjustment_type" value="add" class="peer sr-only">
                                                        <div class="rounded-[1rem] border border-transparent px-3 py-3 text-center text-xs font-semibold uppercase tracking-[0.18em] text-[#6E7D75] transition peer-checked:border-[#245848]/28 peer-checked:bg-[#FCFBF8] peer-checked:text-[#17302A]">Add stock</div>
                                                    </label>
                                                    <label class="cursor-pointer">
                                                        <input type="radio" wire:model="adjustment_type" value="subtract" class="peer sr-only">
                                                        <div class="rounded-[1rem] border border-transparent px-3 py-3 text-center text-xs font-semibold uppercase tracking-[0.18em] text-[#6E7D75] transition peer-checked:border-[#C94B3C]/25 peer-checked:bg-[#FCFBF8] peer-checked:text-[#C94B3C]">Remove stock</div>
                                                    </label>
                                                    <label class="cursor-pointer">
                                                        <input type="radio" wire:model="adjustment_type" value="set" class="peer sr-only">
                                                        <div class="rounded-[1rem] border border-transparent px-3 py-3 text-center text-xs font-semibold uppercase tracking-[0.18em] text-[#6E7D75] transition peer-checked:border-[#245848]/28 peer-checked:bg-[#FCFBF8] peer-checked:text-[#17302A]">Set absolute</div>
                                                    </label>
                                                </div>
                                            </div>

                                            <x-admin.input type="number" wire:model="quantity" min="0" label="Quantity" class="text-center font-mono text-2xl" :error="$errors->first('quantity')" required />

                                            <x-admin.select wire:model="reason" label="Reason" :error="$errors->first('reason')" required>
                                                <option value="restock">Restock / Received</option>
                                                <option value="manual_adjustment">Manual Inventory Count</option>
                                                <option value="sale">Direct Sale</option>
                                                <option value="damage">Damage / Breakage</option>
                                                <option value="shrinkage">Shrinkage / Loss</option>
                                                <option value="return">Customer Return</option>
                                            </x-admin.select>

                                            <x-admin.textarea wire:model="notes" label="Audit notes" rows="3" placeholder="Optional context for the stock history timeline." :error="$errors->first('notes')" />
                                        </div>
                                    @endif
                                </div>

                                <div class="modal-footer">
                                    <div class="form-actions">
                                        <x-admin.button type="button" variant="secondary" x-on:click="open = false">Cancel</x-admin.button>
                                        <x-admin.button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save" class="gap-2">
                                            <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                                                <span class="material-symbols-outlined text-[18px]">save</span>
                                                Confirm Update
                                            </span>
                                            <span wire:loading wire:target="save" class="flex items-center gap-2">
                                                <span class="animate-spin material-symbols-outlined text-[18px]">refresh</span>
                                                Saving...
                                            </span>
                                        </x-admin.button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
