@extends('admin.layouts.app')

@section('title', 'Damaged Stock Report')

@section('header')
    <x-nino.page-header
        title="Damaged Stock Report"
        subtitle="Read-only reporting surface for damage-only stock deductions across your visible branch scope.">
        <x-slot:actions>
            <x-admin.button href="{{ route('admin.inventory.damage.index') }}" variant="outline" class="flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">warning</span> Damage Queue
            </x-admin.button>
            <x-admin.button href="{{ route('admin.inventory.reports.adjustments', ['reason' => 'damage']) }}" variant="outline" class="flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">history</span> Full Movement Log
            </x-admin.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    <div class="mb-6 grid gap-4 md:grid-cols-4">
        <x-nino.detail-section title="Damage Events" subtitle="Damage-only movement rows in the report scope.">
            <p class="font-mono text-2xl font-bold text-[#1E2B27]">{{ number_format($summary['rows']) }}</p>
        </x-nino.detail-section>
        <x-nino.detail-section title="Damaged Units" subtitle="Absolute units removed through the damage workflow.">
            <p class="font-mono text-2xl font-bold text-[#1E2B27]">{{ number_format($summary['units']) }}</p>
        </x-nino.detail-section>
        <x-nino.detail-section title="Branch Rows" subtitle="Damage entries tied to branch-owned stock.">
            <p class="font-mono text-2xl font-bold text-[#1E2B27]">{{ number_format($summary['branch_rows']) }}</p>
        </x-nino.detail-section>
        <x-nino.detail-section title="Staff Logged" subtitle="Damage rows with explicit operator attribution.">
            <p class="font-mono text-2xl font-bold text-[#1E2B27]">{{ number_format($summary['operator_rows']) }}</p>
        </x-nino.detail-section>
    </div>

    <div class="filter-toolbar mb-6">
        <form method="GET" class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
            <div class="grid gap-3 sm:grid-cols-2 xl:w-full xl:max-w-5xl xl:grid-cols-3">
                <div class="filter-field xl:col-span-2">
                    <label class="filter-label" for="damage-report-search">Search</label>
                    <input id="damage-report-search" type="text" name="search" value="{{ request('search') }}" placeholder="Product, SKU, notes, or operator" class="input-field">
                </div>
                <div class="filter-field">
                    <label class="filter-label" for="damage-report-branch">Branch</label>
                    <select id="damage-report-branch" name="branch_id" class="input-field">
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
                    <a href="{{ route('admin.inventory.reports.damaged-stock') }}" class="btn-secondary">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <div class="datatable-shell">
        <div class="datatable-header">
            <div>
                <h2 class="datatable-title">Damage Log</h2>
                <p class="datatable-subtitle">Dedicated reporting view for damage-only deductions with branch, operator, and notes context.</p>
            </div>
            <span class="datatable-meta">{{ number_format($movements->total()) }} rows</span>
        </div>

        <x-nino.table>
            <x-slot name="head">
                <th class="text-left">Date</th>
                <th class="text-left">Item</th>
                <th class="text-left">Branch</th>
                <th class="text-left">Operator</th>
                <th class="text-right">Units</th>
                <th class="text-left">Notes</th>
            </x-slot>

            <x-slot name="body">
                @forelse($movements as $movement)
                    <tr>
                        <td class="font-mono text-xs text-[#61706B]">{{ $movement->created_at?->format('M d, Y H:i') }}</td>
                        <td class="text-sm font-semibold text-[#17302A]">
                            {{ $movement->stockItem?->product?->name ?? 'Unknown item' }}
                            @if($movement->stockItem?->variant?->sku)
                                <span class="block pt-1 font-mono text-[10px] tracking-[0.16em] text-[#7A8681]">{{ $movement->stockItem->variant->sku }}</span>
                            @endif
                        </td>
                        <td class="text-sm text-[#61706B]">{{ $movement->stockItem?->branch?->name ?? 'Global stock' }}</td>
                        <td class="text-sm text-[#61706B]">{{ $movement->user?->name ?? 'System' }}</td>
                        <td class="text-right font-mono text-sm font-semibold text-[#C45143]">{{ number_format(abs((int) $movement->quantity)) }}</td>
                        <td class="text-sm text-[#61706B]">{{ $movement->notes ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="datatable-empty">
                            <x-nino.empty-state
                                title="No damaged stock rows"
                                description="Damage-only inventory deductions will appear here once the workflow records dedicated damage movements."
                                icon="warning" />
                        </td>
                    </tr>
                @endforelse
            </x-slot>
        </x-nino.table>

        @if($movements->hasPages())
            <div class="datatable-footer">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
@endsection
