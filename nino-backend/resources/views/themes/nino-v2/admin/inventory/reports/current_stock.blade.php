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
            <x-admin.button href="#" variant="primary" class="flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">download</span> Export CSV
            </x-admin.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
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
