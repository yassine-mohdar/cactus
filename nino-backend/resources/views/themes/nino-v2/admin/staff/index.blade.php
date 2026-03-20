@extends('admin.layouts.app')

@section('title', 'Staff Management')
@section('header')
    <x-nino.page-header
        title="Staff Management"
        subtitle="Manage access, role scope, and internal user status from one operational roster.">
        <x-slot:actions>
            @can('create', App\Models\User::class)
                <x-nino.button href="{{ route('admin.staff.create') }}" variant="primary" icon="add">New Staff Member</x-nino.button>
            @endcan
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="metric-grid">
            <div class="metric-tile">
                <p class="metric-label">Total Staff</p>
                <h3 class="metric-value">{{ number_format($summary['total_staff']) }}</h3>
                <p class="metric-subtitle">Visible staff records inside your current scope</p>
            </div>
            <div class="metric-tile">
                <p class="metric-label">Active</p>
                <h3 class="metric-value">{{ number_format($summary['active']) }}</h3>
                <p class="metric-subtitle">Users currently able to log in and operate</p>
            </div>
            <div class="metric-tile">
                <p class="metric-label">Suspended</p>
                <h3 class="metric-value">{{ number_format($summary['suspended']) }}</h3>
                <p class="metric-subtitle">Users requiring review before access is restored</p>
            </div>
            <div class="metric-tile">
                <p class="metric-label">Super Admins</p>
                <h3 class="metric-value">{{ number_format($summary['super_admins']) }}</h3>
                <p class="metric-subtitle">Users carrying the highest platform-level privileges</p>
            </div>
        </div>

        <div class="filter-toolbar">
            <form method="GET" class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div class="filter-field w-full sm:max-w-md">
                    <label class="filter-label" for="staff-search">Search</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#7A8681] text-sm">search</span>
                        <input id="staff-search" type="text" name="search" value="{{ request('search') }}" placeholder="Name, email, or role..." class="input-field pl-9">
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <x-nino.button type="submit" variant="primary">Search</x-nino.button>
                    @if(request()->filled('search'))
                        <x-nino.button href="{{ route('admin.staff.index') }}" variant="outline">Clear</x-nino.button>
                    @endif
                </div>
            </form>
        </div>

        <div class="datatable-shell">
            <div class="datatable-header">
                <div>
                    <h2 class="datatable-title">All Staff</h2>
                    <p class="datatable-subtitle">Role visibility, organization scope, and actions are standardized in one table layout.</p>
                </div>
                <span class="datatable-meta">{{ number_format($staff->total()) }} team members</span>
            </div>

            <x-nino.table>
                <x-slot name="head">
                    <th>Name & Email</th>
                    <th>Role(s)</th>
                    <th>Scope</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </x-slot>
                <x-slot name="body">
                    @forelse($staff as $user)
                        @php
                            $statusTone = match ($user->status) {
                                'active' => 'success',
                                'suspended' => 'danger',
                                default => 'neutral',
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full border border-[rgba(36,88,72,0.16)] bg-[#ECF4EE] font-bold text-[#1E2B27]">
                                        {{ substr($user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-[#1E2B27]">{{ $user->name }}</div>
                                        <div class="text-xs text-[#61706B]">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @foreach($user->roles as $role)
                                    <span class="inline-flex items-center rounded-md border px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] {{ $role->name === 'Super Admin' ? 'border-[#EFC8C1] bg-[#FCEDEA] text-[#C45143]' : 'border-[rgba(120,112,95,0.14)] bg-[#FBFAF7] text-[#61706B]' }}">
                                        {{ $role->name }}
                                    </span>
                                @endforeach
                            </td>
                            <td class="text-sm font-medium text-[#61706B]">
                                {{ ucfirst($user->organization_scope ?? 'Platform') }}
                                @if($user->organization_id)
                                    <span class="mt-1 block font-mono text-[10px] uppercase tracking-[0.16em] text-[#7A8681]">Org ID: {{ $user->organization_id }}</span>
                                @endif
                            </td>
                            <td>
                                <x-nino.status-badge :tone="$statusTone" size="sm">{{ ucfirst($user->status ?? 'active') }}</x-nino.status-badge>
                            </td>
                            <td class="text-right">
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
                                <x-nino.empty-state
                                    title="No staff found"
                                    description="Try a broader search or add a new staff member."
                                    icon="group" />
                            </td>
                        </tr>
                    @endforelse
                </x-slot>
            </x-nino.table>

            @if($staff->hasPages())
                <div class="datatable-footer">
                    {{ $staff->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
