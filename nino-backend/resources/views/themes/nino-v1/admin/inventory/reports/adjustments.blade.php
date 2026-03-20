@extends('admin.layouts.app')

@section('title', 'Adjustment History & Damaged Stock')
@section('header', 'Adjustment History')
@section('subheader', 'Chronological log of all stock movements and damage reports.')

@section('content')
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex gap-2 border border-slate-300 p-1 rounded-md bg-surface overflow-x-auto">
            <a href="{{ route('admin.inventory.reports.adjustments') }}" class="px-4 py-2 rounded-md text-[10px] font-bold uppercase tracking-widest transition-colors whitespace-nowrap {{ !request('reason') ? 'bg-slate-900 text-white' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">All Movements</a>
            <a href="{{ route('admin.inventory.reports.adjustments', ['reason' => 'damage']) }}" class="px-4 py-2 rounded-md text-[10px] font-bold uppercase tracking-widest transition-colors flex items-center gap-2 whitespace-nowrap {{ request('reason') === 'damage' ? 'bg-slate-900 text-white' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                <span class="material-symbols-outlined text-[14px]">broken_image</span> Damaged Stock
            </a>
            <a href="{{ route('admin.inventory.reports.adjustments', ['reason' => 'shrinkage']) }}" class="px-4 py-2 rounded-md text-[10px] font-bold uppercase tracking-widest transition-colors whitespace-nowrap {{ request('reason') === 'shrinkage' ? 'bg-slate-900 text-white' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                Shrinkage / Loss
            </a>
            <a href="{{ route('admin.inventory.reports.adjustments', ['reason' => 'restock']) }}" class="px-4 py-2 rounded-md text-[10px] font-bold uppercase tracking-widest transition-colors whitespace-nowrap {{ request('reason') === 'restock' ? 'bg-slate-900 text-white' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                Restocks
            </a>
        </div>
        
        <x-admin.button href="#" variant="outline" class="flex items-center gap-2 shrink-0">
            <span class="material-symbols-outlined text-sm">download</span> Export CSV
        </x-admin.button>
    </div>

    <x-admin.card>
        <div class="overflow-x-auto">
            <table class="nino-table">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-300">
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Date</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Item (SKU)</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">User</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Reason / Type</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Adjustment</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Result</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-300">
                    @forelse($movements as $mov)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 align-top text-sm font-medium text-slate-500 whitespace-nowrap font-mono">
                                {{ $mov->created_at->format('M d, Y H:i') }}
                            </td>
                            <td class="px-6 py-4 align-top">
                                <div class="font-bold text-sm text-slate-900">
                                    @if($mov->stockItem->variant)
                                        {{ $mov->stockItem->product->name ?? 'Unknown' }} - {{ $mov->stockItem->variant->sku }}
                                    @else
                                        {{ $mov->stockItem->product->name ?? 'Unknown' }}
                                    @endif
                                </div>
                                @if($mov->notes)
                                    <p class="text-[11px] text-slate-500 mt-1">{{ $mov->notes }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 align-top text-sm font-bold text-slate-900">
                                {{ $mov->user ? $mov->user->name : 'System' }}
                            </td>
                            <td class="px-6 py-4 align-top">
                                <span class="inline-flex items-center px-2 py-1 rounded text-[10px] font-bold uppercase tracking-widest bg-slate-100 text-slate-900 border border-slate-300">
                                    {{ str_replace('_', ' ', $mov->reason) }}
                                </span>
                                <span class="block text-[10px] text-slate-500 uppercase tracking-widest mt-2">{{ $mov->type }}</span>
                            </td>
                            <td class="px-6 py-4 text-right align-top font-bold font-mono text-sm {{ $mov->quantity > 0 ? 'text-slate-900' : ($mov->quantity < 0 ? 'text-red-600' : 'text-slate-500') }}">
                                {{ $mov->quantity > 0 ? '+' : '' }}{{ $mov->quantity }}
                            </td>
                            <td class="px-6 py-4 text-right align-top text-sm font-medium text-slate-500 font-mono">
                                {{ $mov->quantity_before }} &rarr; <strong class="text-slate-900">{{ $mov->quantity_after }}</strong>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-500">
                        <div class="bg-slate-50 border border-dashed border-slate-300 rounded-md py-6 text-[11px] uppercase tracking-widest font-bold">
                            
                                No adjustment history found for the selected filters.
                            
                        </div>
                    </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($movements->hasPages())
            <div class="border-t border-slate-300 px-6 py-4">
                {{ $movements->links() }}
            </div>
        @endif
    </x-admin.card>
@endsection
