@extends('admin.layouts.app')

@section('title', 'Report Damaged Stock')

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
        title="Report Damaged Stock"
        subtitle="Remove damaged units from sellable inventory with a dedicated damage workflow and explicit operator attribution.">
        <x-slot:actions>
            <x-nino.button href="{{ route('admin.inventory.damage.index') }}" variant="secondary" size="sm">Damage history</x-nino.button>
            <x-nino.button href="{{ route('admin.inventory.index') }}" variant="secondary" size="sm">Back to inventory</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_22rem]">
        <x-nino.card>
            <x-slot name="header">
                <div>
                    <p class="datatable-meta">Damage intake</p>
                    <h2 class="datatable-title">Record damaged units</h2>
                    <p class="datatable-subtitle">This workflow always posts a stock deduction with the dedicated <span class="font-semibold text-[#17302A]">damage</span> reason.</p>
                </div>
            </x-slot>

            <form method="POST" action="{{ route('admin.inventory.damage.store') }}" class="space-y-6">
                @csrf

                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="filter-field lg:col-span-2">
                        <label class="filter-label" for="damage-stock-item-id">Stock item</label>
                        <select id="damage-stock-item-id" name="stock_item_id" class="input-field" required>
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

                    <div class="filter-field">
                        <label class="filter-label" for="damage-quantity">Damaged units</label>
                        <input id="damage-quantity" type="number" min="1" step="1" name="quantity" value="{{ old('quantity', 1) }}" class="input-field font-mono" required>
                        @error('quantity')
                            <p class="mt-2 text-sm font-medium text-[#C45143]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="rounded-[1.15rem] border border-[rgba(201,75,60,0.16)] bg-[#FFF4F1] px-4 py-3 text-sm text-[#A64539]">
                        <p class="font-semibold uppercase tracking-[0.18em] text-[11px]">Reason</p>
                        <p class="mt-2 font-semibold">Damage / breakage</p>
                        <p class="mt-1 leading-6">This workflow always logs the dedicated damage reason for reporting and audit separation.</p>
                    </div>

                    <div class="filter-field lg:col-span-2">
                        <label class="filter-label" for="damage-notes">Damage notes</label>
                        <textarea id="damage-notes" name="notes" rows="4" class="input-field" placeholder="Describe what was damaged, where it happened, and any receiving or warehouse context.">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="mt-2 text-sm font-medium text-[#C45143]">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[rgba(145,133,109,0.18)] pt-5">
                    <p class="text-sm text-[#61706B]">Recorded by <span class="font-semibold text-[#17302A]">{{ auth()->user()?->name }}</span> and written to both inventory movement history and audit logs.</p>
                    <div class="flex items-center gap-3">
                        <x-nino.button href="{{ route('admin.inventory.index') }}" variant="secondary">Cancel</x-nino.button>
                        <x-nino.button type="submit" variant="primary">Record damage</x-nino.button>
                    </div>
                </div>
            </form>
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
                    <div class="metric-tile">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#6E7D75]">Available units</p>
                        <p class="mt-3 text-3xl font-bold tracking-[-0.04em] text-[#17302A]">{{ number_format($selectedStockItem->available_quantity) }}</p>
                    </div>
                    <div class="rounded-[1.15rem] border border-[rgba(145,133,109,0.16)] bg-[#FCFBF8] p-4">
                        <dl class="grid gap-3 text-sm text-[#61706B]">
                            <div class="flex items-center justify-between gap-4">
                                <dt>Reserved</dt>
                                <dd class="font-mono font-semibold text-[#17302A]">{{ number_format((int) $selectedStockItem->reserved_quantity) }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt>Threshold</dt>
                                <dd class="font-mono font-semibold text-[#17302A]">{{ number_format((int) $selectedStockItem->low_stock_threshold) }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt>Scope</dt>
                                <dd class="font-semibold text-[#17302A]">{{ $selectedStockItem->stockScopeLabel() }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            @else
                <x-nino.empty-state
                    title="Choose a stock item"
                    description="Select the affected product or variant to capture the damaged quantity against the correct inventory record."
                    icon="inventory_2" />
            @endif
        </x-nino.card>
    </div>
@endsection
