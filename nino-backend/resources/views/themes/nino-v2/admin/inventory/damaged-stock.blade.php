@extends('admin.layouts.app')

@section('title', 'Damaged Stock History')

@section('header')
    <x-nino.page-header
        title="Damaged Stock History"
        subtitle="Dedicated queue of inventory deductions recorded with the damage reason.">
        <x-slot:actions>
            <x-nino.button href="{{ route('admin.inventory.damage.create') }}" variant="primary" size="sm">Report damage</x-nino.button>
            <x-nino.button href="{{ route('admin.inventory.reports.adjustments', ['reason' => 'damage']) }}" variant="secondary" size="sm">Open full movement log</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    <div class="filter-toolbar mb-6">
        <form method="GET" class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
            <div class="grid gap-3 xl:w-full xl:max-w-4xl xl:grid-cols-1">
                <div class="filter-field">
                    <label class="filter-label" for="damage-search">Search</label>
                    <input id="damage-search" type="text" name="search" value="{{ request('search') }}" placeholder="Product, SKU, notes, or operator" class="input-field">
                </div>
            </div>

            <div class="flex items-center gap-3">
                <x-admin.button type="submit" variant="primary">Apply Filters</x-admin.button>
                @if(request()->filled('search'))
                    <a href="{{ route('admin.inventory.damage.index') }}" class="btn-secondary">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <div class="datatable-shell">
        <div class="datatable-header">
            <div>
                <h2 class="datatable-title">Damage movement queue</h2>
                <p class="datatable-subtitle">Every damaged-stock deduction with branch context, operator attribution, and notes.</p>
            </div>
            <span class="datatable-meta">{{ number_format($movements->total()) }} damage events</span>
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
                        <td class="text-sm text-[#61706B]">{{ $movement->stockItem?->branch?->name ?? 'Global' }}</td>
                        <td class="text-sm text-[#61706B]">{{ $movement->user?->name ?? 'System' }}</td>
                        <td class="text-right font-mono text-sm font-semibold text-[#C45143]">{{ number_format(abs((int) $movement->quantity)) }}</td>
                        <td class="text-sm text-[#61706B]">{{ $movement->notes ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="datatable-empty">
                            <x-nino.empty-state
                                title="No damaged stock records"
                                description="Damage reports will appear here once warehouse or branch staff record dedicated damage deductions."
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
