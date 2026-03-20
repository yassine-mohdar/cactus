@extends('admin.layouts.app')

@section('title', 'Staff Management')
@section('header')
    <div class="page-header">
        <div>
            <h1 class="page-title">Staff Management</h1>
            <p class="page-subtitle">Manage access, scope, and role assignments for internal users.</p>
        </div>
        @can('create', App\Models\User::class)
            <x-admin.button href="{{ route('admin.staff.create') }}" variant="primary" class="flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">add</span> New Staff Member
            </x-admin.button>
        @endcan
    </div>
@endsection

@section('content')
    <div class="filter-toolbar mb-6">
        <form method="GET" class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="filter-field w-full sm:max-w-md">
                <label class="filter-label" for="staff-search">Search</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">search</span>
                    <input id="staff-search" type="text" name="search" value="{{ request('search') }}" placeholder="Name, email, or role..." class="input-field pl-9">
                </div>
            </div>
            <div class="flex items-center gap-3">
                <x-admin.button type="submit" variant="primary">Search</x-admin.button>
                @if(request()->filled('search'))
                    <a href="{{ route('admin.staff.index') }}" class="btn-secondary">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <x-admin.card noPadding>
        <div class="datatable-header">
            <div>
                <h2 class="datatable-title">All Staff</h2>
                <p class="datatable-subtitle">Role visibility, organization scope, and actions are now standardized in one table layout.</p>
            </div>
            <span class="datatable-meta">{{ number_format($staff->total()) }} team members</span>
        </div>
        
        <div class="datatable-scroll">
            <table class="nino-table">
                <thead>
                    <tr>
                        <th>Name & Email</th>
                        <th>Role(s)</th>
                        <th>Scope</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staff as $user)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-900 font-bold ring-1 ring-primary/20">
                                        {{ substr($user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-sm text-slate-900">{{ $user->name }}</div>
                                        <div class="text-xs text-outline">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @foreach($user->roles as $role)
                                    <span class="inline-flex items-center px-2 py-1 rounded text-[10px] font-bold uppercase tracking-widest {{ $role->name === 'Super Admin' ? 'bg-red-500/10 text-red-600' : 'bg-slate-100 text-slate-900' }}">
                                        {{ $role->name }}
                                    </span>
                                @endforeach
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-slate-500">
                                {{ ucfirst($user->organization_scope ?? 'Platform') }}
                                @if($user->organization_id)
                                    <span class="text-[10px] font-bold uppercase block text-outline mt-1 tracking-widest">Org ID: {{ $user->organization_id }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                    @if($user->status === 'active')
                                        <span class="inline-flex items-center px-2 py-1 rounded text-[10px] font-bold bg-slate-100 text-slate-900 uppercase tracking-widest">Active</span>
                                    @elseif($user->status === 'inactive')
                                        <span class="inline-flex items-center px-2 py-1 rounded text-[10px] font-bold bg-white-container-highest text-outline uppercase tracking-widest">Inactive</span>
                                    @else
                                    <span class="inline-flex items-center px-2 py-1 rounded text-[10px] font-bold bg-red-500/10 text-red-600 uppercase tracking-widest">Suspended</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="table-actions">
                                    @canImpersonate
                                        @canBeImpersonated($user)
                                            <a href="{{ route('impersonate', $user->id) }}" class="table-action-link" title="Impersonate User">
                                                Impersonate
                                            </a>
                                        @endCanBeImpersonated
                                    @endCanImpersonate

                                    @can('update', $user)
                                        <a href="{{ route('admin.staff.edit', $user) }}" class="table-action-link">Edit</a>
                                    @endcan
                                    
                                    @can('delete', $user)
                                        <form action="{{ route('admin.staff.destroy', $user) }}" method="POST" onsubmit="return confirm('Are you sure you want to disable/delete this staff member?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="table-action-danger">Delete</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="datatable-empty">
                                <div class="datatable-empty-panel">
                                    <p class="text-sm font-semibold text-slate-900">No staff found.</p>
                                    <p class="text-sm text-slate-500">Try a broader search or add a new staff member.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($staff->hasPages())
            <div class="datatable-footer">
                {{ $staff->links() }}
            </div>
        @endif
    </x-admin.card>
@endsection
