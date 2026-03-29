@php
    $isEditing = isset($role);
    $selectedPermissions = old('permissions', $isEditing ? $role->permissions->pluck('name')->all() : []);
@endphp

<div class="form-layout">
    <div class="form-main">
        <x-admin.card title="Role profile">
            <div class="space-y-5">
                <p class="form-copy">Define a reusable access profile that can be assigned to staff accounts without relying on one-off permission edits.</p>

                @if(($isProtectedRole ?? false) === true)
                    <input type="hidden" name="name" value="{{ old('name', $role->name) }}">
                    <div class="form-note">
                        <span class="font-semibold text-[#17302A]">{{ $role->name }}</span> is a protected system role. Its name stays fixed, but its permission set can still be reviewed here by authorized staff.
                    </div>
                @else
                    <x-admin.input type="text" name="name" label="Role name" :value="old('name', $isEditing ? $role->name : null)" :error="$errors->first('name')" required />
                @endif
            </div>
        </x-admin.card>

        <x-admin.card title="Permissions">
            <x-slot:header>
                <span class="inline-flex items-center rounded-full border border-[rgba(145,133,109,0.18)] bg-[#F7F2E8] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-[#6E7D75]">
                    {{ count($selectedPermissions) }} selected
                </span>
            </x-slot:header>

            <div class="space-y-5">
                <p class="form-copy">Permissions are grouped by operational area. Custom roles should stay narrow and explicit.</p>

                @foreach($permissionGroups as $group)
                    <div class="rounded-2xl border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] p-4">
                        <div class="mb-3">
                            <p class="text-sm font-semibold text-[#17302A]">{{ $group['label'] }}</p>
                            <p class="text-xs text-[#617169]">{{ $group['permissions']->count() }} permissions</p>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            @foreach($group['permissions'] as $permission)
                                <label class="block cursor-pointer">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" class="peer sr-only" @checked(in_array($permission->name, $selectedPermissions, true))>
                                    <div class="surface-selection-active">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold text-[#17302A]">{{ \Illuminate\Support\Str::headline(\Illuminate\Support\Str::afterLast($permission->name, '.')) }}</p>
                                                <p class="mt-1 font-mono text-[11px] uppercase tracking-[0.12em] text-[#617169]">{{ $permission->name }}</p>
                                            </div>
                                            <span class="material-symbols-outlined text-[20px] text-[#6E7D75] transition peer-checked:text-[#245848]">done</span>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                @error('permissions')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </x-admin.card>
    </div>

    <div class="form-sidebar">
        <x-admin.card title="Save role">
            <div class="meta-list">
                <div class="meta-row">
                    <span class="meta-label">Mode</span>
                    <span class="meta-value">{{ $isEditing ? 'Updating role' : 'Creating role' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Type</span>
                    <span class="meta-value">{{ ($isProtectedRole ?? false) ? 'Protected system role' : 'Custom role' }}</span>
                </div>
            </div>

            <x-slot:footer>
                <div class="form-actions">
                    <x-admin.button href="{{ route('admin.staff.roles.index') }}" variant="secondary">Cancel</x-admin.button>
                    <x-admin.button type="submit" variant="primary">{{ $isEditing ? 'Update Role' : 'Create Role' }}</x-admin.button>
                </div>
            </x-slot:footer>
        </x-admin.card>
    </div>
</div>
