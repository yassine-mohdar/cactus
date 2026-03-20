@php
    $isEditing = isset($role);
    $selectedPermissions = old('permissions', $isEditing ? $role->permissions->pluck('name')->all() : []);
@endphp

<div class="space-y-6">
    <div>
        <h3 class="font-bold text-lg border-b border-gray-100 pb-2 mb-4">Role Details</h3>

        @if(($isProtectedRole ?? false) === true)
            <input type="hidden" name="name" value="{{ old('name', $role->name) }}">
            <div class="rounded-lg border border-sage/20 bg-sage/5 px-4 py-3 text-sm text-sage">
                <span class="font-semibold">{{ $role->name }}</span> is a protected system role. The name stays fixed, but authorized staff can still review its permissions here.
            </div>
        @else
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role Name *</label>
                <input type="text" name="name" value="{{ old('name', $isEditing ? $role->name : null) }}" required class="input-field">
                @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
        @endif
    </div>

    <div class="pt-4 border-t border-gray-100">
        <h3 class="font-bold text-lg border-b border-gray-100 pb-2 mb-4">Permissions</h3>
        <div class="space-y-4">
            @foreach($permissionGroups as $group)
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <p class="mb-3 text-xs font-bold uppercase tracking-widest text-gray-500">{{ $group['label'] }}</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        @foreach($group['permissions'] as $permission)
                            <label class="flex items-center gap-2 rounded border border-gray-200 bg-white px-3 py-2 text-sm text-ink">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" class="rounded text-sage focus:ring-sage" {{ in_array($permission->name, $selectedPermissions, true) ? 'checked' : '' }}>
                                <span>
                                    <span class="block font-medium">{{ \Illuminate\Support\Str::headline(\Illuminate\Support\Str::afterLast($permission->name, '.')) }}</span>
                                    <span class="block text-[11px] font-mono uppercase tracking-widest text-gray-500">{{ $permission->name }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        @error('permissions') <span class="text-red-500 text-xs mt-2 block">{{ $message }}</span> @enderror
    </div>

    <div class="pt-6 flex items-center justify-end space-x-3">
        <a href="{{ route('admin.staff.roles.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition border border-gray-300">
            Cancel
        </a>
        <button type="submit" class="btn-primary">
            {{ $isEditing ? 'Update Role' : 'Create Role' }}
        </button>
    </div>
</div>
