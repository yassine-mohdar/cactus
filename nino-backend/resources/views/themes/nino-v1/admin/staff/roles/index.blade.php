@extends('admin.layouts.app')

@section('header')
    <div class="page-header">
        <div>
            <a href="{{ route('admin.staff.index') }}" class="text-sm text-gray-500 hover:text-sage mb-1 block">&larr; Back to Staff</a>
            <h1 class="page-title">Role Management</h1>
            <p class="page-subtitle">Manage system roles and create custom role profiles for operational teams.</p>
        </div>
        @can('create', Spatie\Permission\Models\Role::class)
            <x-admin.button href="{{ route('admin.staff.roles.create') }}" variant="primary">New Custom Role</x-admin.button>
        @endcan
    </div>
@endsection

@section('content')
    <x-admin.card noPadding>
        <div class="datatable-header">
            <div>
                <h2 class="datatable-title">All Roles</h2>
                <p class="datatable-subtitle">Protected presets remain identifiable while custom roles stay editable.</p>
            </div>
            <span class="datatable-meta">{{ $roles->count() }} roles</span>
        </div>

        <div class="datatable-scroll">
            <table class="nino-table">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Permissions</th>
                        <th>Assigned Staff</th>
                        <th>Type</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roles as $role)
                        <tr>
                            <td>
                                <div class="font-bold text-sm text-slate-900">{{ $role->name }}</div>
                                <div class="text-xs text-outline">{{ $role->permissions_count }} configured permissions</div>
                            </td>
                            <td class="text-sm text-slate-500">
                                {{ $role->permissions->pluck('name')->take(3)->implode(', ') ?: 'No permissions yet' }}
                                @if($role->permissions_count > 3)
                                    <span class="block text-[10px] uppercase tracking-widest text-outline mt-1">+{{ $role->permissions_count - 3 }} more</span>
                                @endif
                            </td>
                            <td class="text-sm font-medium text-slate-500">{{ number_format($role->users_count) }}</td>
                            <td>
                                <span class="inline-flex items-center px-2 py-1 rounded text-[10px] font-bold uppercase tracking-widest {{ in_array($role->name, $protectedRoleNames, true) ? 'bg-slate-100 text-slate-900' : 'bg-sage/10 text-sage' }}">
                                    {{ in_array($role->name, $protectedRoleNames, true) ? 'System' : 'Custom' }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="table-actions">
                                    @can('update', $role)
                                        <a href="{{ route('admin.staff.roles.edit', $role) }}" class="table-action-link">Edit</a>
                                    @endcan
                                    @can('delete', $role)
                                        <form action="{{ route('admin.staff.roles.destroy', $role) }}" method="POST" class="inline" onsubmit="return confirm('Delete this custom role?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="table-action-danger">Delete</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-admin.card>
@endsection
