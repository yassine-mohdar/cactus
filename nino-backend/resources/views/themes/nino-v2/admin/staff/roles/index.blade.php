@extends('admin.layouts.app')

@section('title', 'Role Management')
@section('header')
    <x-nino.page-header
        title="Role Management"
        subtitle="Manage protected role presets and create custom access profiles for operational teams.">
        <x-slot:actions>
            <x-nino.button href="{{ route('admin.staff.index') }}" variant="secondary" icon="arrow_back">Back to Staff</x-nino.button>
            @can('create', Spatie\Permission\Models\Role::class)
                <x-nino.button href="{{ route('admin.staff.roles.create') }}" variant="primary" icon="add">New Custom Role</x-nino.button>
            @endcan
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    <div class="datatable-shell">
        <div class="datatable-header">
            <div>
                <h2 class="datatable-title">Roles</h2>
                <p class="datatable-subtitle">System roles stay identifiable while custom roles remain fully manageable.</p>
            </div>
            <span class="datatable-meta">{{ $roles->count() }} roles</span>
        </div>

        <x-nino.table>
            <x-slot name="head">
                <th>Role</th>
                <th>Permissions</th>
                <th>Assigned Staff</th>
                <th>Type</th>
                <th class="text-right">Actions</th>
            </x-slot>
            <x-slot name="body">
                @foreach($roles as $role)
                    <tr>
                        <td>
                            <div>
                                <div class="text-sm font-semibold text-[#1E2B27]">{{ $role->name }}</div>
                                <div class="text-xs text-[#61706B]">{{ $role->permissions_count }} configured permissions</div>
                            </div>
                        </td>
                        <td class="text-sm text-[#61706B]">
                            {{ $role->permissions->pluck('name')->take(3)->implode(', ') ?: 'No permissions yet' }}
                            @if($role->permissions_count > 3)
                                <span class="block text-xs text-[#7A8681]">+{{ $role->permissions_count - 3 }} more</span>
                            @endif
                        </td>
                        <td class="text-sm font-medium text-[#61706B]">{{ number_format($role->users_count) }}</td>
                        <td>
                            <x-nino.status-badge :tone="in_array($role->name, $protectedRoleNames, true) ? 'neutral' : 'success'" size="sm">
                                {{ in_array($role->name, $protectedRoleNames, true) ? 'System' : 'Custom' }}
                            </x-nino.status-badge>
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
            </x-slot>
        </x-nino.table>
    </div>
@endsection
