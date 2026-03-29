@extends('admin.layouts.app')

@section('header')
<div class="page-header">
    <div>
        <h1 class="page-title">Customers</h1>
        <p class="page-subtitle">Manage customer records with a cleaner list layout and clearer actions.</p>
    </div>
    <a href="{{ route('admin.customers.create') }}" class="btn-primary gap-2">
        <span class="material-symbols-outlined text-sm">person_add</span>
        New Customer
    </a>
</div>
@endsection

@section('content')
<div class="datatable-shell">
    <div class="datatable-header">
        <div>
            <h2 class="datatable-title">Customer Directory</h2>
            <p class="datatable-subtitle">The list now emphasizes primary identity data instead of forcing users to parse dense text blocks.</p>
        </div>
        <span class="datatable-meta">{{ number_format($customers->total()) }} customers</span>
    </div>

    <x-nino.table>
        <x-slot name="head">
            <th class="text-left">Name</th>
            <th class="text-left">Email</th>
            <th class="text-left">Joined</th>
            <th class="text-right">Actions</th>
        </x-slot>
        <x-slot name="body">
            @forelse($customers as $customer)
                <tr>
                    <td class="text-left font-medium text-slate-900">
                        <a href="{{ route('admin.customers.show', $customer) }}" class="table-link">{{ $customer->name }}</a>
                    </td>
                    <td class="text-left text-slate-500">{{ $customer->email }}</td>
                    <td class="text-left font-mono text-slate-500">{{ $customer->created_at->format('M j, Y') }}</td>
                    <td class="text-right">
                        <a href="{{ route('admin.customers.show', $customer) }}" class="table-action-link">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="datatable-empty">
                        <div class="datatable-empty-panel">
                            <p class="text-sm font-semibold text-slate-900">No customers found.</p>
                            <p class="text-sm text-slate-500">Customer records will appear here once accounts are created.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-slot>
    </x-nino.table>
    
    @if($customers->hasPages())
        <div class="datatable-footer">
            {{ $customers->links() }}
        </div>
    @endif
</div>
@endsection
