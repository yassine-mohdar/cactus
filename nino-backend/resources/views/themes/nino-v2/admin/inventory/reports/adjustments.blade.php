@extends('admin.layouts.app')

@section('title', 'Adjustment History & Damaged Stock')
@section('header')
    <x-nino.page-header
        title="Adjustment History"
        subtitle="Chronological log of all stock movements, restocks, shrinkage, and damage reports." />
@endsection

@section('content')
    <div class="mb-6 grid gap-4 md:grid-cols-4">
        <x-nino.detail-section title="Movement Rows" subtitle="All inventory movements in your visible scope.">
            <p class="font-mono text-2xl font-bold text-[#1E2B27]">{{ number_format($summary['rows']) }}</p>
        </x-nino.detail-section>
        <x-nino.detail-section title="Additions" subtitle="Inbound stock, returns, and received transfers.">
            <p class="font-mono text-2xl font-bold text-[#1E2B27]">{{ number_format($summary['additions']) }}</p>
        </x-nino.detail-section>
        <x-nino.detail-section title="Deductions" subtitle="Adjustments, damage, and shipped transfers.">
            <p class="font-mono text-2xl font-bold text-[#1E2B27]">{{ number_format($summary['deductions']) }}</p>
        </x-nino.detail-section>
        <x-nino.detail-section title="Damage Events" subtitle="Dedicated damage-reason movement entries.">
            <p class="font-mono text-2xl font-bold text-[#1E2B27]">{{ number_format($summary['damage_events']) }}</p>
        </x-nino.detail-section>
    </div>

    <div class="filter-toolbar mb-6">
        <form method="GET" class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
            <div class="grid gap-3 sm:grid-cols-2 xl:w-full xl:max-w-6xl xl:grid-cols-5">
                <div class="filter-field xl:col-span-2">
                    <label class="filter-label" for="adjustments-search">Search</label>
                    <input id="adjustments-search" type="text" name="search" value="{{ request('search') }}" placeholder="Product, SKU, notes, or user" class="input-field">
                </div>
                <div class="filter-field">
                    <label class="filter-label" for="adjustments-branch">Branch</label>
                    <select id="adjustments-branch" name="branch_id" class="input-field">
                        <option value="">All visible branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-field">
                    <label class="filter-label" for="adjustments-reason">Reason</label>
                    <select id="adjustments-reason" name="reason" class="input-field">
                        <option value="">All reasons</option>
                        <option value="manual_adjustment" @selected(request('reason') === 'manual_adjustment')>Manual inventory count</option>
                        <option value="damage" @selected(request('reason') === 'damage')>Damaged Stock</option>
                        <option value="shrinkage" @selected(request('reason') === 'shrinkage')>Shrinkage / Loss</option>
                        <option value="restock" @selected(request('reason') === 'restock')>Restocks</option>
                        <option value="return" @selected(request('reason') === 'return')>Customer returns</option>
                        <option value="transfer_in" @selected(request('reason') === 'transfer_in')>Transfer in</option>
                        <option value="transfer_out" @selected(request('reason') === 'transfer_out')>Transfer out</option>
                    </select>
                </div>
                <div class="filter-field">
                    <label class="filter-label" for="adjustments-type">Movement Type</label>
                    <select id="adjustments-type" name="type" class="input-field">
                        <option value="">All types</option>
                        <option value="addition" @selected(request('type') === 'addition')>Increase</option>
                        <option value="deduction" @selected(request('type') === 'deduction')>Decrease</option>
                        <option value="set" @selected(request('type') === 'set')>Set Quantity</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <x-admin.button type="submit" variant="primary">Apply Filters</x-admin.button>
                @if(request()->filled('search') || request()->filled('reason') || request()->filled('type') || request()->filled('branch_id'))
                    <a href="{{ route('admin.inventory.reports.adjustments') }}" class="btn-secondary">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <div class="datatable-shell">
        <div class="datatable-header">
            <div>
                <h2 class="datatable-title">Inventory Movement Log</h2>
                <p class="datatable-subtitle">Every quantity change captured with the operator, reason, and resulting stock level.</p>
            </div>
            <span class="datatable-meta">{{ number_format($movements->total()) }} movements</span>
        </div>

        <x-nino.table>
            <x-slot name="head">
                <th class="text-left">Date</th>
                <th class="text-left">Item</th>
                <th class="text-left">User</th>
                <th class="text-left">Reason / Type</th>
                <th class="text-right">Adjustment</th>
                <th class="text-right">Result</th>
            </x-slot>

            <x-slot name="body">
                @forelse($movements as $mov)
                    <tr>
                        <td class="text-left font-mono text-xs text-[#61706B]">{{ $mov->created_at->format('M d, Y H:i') }}</td>
                        <td class="text-left">
                            <div class="font-semibold text-sm text-[#1E2B27]">
                                @if($mov->stockItem->variant)
                                    {{ $mov->stockItem->product->name ?? 'Unknown' }} - {{ $mov->stockItem->variant->sku }}
                                @else
                                    {{ $mov->stockItem->product->name ?? 'Unknown' }}
                                @endif
                            </div>
                            <p class="mt-1 text-[11px] text-[#7A8681]">{{ $mov->stockItem->branch?->name ?? 'Global stock' }}</p>
                            @if($mov->notes)
                                <p class="mt-1 text-[11px] text-[#7A8681]">{{ $mov->notes }}</p>
                            @endif
                        </td>
                        <td class="text-left text-sm font-medium text-[#1E2B27]">{{ $mov->user ? $mov->user->name : 'System' }}</td>
                        <td class="text-left">
                            <x-nino.status-badge tone="neutral" size="sm">{{ str_replace('_', ' ', $mov->reason) }}</x-nino.status-badge>
                            <p class="mt-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-[#7A8681]">{{ $mov->type }}</p>
                        </td>
                        <td class="text-right font-mono text-sm font-semibold {{ $mov->quantity > 0 ? 'text-[#1F7A4E]' : ($mov->quantity < 0 ? 'text-[#C45143]' : 'text-[#61706B]') }}">
                            {{ $mov->quantity > 0 ? '+' : '' }}{{ $mov->quantity }}
                        </td>
                        <td class="text-right font-mono text-sm text-[#61706B]">
                            {{ $mov->quantity_before }} &rarr; <span class="font-semibold text-[#1E2B27]">{{ $mov->quantity_after }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="datatable-empty">
                            <x-nino.empty-state
                                title="No adjustment history found"
                                description="Try widening your filters or checking another stock movement reason."
                                icon="history" />
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
