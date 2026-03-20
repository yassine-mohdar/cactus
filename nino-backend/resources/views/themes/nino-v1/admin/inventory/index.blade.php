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
            <x-nino.button variant="primary" icon="add" @click="$dispatch('open-purchase-order-modal')">Purchase Order</x-nino.button>
        </div>
    </div>
@endsection

@section('content')
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
                <th class="text-center">Status</th>
                <th class="text-right">Actions</th>
            </x-slot>

            <x-slot name="body">
                @forelse($items as $item)
                    <tr>
                        <td class="text-left">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded shadow-sm border border-slate-300 bg-surface flex items-center justify-center text-slate-400">
                                    <span class="material-symbols-outlined text-[1.2rem]">inventory_2</span>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-900 leading-tight tracking-tight">{{ $item->product->name ?? 'Deleted Product' }}</p>
                                    <p class="text-[10px] text-slate-500 font-mono tracking-widest mt-0.5">{{ $item->sku ?? 'NO-SKU' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="text-left text-sm font-medium text-slate-600">
                            {{ $item->branch->name ?? 'Default' }}
                        </td>
                        <td class="text-right text-sm font-mono font-bold text-slate-900">
                            {{ $item->available_quantity }}
                        </td>
                        <td class="text-right text-sm font-mono text-slate-500">
                            {{ $item->reserved_quantity }}
                        </td>
                        <td class="text-center text-xs">
                            @if($item->available_quantity <= 5)
                                <span class="px-3 py-1 rounded-lg bg-red-50 text-red-600 text-[10px] font-bold uppercase tracking-widest border border-red-100 shadow-sm">Low Stock</span>
                            @else
                                <span class="px-3 py-1 rounded-lg bg-emerald-50 text-emerald-600 text-[10px] font-bold uppercase tracking-widest border border-emerald-100 shadow-sm">In Stock</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="table-actions">
                                <button type="button" @click="$dispatch('openQuickEdit', { id: {{ $item->id }} })" class="table-action-link" title="Quick Adjust">
                                    <span class="material-symbols-outlined text-[1.2em]">edit_square</span>
                                </button>
                                <button class="table-action-link" title="Stock History">
                                    <span class="material-symbols-outlined text-[1.2em]">history</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="datatable-empty">
                            <div class="datatable-empty-panel">
                                <p class="text-sm font-semibold text-slate-900">No products in inventory.</p>
                                <p class="text-sm text-slate-500">Start by adding products to your catalog or creating a purchase order.</p>
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
