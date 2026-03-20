@php
    $isEditing = isset($staff);
    $currentUser = auth()->user();
    $canManageScope = $currentUser->organization_scope === 'platform' || $currentUser->isSuperAdmin();
    $canManageRoleAccess = $currentUser->can('viewAny', \Spatie\Permission\Models\Role::class);
    $selectedRoles = old('roles', $isEditing ? $staff->roles->pluck('name')->all() : []);
    $selectedPermissionOverrides = old('permission_overrides', $isEditing ? $staff->getDirectPermissions()->pluck('name')->all() : []);
    $statusOptions = $isEditing
        ? ['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended']
        : ['active' => 'Active', 'inactive' => 'Inactive (Needs Activation)'];
    $allowedScopes = $allowedScopes ?? ['platform', 'franchise', 'branch', 'own'];
    $scopeValue = $canManageScope
        ? old('organization_scope', $isEditing ? $staff->organization_scope : 'platform')
        : $currentUser->organization_scope;
    $selectedOrganizationId = old('organization_id', $isEditing ? $staff->organization_id : null);
@endphp

<div class="space-y-6">
    <div>
        <h3 class="font-bold text-lg border-b border-gray-100 pb-2 mb-4">Profile Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                <input type="text" name="name" value="{{ old('name', $isEditing ? $staff->name : null) }}" required class="input-field" placeholder="Jane Doe">
                @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                <input type="email" name="email" value="{{ old('email', $isEditing ? $staff->email : null) }}" required class="input-field" placeholder="jane@ninoworld.com">
                @error('email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                <input type="tel" name="phone" value="{{ old('phone', $isEditing ? $staff->phone : null) }}" class="input-field" placeholder="+212 6...">
                @error('phone') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Account Status *</label>
                <select name="status" required class="input-field">
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $isEditing ? $staff->status : 'active') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    <div class="pt-4 border-t border-gray-100">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-2">
            <h3 class="font-bold text-lg">Access & Roles</h3>
            @if($canManageRoleAccess)
                <a href="{{ route('admin.staff.roles.index') }}" class="text-xs font-semibold uppercase tracking-[0.18em] text-sage hover:text-sage-dark">Manage Roles</a>
            @endif
        </div>

        <div class="mb-5">
            <label class="block text-sm font-medium text-gray-700 mb-2">Assign Roles</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                @foreach($roles as $role)
                    <label class="flex items-center space-x-2 p-2 rounded hover:bg-gray-50 border border-transparent hover:border-gray-200 transition cursor-pointer">
                        <input type="checkbox" name="roles[]" value="{{ $role->name }}" class="rounded text-sage focus:ring-sage" {{ in_array($role->name, $selectedRoles, true) ? 'checked' : '' }}>
                        <span class="text-sm font-medium text-ink">{{ $role->name }}</span>
                    </label>
                @endforeach
            </div>
            @error('roles') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>

        @if($canManageScope)
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Organization Scope</label>
                <select name="organization_scope" class="input-field">
                    @foreach($allowedScopes as $allowedScope)
                        <option value="{{ $allowedScope }}" @selected($scopeValue === $allowedScope)>{{ match($allowedScope) {
                            'platform' => 'Platform (Global)',
                            'franchise' => 'Franchise View',
                            'branch' => 'Branch View',
                            'own' => 'Own Records Only',
                            default => \Illuminate\Support\Str::headline($allowedScope),
                        } }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Limits what data this user can see and edit.</p>
                @error('organization_scope') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            @if(($organizationOptions ?? collect())->isNotEmpty() && $scopeValue !== 'platform')
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Organization Unit</label>
                    <select name="organization_id" class="input-field">
                        <option value="">Select an organization unit</option>
                        @foreach($organizationOptions as $organizationOption)
                            <option value="{{ $organizationOption->id ?? $organizationOption['id'] }}" @selected((string) $selectedOrganizationId === (string) ($organizationOption->id ?? $organizationOption['id']))>
                                {{ $organizationOption->label ?? $organizationOption['label'] }}
                            </option>
                        @endforeach
                    </select>
                    @error('organization_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
            @endif
        @else
            <div class="p-3 bg-sage/5 rounded-lg border border-sage/20 mb-5">
                <p class="text-sm text-sage">This user will automatically inherit your organization scope ({{ ucfirst($currentUser->organization_scope) }}).</p>
            </div>
        @endif

        @if($canManageRoleAccess)
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <div class="mb-4">
                    <h4 class="font-semibold text-ink">Permission Overrides</h4>
                    <p class="mt-1 text-sm text-gray-500">Direct permission overrides add explicit grants beyond the selected roles.</p>
                </div>
                <div class="space-y-4">
                    @foreach($permissionGroups as $group)
                        <div>
                            <p class="mb-2 text-xs font-bold uppercase tracking-widest text-gray-500">{{ $group['label'] }}</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                @foreach($group['permissions'] as $permission)
                                    <label class="flex items-center gap-2 rounded border border-gray-200 bg-white px-3 py-2 text-sm text-ink">
                                        <input type="checkbox" name="permission_overrides[]" value="{{ $permission->name }}" class="rounded text-sage focus:ring-sage" {{ in_array($permission->name, $selectedPermissionOverrides, true) ? 'checked' : '' }}>
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
                @error('permission_overrides') <span class="text-red-500 text-xs mt-2 block">{{ $message }}</span> @enderror
            </div>
        @endif
    </div>

    <div class="pt-4 border-t border-gray-100">
        <h3 class="font-bold text-lg border-b border-gray-100 pb-2 mb-4">Security</h3>
        <p class="text-sm text-gray-500 mb-4">
            {{ $isEditing ? 'Leave password fields empty unless you want to change the user password. Use the access setup action if you need to send a fresh setup link.' : 'Set an initial password, or send a setup link so the staff member creates their own password securely.' }}
        </p>

        @unless($isEditing)
            <label class="flex items-start gap-3 rounded-lg border border-sage/20 bg-sage/5 px-4 py-3 mb-4">
                <input type="checkbox" name="send_setup_link" value="1" class="mt-1 rounded text-sage focus:ring-sage" {{ old('send_setup_link') ? 'checked' : '' }}>
                <span class="text-sm text-ink">
                    <span class="block font-semibold text-sage">Send access setup link</span>
                    Email a secure setup link instead of relying on a manually shared password.
                </span>
            </label>
        @endunless

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $isEditing ? 'New Password' : 'Initial Password' }}{{ $isEditing ? '' : ' *' }}</label>
                <input type="password" name="password" class="input-field">
                @error('password') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $isEditing ? 'Confirm New Password' : 'Confirm Password' }}</label>
                <input type="password" name="password_confirmation" class="input-field">
            </div>
        </div>
    </div>

    <div class="pt-6 flex items-center justify-end space-x-3">
        <a href="{{ route('admin.staff.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition border border-gray-300">
            Cancel
        </a>
        <button type="submit" class="btn-primary">
            {{ $isEditing ? 'Update Staff Member' : 'Create Staff Member' }}
        </button>
    </div>
</div>
