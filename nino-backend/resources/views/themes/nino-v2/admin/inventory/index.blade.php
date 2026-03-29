@extends('admin.layouts.app')

@section('title', 'Inventory & Stock')

@section('header')
    <div class="page-header">
        <div>
            <h1 class="page-title">Inventory & Stock</h1>
            <p class="page-subtitle">Search stock by product or SKU, review branch inventory, and jump into quick adjustments.</p>
        </div>

        <div class="flex items-center gap-3">
            @livewire('admin.inventory.sync-button')
            <x-nino.button href="{{ route('admin.inventory.damage.create') }}" variant="secondary" icon="warning">Report Damage</x-nino.button>
            <x-nino.button href="{{ route('admin.inventory.low-stock') }}" variant="secondary" icon="warning">Low Stock Alerts</x-nino.button>
            <x-nino.button href="{{ route('admin.inventory.adjustments.create') }}" variant="secondary" icon="tune">Manual Adjustment</x-nino.button>
            <x-nino.button variant="primary" icon="add" @click="$dispatch('open-purchase-order-modal')">Purchase Order</x-nino.button>
        </div>
    </div>
@endsection

@section('content')
    <div class="mb-6 grid gap-4 md:grid-cols-4">
        <x-nino.detail-section title="Visible Stock" subtitle="Inventory rows in your current operational scope.">
            <p class="font-mono text-2xl font-bold text-[#1E2B27]">{{ number_format($summary['visible_items']) }}</p>
        </x-nino.detail-section>
        <x-nino.detail-section title="Visible Branches" subtitle="Branches available to your current inventory access.">
            <p class="font-mono text-2xl font-bold text-[#1E2B27]">{{ number_format($summary['visible_branches']) }}</p>
        </x-nino.detail-section>
        <x-nino.detail-section title="Reserved Units" subtitle="Units currently held against checkout or payment flow.">
            <p class="font-mono text-2xl font-bold text-[#1E2B27]">{{ number_format($summary['reserved_units']) }}</p>
        </x-nino.detail-section>
        <x-nino.detail-section title="Global Stock" subtitle="Platform-wide stock rows outside branch-specific inventory.">
            <p class="font-mono text-2xl font-bold text-[#1E2B27]">{{ number_format($summary['global_items']) }}</p>
        </x-nino.detail-section>
    </div>

    <div class="filter-toolbar mb-6">
        <form method="GET" class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 xl:w-full xl:max-w-5xl">
                <div class="filter-field xl:col-span-1">
                    <label class="filter-label" for="inventory-search">Search</label>
                    <input id="inventory-search" type="text" name="search" value="{{ request('search') }}" placeholder="Product name or SKU" class="input-field">
                </div>

                <div class="filter-field">
                    <label class="filter-label" for="inventory-branch">Branch</label>
                    <select id="inventory-branch" name="branch_id" class="input-field">
                        <option value="">All branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-field">
                    <label class="filter-label" for="inventory-filter">Stock State</label>
                    <select id="inventory-filter" name="filter" class="input-field">
                        <option value="">All inventory</option>
                        <option value="low_stock" @selected(request('filter') === 'low_stock')>Low stock only</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <x-admin.button type="submit" variant="primary">Apply Filters</x-admin.button>
                @if(request()->filled('search') || request()->filled('branch_id') || request()->filled('filter'))
                    <a href="{{ route('admin.inventory.index') }}" class="btn-secondary">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <x-nino.card class="overflow-hidden">
        <x-slot name="header">
            <span class="datatable-meta">{{ number_format($items->total()) }} stock items</span>
        </x-slot>
        <x-nino.table>
            <x-slot name="head">
                <th class="text-left">Product</th>
                <th class="text-left">Branch</th>
                <th class="text-right">Available</th>
                <th class="text-right">Reserved</th>
                <th class="text-right">Threshold</th>
                <th class="text-center">Status</th>
                <th class="text-right">Actions</th>
            </x-slot>

            <x-slot name="body">
                @forelse($items as $item)
                    @php
                        $effectiveThreshold = max(1, (int) ($item->low_stock_threshold ?? 5));
                    @endphp
                    <tr>
                        <td class="text-left">
                            <div class="flex items-center gap-4">
                                <div class="flex h-10 w-10 items-center justify-center rounded-md border border-[rgba(120,112,95,0.16)] bg-[#FBFAF7] text-[#7A8681]">
                                    <span class="material-symbols-outlined text-[1.2rem]">inventory_2</span>
                                </div>
                                <div>
                                    <p class="text-sm font-bold leading-tight tracking-tight text-[#1E2B27]">{{ $item->product->name ?? 'Deleted Product' }}</p>
                                    <p class="mt-0.5 font-mono text-[10px] tracking-widest text-[#61706B]">{{ $item->sku ?? 'NO-SKU' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="text-left text-sm font-medium text-[#61706B]">
                            <div class="flex flex-col">
                                <span>{{ $item->branch->name ?? 'Global' }}</span>
                                <span class="mt-0.5 font-mono text-[10px] uppercase tracking-[0.18em] text-[#7A8681]">{{ $item->stockScopeLabel() }}</span>
                            </div>
                        </td>
                        <td class="text-right text-sm font-mono font-bold text-[#1E2B27]">
                            {{ $item->available_quantity }}
                        </td>
                        <td class="text-right text-sm font-mono text-[#61706B]">
                            {{ $item->reserved_quantity }}
                        </td>
                        <td class="text-right text-sm font-mono text-[#61706B]">
                            {{ $item->low_stock_threshold }}
                        </td>
                        <td class="text-center text-xs">
                            @if($item->available_quantity <= 0)
                                <x-nino.status-badge tone="danger" size="sm">Out of Stock</x-nino.status-badge>
                            @elseif($item->available_quantity <= $effectiveThreshold)
                                <x-nino.status-badge tone="danger" size="sm">Low Stock</x-nino.status-badge>
                            @else
                                <x-nino.status-badge tone="success" size="sm">In Stock</x-nino.status-badge>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="table-actions">
                                <a href="{{ route('admin.inventory.adjustments.create', ['stock_item_id' => $item->id]) }}" class="table-action-link gap-1.5" aria-label="Manual adjust {{ $item->product->name ?? 'inventory item' }}">
                                    <span class="material-symbols-outlined text-[1.2em]">edit_square</span>
                                    <span class="hidden xl:inline">Adjust</span>
                                </a>
                                <a href="{{ route('admin.inventory.damage.create', ['stock_item_id' => $item->id]) }}" class="table-action-link gap-1.5" aria-label="Report damage for {{ $item->product->name ?? 'inventory item' }}">
                                    <span class="material-symbols-outlined text-[1.2em]">warning</span>
                                    <span class="hidden xl:inline">Damage</span>
                                </a>
                                <button type="button" @click="$dispatch('openQuickEdit', { id: {{ $item->id }} })" class="table-action-link gap-1.5" aria-label="Quick adjust {{ $item->product->name ?? 'inventory item' }}">
                                    <span class="material-symbols-outlined text-[1.2em]">bolt</span>
                                    <span class="hidden xl:inline">Quick</span>
                                </button>
                                <a href="{{ route('admin.inventory.reports.adjustments', ['search' => $item->sku]) }}" class="table-action-link gap-1.5" aria-label="View stock history for {{ $item->sku ?? 'inventory item' }}">
                                    <span class="material-symbols-outlined text-[1.2em]">history</span>
                                    <span class="hidden xl:inline">History</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="datatable-empty">
                            <div class="datatable-empty-panel">
                                <p class="text-sm font-semibold text-[#1E2B27]">No products in inventory.</p>
                                <p class="text-sm text-[#61706B]">Start by adding products to your catalog or creating a purchase order.</p>
                                <div class="mt-2 inline-block">
                                    <x-nino.button variant="primary" icon="add" @click="$dispatch('open-purchase-order-modal')">Create Purchase Order</x-nino.button>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </x-slot>
        </x-nino.table>

        <div class="datatable-footer">
            {{ $items->links() }}
        </div>
    </x-nino.card>

    {{-- The Fixed Livewire Components --}}
    @livewire('admin.inventory.quick-edit')
    @livewire('admin.inventory.purchase-order-modal')

@endsection
