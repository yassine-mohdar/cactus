@extends('admin.layouts.app')

@section('title', 'Current Stock Report')
@section('header')
    <x-nino.page-header
        title="Current Stock Report"
        subtitle="Exportable view of stock quantity, reserved units, and estimated value by branch.">
        <x-slot:actions>
            <x-admin.button href="{{ route('admin.inventory.index') }}" variant="outline" class="flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">arrow_back</span> Back to Inventory
            </x-admin.button>
            <x-admin.button href="{{ route('admin.inventory.reports.low-stock') }}" variant="outline" class="flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">warning</span> Low Stock Report
            </x-admin.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    <div class="mb-6 grid gap-4 md:grid-cols-4">
        <x-nino.detail-section title="Stock Rows" subtitle="Inventory records in your current scope.">
            <p class="font-mono text-2xl font-bold text-[#1E2B27]">{{ number_format($summary['rows']) }}</p>
        </x-nino.detail-section>
        <x-nino.detail-section title="Physical Units" subtitle="Total on-hand quantity before reservations.">
            <p class="font-mono text-2xl font-bold text-[#1E2B27]">{{ number_format($summary['physical_units']) }}</p>
        </x-nino.detail-section>
        <x-nino.detail-section title="Reserved Units" subtitle="Units currently held for checkout or payment flow.">
            <p class="font-mono text-2xl font-bold text-[#1E2B27]">{{ number_format($summary['reserved_units']) }}</p>
        </x-nino.detail-section>
        <x-nino.detail-section title="Branch Rows" subtitle="Branch-owned stock lines in the report scope.">
            <p class="font-mono text-2xl font-bold text-[#1E2B27]">{{ number_format($summary['branch_rows']) }}</p>
        </x-nino.detail-section>
    </div>

    <div class="filter-toolbar mb-6">
        <form method="GET" class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
            <div class="grid gap-3 sm:grid-cols-2 xl:w-full xl:max-w-5xl xl:grid-cols-3">
                <div class="filter-field xl:col-span-2">
                    <label class="filter-label" for="stock-report-search">Search</label>
                    <input id="stock-report-search" type="text" name="search" value="{{ request('search') }}" placeholder="Product name or SKU" class="input-field">
                </div>
                <div class="filter-field">
                    <label class="filter-label" for="stock-report-branch">Branch</label>
                    <select id="stock-report-branch" name="branch_id" class="input-field">
                        <option value="">All visible branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <x-admin.button type="submit" variant="primary">Apply Filters</x-admin.button>
                @if(request()->filled('search') || request()->filled('branch_id'))
                    <a href="{{ route('admin.inventory.reports.stock') }}" class="btn-secondary">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <div class="datatable-shell">
        <div class="datatable-header">
            <div>
                <h2 class="datatable-title">Stock Valuation Overview</h2>
                <p class="datatable-subtitle">Physical and reserved quantities alongside the current estimated unit price.</p>
            </div>
            <span class="datatable-meta">{{ number_format($items->total()) }} stock items</span>
        </div>
        
        <x-nino.table>
            <x-slot name="head">
                <th class="text-left">Item</th>
                <th class="text-left">Branch</th>
                <th class="text-center">Physical Qty</th>
                <th class="text-center">Reserved Qty</th>
                <th class="text-right">Est. Unit Price*</th>
            </x-slot>

            <x-slot name="body">
                @forelse($items as $item)
                    <tr>
                        <td class="text-left">
                            <div class="font-semibold text-sm text-[#1E2B27]">
                                @if($item->variant)
                                    {{ $item->product->name ?? 'Unknown' }} - {{ $item->variant->sku }}
                                @else
                                    {{ $item->product->name ?? 'Unknown Product' }}
                                @endif
                            </div>
                        </td>
                        <td class="text-left text-sm text-[#61706B]">
                            {{ $item->branch ? $item->branch->name : 'Global' }}
                        </td>
                        <td class="text-center font-mono text-sm font-semibold text-[#1E2B27]">
                            {{ $item->quantity }}
                        </td>
                        <td class="text-center font-mono text-sm text-[#61706B]">
                            {{ $item->reserved_quantity }}
                        </td>
                        <td class="text-right font-mono text-sm font-medium text-[#1E2B27]">
                            {{ number_format($item->product->price ?? 0, 2) }} <span class="text-[10px] font-normal uppercase tracking-[0.18em] text-[#7A8681]">MAD</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="datatable-empty">
                            <x-nino.empty-state
                                title="No stock items to report"
                                description="Inventory valuation rows will appear here once products are stocked in a branch."
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
