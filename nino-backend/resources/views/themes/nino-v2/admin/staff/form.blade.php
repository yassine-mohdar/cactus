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
    $currentStatus = old('status', $isEditing ? $staff->status : 'active');
    $scopeValue = $canManageScope
        ? old('organization_scope', $isEditing ? $staff->organization_scope : 'platform')
        : $currentUser->organization_scope;
    $selectedOrganizationId = old('organization_id', $isEditing ? $staff->organization_id : null);
    $sendSetupLink = old('send_setup_link', false);
@endphp

<div x-data="{ selectedRoles: @js(array_values($selectedRoles)), selectedPermissionOverrides: @js(array_values($selectedPermissionOverrides)), sendSetupLink: @js((bool) $sendSetupLink), scopeValue: @js($scopeValue) }" class="form-layout">
    <div class="form-main">
        <x-admin.card title="Profile information">
            <x-slot:header>
                <span class="inline-flex items-center rounded-full border border-[rgba(36,88,72,0.16)] bg-[#E7F0EA] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-[#245848]">Required</span>
            </x-slot:header>

            <div class="space-y-5">
                <p class="form-copy">Capture the operator identity details that appear in audit trails, approvals, and support interactions throughout the back office.</p>

                <div class="form-field-grid-2">
                    <x-admin.input type="text" name="name" label="Full name" :value="old('name', $isEditing ? $staff->name : null)" :error="$errors->first('name')" required />
                    <x-admin.input type="email" name="email" label="Email address" :value="old('email', $isEditing ? $staff->email : null)" :error="$errors->first('email')" required />
                </div>

                <div class="form-field-grid-2">
                    <x-admin.input type="tel" name="phone" label="Phone number" :value="old('phone', $isEditing ? $staff->phone : null)" :error="$errors->first('phone')" />
                </div>
            </div>
        </x-admin.card>

        <x-admin.card title="Access and roles">
            <x-slot:header>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-full border border-[rgba(145,133,109,0.18)] bg-[#F7F2E8] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-[#6E7D75]" x-text="selectedRoles.length ? selectedRoles.length + ' selected' : 'Choose roles'"></span>
                    @if($canManageRoleAccess)
                        <a href="{{ route('admin.staff.roles.index') }}" class="inline-flex items-center gap-1 rounded-full border border-[rgba(36,88,72,0.16)] bg-[#E7F0EA] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#245848] transition hover:bg-[#DDEBE3]">
                            <span class="material-symbols-outlined text-sm">shield_person</span>
                            Manage roles
                        </a>
                    @endif
                </div>
            </x-slot:header>

            <div class="space-y-5">
                <p class="form-copy">Assign the workflow permissions this teammate needs. Keep access narrow and role-driven so the admin stays predictable and secure.</p>

                <div class="grid gap-3 md:grid-cols-2">
                    @foreach($roles as $role)
                        <label class="block cursor-pointer">
                            <input type="checkbox" name="roles[]" value="{{ $role->name }}" x-model="selectedRoles" class="peer sr-only" @checked(in_array($role->name, $selectedRoles, true))>
                            <div class="surface-selection-active">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-[#17302A]">{{ $role->name }}</p>
                                        <p class="mt-1 text-xs leading-5 text-[#617169]">Grants {{ \Illuminate\Support\Str::lower($role->name) }} workflow access inside the operations workspace.</p>
                                    </div>
                                    <span class="material-symbols-outlined text-[20px] text-[#6E7D75] transition peer-checked:text-[#245848]">verified_user</span>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>

                @error('roles')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </x-admin.card>

        @if($canManageRoleAccess)
            <x-admin.card title="Permission overrides">
                <x-slot:header>
                    <span class="inline-flex items-center rounded-full border border-[rgba(36,88,72,0.16)] bg-[#E7F0EA] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-[#245848]" x-text="selectedPermissionOverrides.length ? selectedPermissionOverrides.length + ' direct grants' : 'Role defaults only'"></span>
                </x-slot:header>

                <div class="space-y-5">
                    <p class="form-copy">Use direct permission overrides sparingly. They add explicit grants on top of the selected roles for exception cases that should not justify a whole new role.</p>

                    <div class="space-y-5">
                        @foreach($permissionGroups as $group)
                            <div class="rounded-2xl border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] p-4">
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-[#17302A]">{{ $group['label'] }}</p>
                                        <p class="text-xs text-[#617169]">{{ $group['permissions']->count() }} permissions available</p>
                                    </div>
                                </div>

                                <div class="grid gap-3 md:grid-cols-2">
                                    @foreach($group['permissions'] as $permission)
                                        <label class="block cursor-pointer">
                                            <input type="checkbox" name="permission_overrides[]" value="{{ $permission->name }}" x-model="selectedPermissionOverrides" class="peer sr-only" @checked(in_array($permission->name, $selectedPermissionOverrides, true))>
                                            <div class="surface-selection-active">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div>
                                                        <p class="text-sm font-semibold text-[#17302A]">{{ \Illuminate\Support\Str::headline(\Illuminate\Support\Str::afterLast($permission->name, '.')) }}</p>
                                                        <p class="mt-1 font-mono text-[11px] uppercase tracking-[0.12em] text-[#617169]">{{ $permission->name }}</p>
                                                    </div>
                                                    <span class="material-symbols-outlined text-[20px] text-[#6E7D75] transition peer-checked:text-[#245848]">tune</span>
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @error('permission_overrides')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </x-admin.card>
        @endif

        <x-admin.card title="Security">
            <div class="space-y-5">
                <p class="form-copy">
                    {{ $isEditing ? 'Leave the password fields empty to keep the current credentials unchanged. Use the access setup action when you want to force a secure password refresh.' : 'Set an initial password for manual provisioning, or send a setup link so the teammate activates access securely on first use.' }}
                </p>

                @unless($isEditing)
                    <label class="flex items-start gap-3 rounded-2xl border border-[rgba(36,88,72,0.16)] bg-[#F6FAF7] px-4 py-4">
                        <input type="checkbox" name="send_setup_link" value="1" x-model="sendSetupLink" class="mt-1 rounded border-[rgba(36,88,72,0.24)] text-[#245848] focus:ring-[#245848]">
                        <span class="space-y-1">
                            <span class="block text-sm font-semibold text-[#17302A]">Send access setup link instead of setting a password manually</span>
                            <span class="block text-sm leading-6 text-[#617169]">A secure signed link will be emailed so the staff member can set their own password. If enabled, the password fields below become optional.</span>
                        </span>
                    </label>
                @endunless

                <div class="form-field-grid-2">
                    <x-admin.input type="password" name="password" :label="$isEditing ? 'New password' : 'Initial password'" :error="$errors->first('password')" />
                    <x-admin.input type="password" name="password_confirmation" :label="$isEditing ? 'Confirm new password' : 'Confirm password'" />
                </div>
            </div>
        </x-admin.card>
    </div>

    <div class="form-sidebar">
        <x-admin.card title="Account state">
            <div class="space-y-5">
                <p class="form-copy">Define how broadly this teammate can operate across organizations and whether the account is ready for active use.</p>

                <x-admin.select name="status" label="Account status" :error="$errors->first('status')">
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($currentStatus === $value)>{{ $label }}</option>
                    @endforeach
                </x-admin.select>

                @if($canManageScope)
                    <x-admin.select name="organization_scope" label="Organization scope" :error="$errors->first('organization_scope')" x-model="scopeValue">
                        @foreach($allowedScopes as $allowedScope)
                            <option value="{{ $allowedScope }}" @selected($scopeValue === $allowedScope)>{{ match($allowedScope) {
                                'platform' => 'Platform (global)',
                                'franchise' => 'Franchise view',
                                'branch' => 'Branch view',
                                'own' => 'Own records only',
                                default => \Illuminate\Support\Str::headline($allowedScope),
                            } }}</option>
                        @endforeach
                    </x-admin.select>

                    @if(($organizationOptions ?? collect())->isNotEmpty())
                        <div x-show="scopeValue !== 'platform'" x-cloak>
                            <x-admin.select name="organization_id" label="Organization unit" :error="$errors->first('organization_id')">
                                <option value="">Select an organization unit</option>
                                @foreach($organizationOptions as $organizationOption)
                                    <option value="{{ $organizationOption->id ?? $organizationOption['id'] }}" @selected((string) $selectedOrganizationId === (string) ($organizationOption->id ?? $organizationOption['id']))>
                                        {{ $organizationOption->label ?? $organizationOption['label'] }}
                                    </option>
                                @endforeach
                            </x-admin.select>
                        </div>
                    @endif

                    <p class="form-copy">Scope determines the data and actions available to the staff account across the back office.</p>
                @else
                    <div class="form-note">
                        This account will inherit your current organization scope:
                        <span class="font-semibold text-[#17302A]">{{ \Illuminate\Support\Str::headline($currentUser->organization_scope) }}</span>.
                    </div>
                @endif
            </div>
        </x-admin.card>

        <x-admin.card title="Ready to save">
            <div class="meta-list">
                <div class="meta-row">
                    <span class="meta-label">Mode</span>
                    <span class="meta-value">{{ $isEditing ? 'Updating staff profile' : 'Inviting new staff profile' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Status</span>
                    <span class="meta-value">{{ $statusOptions[$currentStatus] ?? \Illuminate\Support\Str::headline($currentStatus) }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Scope</span>
                    <span class="meta-value">{{ \Illuminate\Support\Str::headline($scopeValue) }}</span>
                </div>
                @if($selectedOrganizationId)
                    <div class="meta-row">
                        <span class="meta-label">Organization</span>
                        <span class="meta-value">
                            @php
                                $selectedOrganization = collect($organizationOptions ?? [])->first(fn ($organizationOption) => (string) ($organizationOption->id ?? $organizationOption['id']) === (string) $selectedOrganizationId);
                            @endphp
                            {{ $selectedOrganization->label ?? $selectedOrganization['label'] ?? 'Selected unit' }}
                        </span>
                    </div>
                @endif
                <div class="meta-row">
                    <span class="meta-label">Assigned roles</span>
                    <span class="meta-value" x-text="selectedRoles.length ? selectedRoles.length + ' roles' : 'No roles yet'">{{ count($selectedRoles) }} roles</span>
                </div>
                @if($canManageRoleAccess)
                    <div class="meta-row">
                        <span class="meta-label">Direct grants</span>
                        <span class="meta-value" x-text="selectedPermissionOverrides.length ? selectedPermissionOverrides.length + ' permissions' : 'No overrides'">{{ count($selectedPermissionOverrides) }} permissions</span>
                    </div>
                @endif
            </div>

            <x-slot:footer>
                <div class="form-actions">
                    <x-admin.button href="{{ route('admin.staff.index') }}" variant="secondary">Cancel</x-admin.button>
                    <x-admin.button type="submit" variant="primary">
                        {{ $isEditing ? 'Update Staff Member' : 'Create Staff Member' }}
                    </x-admin.button>
                </div>
            </x-slot:footer>
        </x-admin.card>
    </div>
</div>
