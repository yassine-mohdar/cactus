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
                        <div class="text-[10px] font-medium uppercase tracking-widest text-[#61706B]">{{ $branch->code ?? 'NO-CODE' }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-xs text-[#1E2B27]">{{ $branch->email ?? 'No Email' }}</div>
                        <div class="text-xs italic text-[#61706B]">{{ $branch->phone ?? 'No Phone' }}</div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="text-xs font-semibold text-[#1E2B27]">{{ $branch->city ?? 'N/A' }}</div>
                        <div class="text-[10px] uppercase tracking-wider text-[#61706B]">{{ $branch->country ?? 'N/A' }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex justify-center">
                            <x-nino.status-badge :tone="$branch->status === 'active' ? 'success' : 'danger'" size="sm">{{ $branch->status }}</x-nino.status-badge>
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
                        <x-nino.empty-state title="No branches found" description="Create a branch to start routing stock and orders by location." icon="store" />
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
