@extends('admin.layouts.app')

@section('title', 'Current Stock Report')
@section('header', 'Current Stock Report')

@section('content')
    <div class="mb-6 flex justify-between items-center">
        <div>
            <p class="text-slate-500 text-sm font-medium">Exportable view of total stock value and quantity.</p>
        </div>
        <div class="flex gap-2">
            <x-admin.button href="{{ route('admin.inventory.index') }}" variant="outline" class="flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">arrow_back</span> Back to Inventory
            </x-admin.button>
            <x-admin.button href="#" variant="primary" class="flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">download</span> Export CSV
            </x-admin.button>
        </div>
    </div>

    <x-admin.card>
        <div class="p-6 border-b border-slate-300 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-widest">Stock Valuation Overview</h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="nino-table">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-300">
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Item (SKU)</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Branch</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Physical Qty</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Reserved Qty</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Est. Unit Price*</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-300">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 align-top">
                                <div class="font-bold text-sm text-slate-900">
                                    @if($item->variant)
                                        {{ $item->product->name ?? 'Unknown' }} - {{ $item->variant->sku }}
                                    @else
                                        {{ $item->product->name ?? 'Unknown Product' }}
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 align-top text-sm font-medium text-slate-500">
                                {{ $item->branch ? $item->branch->name : 'Global' }}
                            </td>
                            <td class="px-6 py-4 text-center align-top font-bold text-sm text-slate-900 font-mono">
                                {{ $item->quantity }}
                            </td>
                            <td class="px-6 py-4 text-center align-top font-bold text-sm text-slate-500 font-mono">
                                {{ $item->reserved_quantity }}
                            </td>
                            <td class="px-6 py-4 text-right align-top text-sm text-slate-900 font-medium font-mono">
                                {{ number_format($item->product->price ?? 0, 2) }} <span class="text-[10px] text-slate-500 font-normal uppercase tracking-widest">MAD</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-500">
                        <div class="bg-slate-50 border border-dashed border-slate-300 rounded-md py-6 text-[11px] uppercase tracking-widest font-bold">
                            
                                No stock items to report.
                            
                        </div>
                    </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="border-t border-slate-300 px-6 py-4">
                {{ $items->links() }}
            </div>
        @endif
    </x-admin.card>
@endsection
