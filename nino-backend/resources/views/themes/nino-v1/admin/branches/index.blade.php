@extends('admin.layouts.app')

@section('title', 'Branches')

@section('header')
    <div class="page-header">
        <div>
            <h1 class="page-title">Branches</h1>
            <p class="page-subtitle">Manage physical branch locations, warehouse contacts, and operating status.</p>
        </div>
        <a href="{{ route('admin.inventory.branches.create') }}" class="btn-primary gap-2">
            <span class="material-symbols-outlined text-sm">add</span>
            New Branch
        </a>
    </div>
@endsection

@section('content')
<div class="datatable-shell">
    <div class="datatable-header">
        <div>
            <h2 class="datatable-title">Branch Directory</h2>
            <p class="datatable-subtitle">Clearer location and contact details, with actions that stay discoverable on desktop and mobile.</p>
        </div>
        <span class="datatable-meta">{{ number_format($branches->total()) }} branches</span>
    </div>

    <div class="datatable-scroll">
    <table class="nino-table">
        <thead>
            <tr>
                <th>Branch</th>
                <th>Contact Info</th>
                <th class="text-center">Location</th>
                <th class="text-center">Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($branches as $branch)
                <tr class="group">
                    <td class="px-6 py-4">
                        <a href="{{ route('admin.inventory.branches.show', $branch) }}" class="table-link">{{ $branch->name }}</a>
                        <div class="text-[10px] text-slate-500 font-medium uppercase tracking-widest">{{ $branch->code ?? 'NO-CODE' }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-xs text-slate-900">{{ $branch->email ?? 'No Email' }}</div>
                        <div class="text-xs text-slate-500 italic">{{ $branch->phone ?? 'No Phone' }}</div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="text-xs text-slate-900 font-semibold">{{ $branch->city ?? 'N/A' }}</div>
                        <div class="text-[10px] text-slate-500 uppercase tracking-wider">{{ $branch->country ?? 'N/A' }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex justify-center">
                            <span class="px-2 py-1 {{ $branch->status === 'active' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }} rounded-lg text-[10px] font-black uppercase tracking-widest border {{ $branch->status === 'active' ? 'border-green-100' : 'border-red-100' }}">
                                {{ $branch->status }}
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="table-actions opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                            <a href="{{ route('admin.inventory.branches.edit', $branch) }}" class="table-action-link">
                                <span class="material-symbols-outlined text-lg">edit</span>
                            </a>
                            <form action="{{ route('admin.inventory.branches.destroy', $branch) }}" method="POST" onsubmit="return confirm('Delete this branch?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="table-action-danger">
                                    <span class="material-symbols-outlined text-lg">delete</span>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="datatable-empty">
                        <div class="datatable-empty-panel">
                            <span class="material-symbols-outlined text-4xl text-slate-300">store</span>
                            <p class="text-sm font-semibold text-slate-900">No branches found.</p>
                            <p class="text-sm text-slate-500">Create a branch to start routing stock and orders by location.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>
    
    @if($branches->hasPages())
        <div class="datatable-footer">
            {{ $branches->links() }}
        </div>
    @endif
</div>
@endsection
