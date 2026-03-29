@extends('admin.layouts.app')

@section('title', 'Low Stock Report')

@section('header')
    <x-nino.page-header
        title="Low Stock Report"
        subtitle="Read-only reporting surface for stock rows at or below threshold in your visible operating scope.">
        <x-slot:actions>
            <x-admin.button href="{{ route('admin.inventory.low-stock') }}" variant="outline" class="flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">warning</span> Manage Alerts
            </x-admin.button>
            <x-admin.button href="{{ route('admin.inventory.reports.stock') }}" variant="outline" class="flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">inventory_2</span> Current Stock
            </x-admin.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    <div class="mb-6 grid gap-4 md:grid-cols-4">
        <x-nino.detail-section title="Alert Rows" subtitle="Low-stock or out-of-stock rows in the report scope.">
            <p class="font-mono text-2xl font-bold text-[#1E2B27]">{{ number_format($summary['rows']) }}</p>
        </x-nino.detail-section>
        <x-nino.detail-section title="Low Stock" subtitle="Rows still above zero but below threshold.">
            <p class="font-mono text-2xl font-bold text-[#1E2B27]">{{ number_format($summary['low_stock']) }}</p>
        </x-nino.detail-section>
        <x-nino.detail-section title="Out Of Stock" subtitle="Rows with no immediately available quantity.">
            <p class="font-mono text-2xl font-bold text-[#1E2B27]">{{ number_format($summary['out_of_stock']) }}</p>
        </x-nino.detail-section>
        <x-nino.detail-section title="Reserved Units" subtitle="Held units across all reported alert rows.">
            <p class="font-mono text-2xl font-bold text-[#1E2B27]">{{ number_format($summary['reserved_units']) }}</p>
        </x-nino.detail-section>
    </div>

    <div class="filter-toolbar mb-6">
        <form method="GET" class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
            <div class="grid gap-3 sm:grid-cols-2 xl:w-full xl:max-w-5xl xl:grid-cols-3">
                <div class="filter-field xl:col-span-2">
                    <label class="filter-label" for="low-stock-report-search">Search</label>
                    <input id="low-stock-report-search" type="text" name="search" value="{{ request('search') }}" placeholder="Product name or SKU" class="input-field">
                </div>
                <div class="filter-field">
                    <label class="filter-label" for="low-stock-report-branch">Branch</label>
                    <select id="low-stock-report-branch" name="branch_id" class="input-field">
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
                    <a href="{{ route('admin.inventory.reports.low-stock') }}" class="btn-secondary">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <div class="datatable-shell">
        <div class="datatable-header">
            <div>
                <h2 class="datatable-title">Threshold Pressure</h2>
                <p class="datatable-subtitle">Rows sorted by available quantity so the most urgent replenishment pressure appears first.</p>
            </div>
            <span class="datatable-meta">{{ number_format($items->total()) }} rows</span>
        </div>

        <x-nino.table>
            <x-slot name="head">
                <th class="text-left">Item</th>
                <th class="text-left">Branch</th>
                <th class="text-right">Available</th>
                <th class="text-right">Reserved</th>
                <th class="text-right">Threshold</th>
                <th class="text-center">Severity</th>
            </x-slot>

            <x-slot name="body">
                @forelse($items as $item)
                    @php
                        $available = (int) ($item->available_quantity ?? 0);
                    @endphp
                    <tr>
                        <td class="text-left">
                            <div class="font-semibold text-sm text-[#1E2B27]">{{ $item->product?->name ?? 'Deleted Product' }}</div>
                            <p class="mt-1 font-mono text-[10px] tracking-widest text-[#61706B]">{{ $item->variant?->sku ?? $item->product?->sku ?? 'NO-SKU' }}</p>
                        </td>
                        <td class="text-left text-sm text-[#61706B]">{{ $item->branch?->name ?? 'Global stock' }}</td>
                        <td class="text-right font-mono text-sm font-semibold {{ $available <= 0 ? 'text-[#C45143]' : 'text-[#B97A22]' }}">{{ number_format($available) }}</td>
                        <td class="text-right font-mono text-sm text-[#61706B]">{{ number_format((int) $item->reserved_quantity) }}</td>
                        <td class="text-right font-mono text-sm text-[#61706B]">{{ number_format((int) $item->low_stock_threshold) }}</td>
                        <td class="text-center">
                            <x-nino.status-badge :tone="$available <= 0 ? 'danger' : 'warning'" size="sm">
                                {{ $available <= 0 ? 'Out of stock' : 'Low stock' }}
                            </x-nino.status-badge>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="datatable-empty">
                            <x-nino.empty-state
                                title="No low-stock rows"
                                description="There are no stock rows at or below threshold for the current report scope."
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
