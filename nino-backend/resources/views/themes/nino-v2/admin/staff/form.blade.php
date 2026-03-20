@php
    $isEditing = isset($staff);
    $currentUser = auth()->user();
    $canManageScope = $currentUser->organization_scope === 'platform' || $currentUser->isSuperAdmin();
    $selectedRoles = old('roles', $isEditing ? $staff->roles->pluck('name')->all() : []);
    $statusOptions = $isEditing
        ? ['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended']
        : ['active' => 'Active', 'inactive' => 'Inactive (Needs Activation)'];
    $currentStatus = old('status', $isEditing ? $staff->status : 'active');
    $scopeValue = $canManageScope
        ? old('organization_scope', $isEditing ? $staff->organization_scope : 'platform')
        : $currentUser->organization_scope;
@endphp

<div x-data="{ selectedRoles: @js(array_values($selectedRoles)) }" class="form-layout">
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
                <span class="inline-flex items-center rounded-full border border-[rgba(145,133,109,0.18)] bg-[#F7F2E8] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-[#6E7D75]" x-text="selectedRoles.length ? selectedRoles.length + ' selected' : 'Choose roles'"></span>
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

        <x-admin.card title="Security">
            <div class="space-y-5">
                <p class="form-copy">
                    {{ $isEditing ? 'Leave the password fields empty to keep the current credentials unchanged.' : 'Set an initial password so the account can be activated securely on first use.' }}
                </p>

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
                    <x-admin.select name="organization_scope" label="Organization scope" :error="$errors->first('organization_scope')">
                        <option value="platform" @selected($scopeValue === 'platform')>Platform (global)</option>
                        <option value="franchise" @selected($scopeValue === 'franchise')>Franchise view</option>
                        <option value="branch" @selected($scopeValue === 'branch')>Branch view</option>
                    </x-admin.select>

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
                <div class="meta-row">
                    <span class="meta-label">Assigned roles</span>
                    <span class="meta-value" x-text="selectedRoles.length ? selectedRoles.length + ' roles' : 'No roles yet'">{{ count($selectedRoles) }} roles</span>
                </div>
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
