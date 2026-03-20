@extends('admin.layouts.app')

@section('title', 'Suppliers')

@section('header')
    <div class="page-header">
        <div>
            <h1 class="page-title">Suppliers</h1>
            <p class="page-subtitle">Track supplier contacts, location coverage, and vendor status in one view.</p>
        </div>
        <a href="{{ route('admin.inventory.suppliers.create') }}" class="btn-primary gap-2">
            <span class="material-symbols-outlined text-sm">add</span>
            New Supplier
        </a>
    </div>
@endsection

@section('content')
<div class="datatable-shell">
    <div class="datatable-header">
        <div>
            <h2 class="datatable-title">Supplier Directory</h2>
            <p class="datatable-subtitle">Vendor data is grouped into clear primary cells and cleaner action affordances.</p>
        </div>
        <span class="datatable-meta">{{ number_format($suppliers->total()) }} suppliers</span>
    </div>

    <x-nino.table>
        <x-slot name="head">
            <th class="text-left">Supplier</th>
            <th class="text-left">Contact Info</th>
            <th class="text-center">Location</th>
            <th class="text-center">Status</th>
            <th class="text-right">Actions</th>
        </x-slot>
        <x-slot name="body">
            @forelse($suppliers as $supplier)
                <tr class="group">
                    <td class="text-left">
                        <a href="{{ route('admin.inventory.suppliers.show', $supplier) }}" class="table-link">{{ $supplier->name }}</a>
                        <div class="mt-0.5 font-mono text-[10px] tracking-widest text-[#61706B]">{{ $supplier->code ?? 'NO-CODE' }}</div>
                    </td>
                    <td class="text-left">
                        <div class="text-sm font-medium text-[#61706B]">{{ $supplier->email ?? 'No Email' }}</div>
                        <div class="text-xs italic text-[#7A8681]">{{ $supplier->phone ?? 'No Phone' }}</div>
                    </td>
                    <td class="text-center">
                        <div class="text-sm font-semibold text-[#1E2B27]">{{ $supplier->city ?? 'N/A' }}</div>
                        <div class="text-[10px] uppercase tracking-wider text-[#61706B]">{{ $supplier->country ?? 'N/A' }}</div>
                    </td>
                    <td class="text-center">
                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-widest border {{ $supplier->status === 'active' ? 'bg-emerald-50 text-emerald-600 border-emerald-200' : 'bg-red-50 text-red-600 border-red-200' }}">
                            {{ $supplier->status }}
                        </span>
                    </td>
                    <td class="text-right">
                        <div class="table-actions opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                            <a href="{{ route('admin.inventory.suppliers.edit', $supplier) }}" class="table-action-link">
                                <span class="material-symbols-outlined text-[1.2rem]">edit</span>
                            </a>
                            <form action="{{ route('admin.inventory.suppliers.destroy', $supplier) }}" method="POST" onsubmit="return confirm('Delete this supplier?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="table-action-danger">
                                    <span class="material-symbols-outlined text-[1.2rem]">delete</span>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                        <tr>
                            <td colspan="5" class="datatable-empty">
                                <x-nino.empty-state
                                    title="No suppliers found"
                                    description="Add your first supplier to start tracking purchasing partners and supply chains."
                                    icon="domain">
                                    <x-nino.button href="{{ route('admin.inventory.suppliers.create') }}" variant="primary" icon="add">New Supplier</x-nino.button>
                                </x-nino.empty-state>
                            </td>
                        </tr>
            @endforelse
        </x-slot>
    </x-nino.table>
    
    @if($suppliers->hasPages())
        <div class="datatable-footer">
            {{ $suppliers->links() }}
        </div>
    @endif
</div>
@endsection
