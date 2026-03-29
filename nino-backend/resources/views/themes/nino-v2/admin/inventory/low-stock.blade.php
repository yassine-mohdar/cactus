@extends('admin.layouts.app')

@section('title', 'Low Stock Alerts')

@section('header')
    <x-nino.page-header
        title="Low Stock Alerts"
        subtitle="Review branch-aware alert thresholds, update limits, and prioritize items that are running close to stock exhaustion.">
        <x-slot:actions>
            <x-nino.button href="{{ route('admin.inventory.index') }}" variant="secondary" size="sm">Back to inventory</x-nino.button>
            <x-nino.button href="{{ route('admin.inventory.reports.low-stock') }}" variant="secondary" size="sm">Open low-stock report</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    <div class="filter-toolbar mb-6">
        <form method="GET" class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
            <div class="grid gap-3 sm:grid-cols-2 xl:w-full xl:max-w-5xl xl:grid-cols-3">
                <div class="filter-field xl:col-span-2">
                    <label class="filter-label" for="low-stock-search">Search</label>
                    <input id="low-stock-search" type="text" name="search" value="{{ request('search') }}" placeholder="Product name or SKU" class="input-field">
                </div>

                <div class="filter-field">
                    <label class="filter-label" for="low-stock-branch">Branch</label>
                    <select id="low-stock-branch" name="branch_id" class="input-field">
                        <option value="">All branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <x-admin.button type="submit" variant="primary">Apply Filters</x-admin.button>
                @if(request()->filled('search') || request()->filled('branch_id'))
                    <a href="{{ route('admin.inventory.low-stock') }}" class="btn-secondary">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <div class="datatable-shell">
        <div class="datatable-header">
            <div>
                <h2 class="datatable-title">Alert queue</h2>
                <p class="datatable-subtitle">Items at or below their configured low-stock thresholds, including out-of-stock records and branch context.</p>
            </div>
            <span class="datatable-meta">{{ number_format($items->total()) }} alerts</span>
        </div>

        <x-nino.table>
            <x-slot name="head">
                <th class="text-left">Item</th>
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
                        $available = (int) ($item->available_quantity ?? $item->available_quantity);
                        $threshold = (int) ($item->low_stock_threshold ?? 5);
                    @endphp
                    <tr>
                        <td class="text-left">
                            <div>
                                <p class="text-sm font-bold leading-tight tracking-tight text-[#1E2B27]">{{ $item->product?->name ?? 'Deleted Product' }}</p>
                                <p class="mt-0.5 font-mono text-[10px] tracking-widest text-[#61706B]">{{ $item->variant?->sku ?? $item->product?->sku ?? 'NO-SKU' }}</p>
                            </div>
                        </td>
                        <td class="text-left text-sm text-[#61706B]">{{ $item->branch?->name ?? 'Global' }}</td>
                        <td class="text-right font-mono text-sm font-semibold {{ $available <= 0 ? 'text-[#C45143]' : 'text-[#B97A22]' }}">{{ number_format($available) }}</td>
                        <td class="text-right font-mono text-sm text-[#61706B]">{{ number_format((int) $item->reserved_quantity) }}</td>
                        <td class="text-right">
                            <form method="POST" action="{{ route('admin.inventory.threshold.update', $item) }}" class="inline-flex items-center justify-end gap-2">
                                @csrf
                                <input
                                    type="number"
                                    name="low_stock_threshold"
                                    min="1"
                                    step="1"
                                    value="{{ $threshold }}"
                                    class="w-20 rounded-[0.9rem] border border-[rgba(145,133,109,0.18)] bg-[#FCFBF8] px-3 py-2 text-right font-mono text-sm text-[#17302A] shadow-[0_10px_30px_-24px_rgba(33,53,45,0.35)] focus:border-[#245848]/40 focus:outline-none focus:ring-2 focus:ring-[#DDEAE2]"
                                >
                                <button type="submit" class="table-action-link whitespace-nowrap">Save</button>
                            </form>
                        </td>
                        <td class="text-center">
                            <x-nino.status-badge :tone="$available <= 0 ? 'danger' : 'warning'" size="sm">
                                {{ $available <= 0 ? 'Out of stock' : 'Low stock' }}
                            </x-nino.status-badge>
                        </td>
                        <td class="text-right">
                            <div class="table-actions">
                                <a href="{{ route('admin.inventory.adjustments.create', ['stock_item_id' => $item->id]) }}" class="table-action-link gap-1.5">
                                    <span class="material-symbols-outlined text-[1.2em]">edit_square</span>
                                    <span class="hidden xl:inline">Adjust</span>
                                </a>
                                <a href="{{ route('admin.inventory.reports.adjustments', ['search' => $item->variant?->sku ?? $item->product?->sku]) }}" class="table-action-link gap-1.5">
                                    <span class="material-symbols-outlined text-[1.2em]">history</span>
                                    <span class="hidden xl:inline">History</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="datatable-empty">
                            <x-nino.empty-state
                                title="No low-stock alerts"
                                description="All tracked stock items are above their configured thresholds for the current filters."
                                icon="inventory_2" />
                        </td>
                    </tr>
                @endforelse
            </x-slot>
        </x-nino.table>

        @if($items->hasPages())
            <div class="datatable-footer">
                {{ $items->links() }}
            </div>
        @endif
    </div>
@endsection
