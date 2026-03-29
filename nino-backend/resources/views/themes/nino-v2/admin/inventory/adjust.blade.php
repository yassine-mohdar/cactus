@extends('admin.layouts.app')

@section('title', 'Manual Inventory Adjustment')

@php
    $selectedLabel = $selectedStockItem
        ? trim(collect([
            $selectedStockItem->product?->name,
            $selectedStockItem->variant?->sku,
        ])->filter()->join(' / '))
        : null;
@endphp

@section('header')
    <x-nino.page-header
        title="Manual Inventory Adjustment"
        subtitle="Apply audited stock increases or deductions with an explicit reason and operator attribution.">
        <x-slot:actions>
            <x-nino.button href="{{ route('admin.inventory.index') }}" variant="secondary" size="sm">Back to inventory</x-nino.button>
            <x-nino.button href="{{ route('admin.inventory.reports.adjustments') }}" variant="secondary" size="sm">Open adjustment history</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    @if(session('error'))
        <x-nino.inline-alert tone="danger" title="Adjustment failed" class="mb-6">
            {{ session('error') }}
        </x-nino.inline-alert>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_22rem]">
        <x-nino.card>
            <x-slot name="header">
                <div>
                    <p class="datatable-meta">Adjustment form</p>
                    <h2 class="datatable-title">Record stock movement</h2>
                    <p class="datatable-subtitle">Choose the stock item, direction, quantity, and inventory reason before posting the movement.</p>
                </div>
            </x-slot>

            <form method="POST" action="{{ route('admin.inventory.adjustments.store') }}" class="space-y-6">
                @csrf

                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="filter-field lg:col-span-2">
                        <label class="filter-label" for="stock-item-id">Stock item</label>
                        <select id="stock-item-id" name="stock_item_id" class="input-field" required>
                            <option value="">Select a stock item</option>
                            @foreach($stockItems as $stockItem)
                                @php
                                    $optionLabel = trim(collect([
                                        $stockItem->product?->name,
                                        $stockItem->variant?->sku,
                                        $stockItem->branch?->name ? '@ '.$stockItem->branch->name : 'Global',
                                    ])->filter()->join(' / '));
                                @endphp
                                <option value="{{ $stockItem->id }}" @selected((string) old('stock_item_id', $selectedStockItem?->id) === (string) $stockItem->id)>
                                    {{ $optionLabel }}
                                </option>
                            @endforeach
                        </select>
                        @error('stock_item_id')
                            <p class="mt-2 text-sm font-medium text-[#C45143]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.24em] text-[#6E7D75]">Adjustment mode</label>
                        <div class="grid grid-cols-2 gap-2 rounded-[1.25rem] border border-[rgba(145,133,109,0.18)] bg-[#F7F2E8]/90 p-2">
                            <label class="cursor-pointer">
                                <input type="radio" name="adjustment_type" value="add" class="peer sr-only" @checked(old('adjustment_type', 'add') === 'add')>
                                <div class="rounded-[1rem] border border-transparent px-3 py-3 text-center text-xs font-semibold uppercase tracking-[0.18em] text-[#6E7D75] transition peer-checked:border-[#245848]/28 peer-checked:bg-[#FCFBF8] peer-checked:text-[#17302A]">Add stock</div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="adjustment_type" value="subtract" class="peer sr-only" @checked(old('adjustment_type') === 'subtract')>
                                <div class="rounded-[1rem] border border-transparent px-3 py-3 text-center text-xs font-semibold uppercase tracking-[0.18em] text-[#6E7D75] transition peer-checked:border-[#C94B3C]/25 peer-checked:bg-[#FCFBF8] peer-checked:text-[#C94B3C]">Remove stock</div>
                            </label>
                        </div>
                        @error('adjustment_type')
                            <p class="mt-2 text-sm font-medium text-[#C45143]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="filter-field">
                        <label class="filter-label" for="adjustment-quantity">Quantity</label>
                        <input id="adjustment-quantity" type="number" min="1" step="1" name="quantity" value="{{ old('quantity', 1) }}" class="input-field font-mono" required>
                        @error('quantity')
                            <p class="mt-2 text-sm font-medium text-[#C45143]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="filter-field">
                        <label class="filter-label" for="adjustment-reason">Reason</label>
                        <select id="adjustment-reason" name="reason" class="input-field" required>
                            @foreach($adjustmentReasons as $reasonValue => $reasonLabel)
                                <option value="{{ $reasonValue }}" @selected(old('reason', 'manual_adjustment') === $reasonValue)>{{ $reasonLabel }}</option>
                            @endforeach
                        </select>
                        @error('reason')
                            <p class="mt-2 text-sm font-medium text-[#C45143]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="filter-field lg:col-span-2">
                        <label class="filter-label" for="adjustment-notes">Audit notes</label>
                        <textarea id="adjustment-notes" name="notes" rows="4" class="input-field" placeholder="Optional context for the movement log, count discrepancy, or receiving note.">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="mt-2 text-sm font-medium text-[#C45143]">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[rgba(145,133,109,0.18)] pt-5">
                    <p class="text-sm text-[#61706B]">The operator on this adjustment will be recorded automatically as <span class="font-semibold text-[#17302A]">{{ auth()->user()?->name }}</span>.</p>
                    <div class="flex items-center gap-3">
                        <x-nino.button href="{{ route('admin.inventory.index') }}" variant="secondary">Cancel</x-nino.button>
                        <x-nino.button type="submit" variant="primary">Record adjustment</x-nino.button>
                    </div>
                </div>
            </form>
        </x-nino.card>

        <div class="space-y-6">
            <x-nino.card>
                <x-slot name="header">
                    <div>
                        <p class="datatable-meta">Operator</p>
                        <h2 class="datatable-title">Staff attribution</h2>
                    </div>
                </x-slot>

                <div class="space-y-3">
                    <div class="metric-tile">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#6E7D75]">Current operator</p>
                        <p class="mt-3 text-lg font-bold tracking-[-0.03em] text-[#17302A]">{{ auth()->user()?->name }}</p>
                        <p class="mt-1 font-mono text-[11px] tracking-[0.16em] text-[#7A8681]">{{ auth()->user()?->email }}</p>
                    </div>
                    <p class="text-sm leading-6 text-[#61706B]">Every manual stock movement records the signed-in staff member for auditability and adjustment history review.</p>
                </div>
            </x-nino.card>

            <x-nino.card>
                <x-slot name="header">
                    <div>
                        <p class="datatable-meta">Selected stock item</p>
                        <h2 class="datatable-title">{{ $selectedLabel ?: 'No item preselected' }}</h2>
                    </div>
                </x-slot>

                @if($selectedStockItem)
                    <div class="space-y-4">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="metric-tile">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#6E7D75]">Physical</p>
                                <p class="mt-3 text-3xl font-bold tracking-[-0.04em] text-[#17302A]">{{ number_format($selectedStockItem->quantity) }}</p>
                            </div>
                            <div class="metric-tile">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#6E7D75]">Reserved</p>
                                <p class="mt-3 text-3xl font-bold tracking-[-0.04em] text-[#B97A22]">{{ number_format($selectedStockItem->reserved_quantity) }}</p>
                            </div>
                        </div>

                        <div class="rounded-[1.15rem] border border-[rgba(145,133,109,0.16)] bg-[#FCFBF8] p-4">
                            <dl class="grid gap-3 text-sm text-[#61706B]">
                                <div class="flex items-center justify-between gap-4">
                                    <dt>Available</dt>
                                    <dd class="font-mono font-semibold text-[#17302A]">{{ number_format($selectedStockItem->available_quantity) }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt>Scope</dt>
                                    <dd class="font-semibold text-[#17302A]">{{ $selectedStockItem->stockScopeLabel() }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt>Level</dt>
                                    <dd class="font-semibold text-[#17302A]">{{ $selectedStockItem->stockLevelLabel() }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt>Status</dt>
                                    <dd><x-nino.status-badge :tone="$selectedStockItem->isOutOfStock() ? 'danger' : ($selectedStockItem->isLowStock() ? 'warning' : 'success')" size="sm">{{ str_replace('_', ' ', $selectedStockItem->status) }}</x-nino.status-badge></dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                @else
                    <x-nino.empty-state
                        title="Choose a stock item"
                        description="Pick a product or variant from the adjustment form to review the current stock context before posting the movement."
                        icon="inventory_2" />
                @endif
            </x-nino.card>
        </div>
    </div>
@endsection
