@extends('admin.layouts.app')

@section('header')
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-ink">Staff Management</h1>
        @can('create', App\Models\User::class)
            <a href="{{ route('admin.staff.create') }}" class="btn-primary">
                + New Staff Member
            </a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between bg-gray-50/50">
            <h2 class="font-semibold text-ink">All Staff</h2>
            <div class="flex space-x-2">
                <input type="text" placeholder="Search staff..." class="input-field text-sm w-64">
            </div>
        </div>
        
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-sm text-gray-500 uppercase tracking-wider">
                    <th class="p-4 font-semibold">Name & Email</th>
                    <th class="p-4 font-semibold">Role(s)</th>
                    <th class="p-4 font-semibold">Scope</th>
                    <th class="p-4 font-semibold">Status</th>
                    <th class="p-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($staff as $user)
                    <tr class="hover:bg-gray-50/50 transition duration-150">
                        <td class="p-4">
                            <div class="flex items-center space-x-3">
                                <div class="h-10 w-10 rounded-full bg-sage/20 flex items-center justify-center text-sage font-bold">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="font-medium text-ink">{{ $user->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="p-4">
                            @foreach($user->roles as $role)
                                <span class="badge {{ $role->name === 'Super Admin' ? 'bg-ink text-white' : 'bg-sage/10 text-sage' }}">
                                    {{ $role->name }}
                                </span>
                            @endforeach
                        </td>
                        <td class="p-4 text-sm text-gray-600">
                            {{ ucfirst($user->organization_scope ?? 'Platform') }}
                            @if($user->organization_id)
                                <span class="text-xs block text-gray-400">Org ID: {{ $user->organization_id }}</span>
                            @endif
                        </td>
                        <td class="p-4">
                            @if($user->status === 'active')
                                <span class="badge bg-green-100 text-green-700">Active</span>
                            @elseif($user->status === 'inactive')
                                <span class="badge bg-gray-100 text-gray-700">Inactive</span>
                            @else
                                <span class="badge bg-red-100 text-red-700">Suspended</span>
                            @endif
                        </td>
                        <td class="p-4 text-right">
                            <div class="flex justify-end space-x-3 items-center">
                                @canImpersonate
                                    @canBeImpersonated($user)
                                        <a href="{{ route('impersonate', $user->id) }}" class="text-xs font-semibold px-2 py-1 bg-ink text-white rounded hover:bg-gray-800 transition shadow-sm" title="Impersonate User">
                                            Impersonate
                                        </a>
                                    @endCanBeImpersonated
                                @endCanImpersonate

                                @can('update', $user)
                                    <a href="{{ route('admin.staff.edit', $user) }}" class="text-sage hover:text-ink text-sm font-medium transition">Edit</a>
                                @endcan
                                
                                @can('delete', $user)
                                    <form action="{{ route('admin.staff.destroy', $user) }}" method="POST" onsubmit="return confirm('Are you sure you want to disable/delete this staff member?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium transition ml-3">Delete</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-gray-500">
                            No staff found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        @if($staff->hasPages())
            <div class="p-4 border-t border-gray-200">
                {{ $staff->links() }}
            </div>
        @endif
    </div>
@endsection
