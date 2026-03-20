<?php

namespace App\Modules\IAM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\IAM\Models\Role;
use App\Modules\IAM\Services\RoleAccessService;
use App\Modules\IAM\Services\SessionManagementService;
use App\Modules\IAM\Services\StaffAccessSetupService;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Services\OrganizationAssignmentService;
use App\Modules\Settings\Services\PasswordPolicyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly RoleAccessService $roleAccess,
        private readonly SessionManagementService $sessions,
        private readonly StaffAccessSetupService $staffAccessSetup,
        private readonly OrganizationAssignmentService $organizationAssignments,
        private readonly PasswordPolicyService $passwordPolicy,
    ) {}

    /**
     * Display a listing of the staff members.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $query = User::staff()->with(['roles', 'organization.parent']);
        $summaryBaseQuery = User::staff()->with('organization');

        if ($request->filled('search')) {
            $search = trim((string) $request->string('search'));

            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('roles', function ($roleQuery) use ($search) {
                        $roleQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('scope')) {
            $query->where('organization_scope', $request->string('scope'));
        }

        if ($request->filled('organization_id')) {
            $query->where('organization_id', (int) $request->integer('organization_id'));
        }

        // Apply scoping automatically: if platform/super admin, see all.
        // If franchise/branch, only see users in their scope.
        $user = auth()->user();
        if ($user->organization_scope === 'franchise') {
            $query->where(function ($q) use ($user) {
                $q->where('organization_id', $user->organization_id)
                  ->orWhereHas('organization', function ($q2) use ($user) {
                      $q2->where('parent_id', $user->organization_id);
                  });
            });

            $summaryBaseQuery->where(function ($q) use ($user) {
                $q->where('organization_id', $user->organization_id)
                  ->orWhereHas('organization', function ($q2) use ($user) {
                      $q2->where('parent_id', $user->organization_id);
                  });
            });
        } elseif ($user->organization_scope === 'branch') {
            $query->where('organization_id', $user->organization_id);
            $summaryBaseQuery->where('organization_id', $user->organization_id);
        } elseif ($user->organization_scope === 'own') {
            $query->whereKey($user->getKey());
            $summaryBaseQuery->whereKey($user->getKey());
        }

        $staff = $query->latest('id')->paginate(20)->withQueryString();

        $summary = [
            'total_staff' => (clone $summaryBaseQuery)->count(),
            'active' => (clone $summaryBaseQuery)->where('status', 'active')->count(),
            'suspended' => (clone $summaryBaseQuery)->where('status', 'suspended')->count(),
            'super_admins' => (clone $summaryBaseQuery)->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'Super Admin'))->count(),
        ];

        $organizationOptions = ($request->filled('scope')
            ? $this->organizationAssignments->availableOrganizationsFor($user, (string) $request->string('scope'))
            : $this->organizationAssignments->assignableOrganizationsFor($user))
            ->map(fn (Organization $organization) => [
                'id' => $organization->id,
                'label' => $this->organizationLabel($organization),
            ]);

        return view('admin.staff.index', compact('staff', 'summary', 'organizationOptions'));
    }

    /**
     * Show the form for creating a new staff member.
     */
    public function create()
    {
        $this->authorize('create', User::class);

        return view('admin.staff.create', [
            'roles' => $this->roleAccess->assignableRolesFor(auth()->user()),
            'permissionGroups' => $this->roleAccess->permissionGroupsFor(auth()->user()),
            'allowedScopes' => $this->organizationAssignments->allowedScopesFor(auth()->user()),
            'organizationOptions' => $this->organizationAssignments->assignableOrganizationsFor(auth()->user())
                ->map(fn (Organization $organization) => [
                    'id' => $organization->id,
                    'label' => $this->organizationLabel($organization),
                ]),
        ]);
    }

    /**
     * Store a newly created staff member in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => array_merge(
                ['required_without:send_setup_link'],
                $this->passwordPolicy->optionalRules(),
            ),
            'send_setup_link' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'roles' => ['array'],
            'roles.*' => ['exists:roles,name'],
            'permission_overrides' => ['array'],
            'permission_overrides.*' => ['exists:permissions,name'],
            'organization_scope' => ['nullable', Rule::in(['platform', 'franchise', 'branch', 'own'])],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
        ]);

        $creator = auth()->user();
        $sendSetupLink = $request->boolean('send_setup_link');
        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->password = Hash::make($sendSetupLink ? Str::random(40) : (string) $validated['password']);
        $user->type = 'staff';
        $user->status = $validated['status'];
        
        $assignment = $this->organizationAssignments->resolveAssignment(
            $creator,
            $validated['organization_scope'] ?? null,
            isset($validated['organization_id']) ? (int) $validated['organization_id'] : null,
        );
        $user->organization_scope = $assignment['scope'];

        $user->save();
        $this->organizationAssignments->assignPrimaryOrganization($user, $assignment['organization']);

        if (!empty($validated['roles'])) {
            $allowedRoles = [];
            foreach ($validated['roles'] as $roleName) {
                $role = Role::findByName($roleName);
                if ($this->roleAccess->canAssignRole($creator, $role)) {
                    $allowedRoles[] = $roleName;
                }
            }
            $user->assignRole($allowedRoles);
        }

        $directPermissions = [];
        if ($this->roleAccess->userCanManageRoles($creator)) {
            $directPermissions = $this->roleAccess->syncDirectPermissions(
                $user,
                $validated['permission_overrides'] ?? [],
                $creator,
            );
        }

        $createdSnapshot = $this->staffSnapshot($user->fresh(['roles']));

        $this->audit->log(
            action: 'staff.created',
            target: $user,
            newValues: $createdSnapshot,
            notes: 'Staff created manually',
            context: [
                'module' => 'iam',
                'source' => 'staff_controller',
            ],
        );

        $this->auditRoleChanges($user, [], $createdSnapshot['roles'] ?? []);
        $this->auditPermissionOverrideChanges($user, [], $directPermissions);

        if ($sendSetupLink) {
            $this->staffAccessSetup->dispatchSetupLink($user, 'staff_invite');

            $this->audit->log(
                action: 'staff.access_setup_link.sent',
                target: $user,
                newValues: [
                    'email' => $user->email,
                    'status' => $user->status,
                    'source' => 'staff_invite',
                ],
                notes: 'Staff access setup link sent on creation',
                context: [
                    'module' => 'iam',
                    'source' => 'staff_controller',
                ],
            );
        }

        return redirect()
            ->route('admin.staff.index')
            ->with('success', $sendSetupLink
                ? 'Staff member created and access setup link sent successfully.'
                : 'Staff member created successfully.');
    }

    /**
     * Show the form for editing the specified staff member.
     */
    public function edit(User $staff)
    {
        $this->authorize('update', $staff);

        return view('admin.staff.edit', [
            'staff' => $staff->load('roles', 'permissions'),
            'roles' => $this->roleAccess->assignableRolesFor(auth()->user()),
            'permissionGroups' => $this->roleAccess->permissionGroupsFor(auth()->user()),
            'allowedScopes' => $this->organizationAssignments->allowedScopesFor(auth()->user()),
            'organizationOptions' => $this->organizationAssignments->assignableOrganizationsFor(auth()->user())
                ->map(fn (Organization $organization) => [
                    'id' => $organization->id,
                    'label' => $this->organizationLabel($organization),
                ]),
        ]);
    }

    /**
     * Update the specified staff member in storage.
     */
    public function update(Request $request, User $staff)
    {
        $this->authorize('update', $staff);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($staff->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => $this->passwordPolicy->optionalRules(),
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'roles' => ['array'],
            'roles.*' => ['exists:roles,name'],
            'permission_overrides' => ['array'],
            'permission_overrides.*' => ['exists:permissions,name'],
            'organization_scope' => ['nullable', Rule::in(['platform', 'franchise', 'branch', 'own'])],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
        ]);

        $oldValues = $this->staffSnapshot($staff);
        $oldDirectPermissions = $this->directPermissionNames($staff);

        $staff->name = $validated['name'];
        $staff->email = $validated['email'];
        $staff->phone = $validated['phone'] ?? null;
        $staff->status = $validated['status'];
        
        if (!empty($validated['password'])) {
            $staff->password = Hash::make($validated['password']);
        }

        $creator = auth()->user();
        $assignment = $this->organizationAssignments->resolveAssignment(
            $creator,
            $validated['organization_scope'] ?? $staff->organization_scope,
            array_key_exists('organization_id', $validated)
                ? ($validated['organization_id'] !== null ? (int) $validated['organization_id'] : null)
                : $staff->organization_id,
        );
        $staff->organization_scope = $assignment['scope'];

        $staff->save();
        $this->organizationAssignments->assignPrimaryOrganization($staff, $assignment['organization']);

        if (isset($validated['roles'])) {
            $allowedRoles = [];
            foreach ($validated['roles'] as $roleName) {
                $role = Role::findByName($roleName);
                if ($this->roleAccess->canAssignRole($creator, $role)) {
                    $allowedRoles[] = $roleName;
                }
            }
            // Preserve Super Admin if the current editor is not Super Admin but the target is
            if ($staff->isSuperAdmin() && !$creator->isSuperAdmin()) {
                $allowedRoles[] = 'Super Admin';
            }
            $staff->syncRoles($allowedRoles);
        }

        $newDirectPermissions = $oldDirectPermissions;
        if ($this->roleAccess->userCanManageRoles($creator)) {
            $newDirectPermissions = $this->roleAccess->syncDirectPermissions(
                $staff,
                $validated['permission_overrides'] ?? [],
                $creator,
            );
        }

        $updatedStaff = $staff->fresh(['roles']);
        $newValues = $this->staffSnapshot($updatedStaff);

        $this->audit->log(
            action: 'staff.updated',
            target: $staff,
            oldValues: $oldValues,
            newValues: $newValues,
            notes: 'Staff updated manually',
            context: [
                'module' => 'iam',
                'source' => 'staff_controller',
            ],
        );

        $this->auditRoleChanges($updatedStaff, $oldValues['roles'] ?? [], $newValues['roles'] ?? []);
        $this->auditPermissionOverrideChanges($updatedStaff, $oldDirectPermissions, $newDirectPermissions);
        $this->auditStaffLifecycleChanges($updatedStaff, (string) ($oldValues['status'] ?? ''), (string) ($newValues['status'] ?? ''));
        $this->auditSensitiveAccountChanges(
            $updatedStaff,
            $oldValues,
            $newValues,
            ! empty($validated['password']),
        );
        $this->revokeSessionsIfNeeded(
            actor: $creator,
            staff: $updatedStaff,
            oldStatus: (string) ($oldValues['status'] ?? ''),
            passwordChanged: ! empty($validated['password']),
            source: 'staff_controller',
        );

        return redirect()->route('admin.staff.index')->with('success', 'Staff member updated successfully.');
    }

    /**
     * Remove the specified staff member from storage.
     */
    public function destroy(User $staff)
    {
        $this->authorize('delete', $staff);

        $oldValues = $this->staffSnapshot($staff);
        $revokedSessions = $this->sessions->revokeUserAccess($staff);
        $this->auditSessionRevocation(
            staff: $staff,
            source: 'staff_controller',
            revokedSessions: $revokedSessions,
            reason: 'staff_deleted',
            keptCurrentSession: false,
        );
        $staff->delete();

        $this->audit->log(
            action: 'staff.deleted',
            target: $staff,
            oldValues: $oldValues,
            notes: 'Staff deleted manually',
            context: [
                'module' => 'iam',
                'source' => 'staff_controller',
                'deleted_staff_id' => $staff->id,
            ],
        );

        return redirect()->route('admin.staff.index')->with('success', 'Staff member deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function staffSnapshot(User $staff): array
    {
        $staff->loadMissing('roles', 'organization.parent');

        return [
            'id' => $staff->id,
            'name' => $staff->name,
            'email' => $staff->email,
            'phone' => $staff->phone,
            'status' => $staff->status,
            'type' => $staff->type,
            'organization_id' => $staff->organization_id,
            'organization_scope' => $staff->organization_scope,
            'organization_label' => $staff->organization ? $this->organizationLabel($staff->organization) : null,
            'roles' => $staff->roles
                ->pluck('name')
                ->sort()
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<int, string>  $oldRoles
     * @param  array<int, string>  $newRoles
     */
    private function auditRoleChanges(User $staff, array $oldRoles, array $newRoles): void
    {
        $oldRoles = collect($oldRoles)->filter()->sort()->values()->all();
        $newRoles = collect($newRoles)->filter()->sort()->values()->all();

        if ($oldRoles === $newRoles) {
            return;
        }

        $this->audit->log(
            action: 'staff.roles.changed',
            target: $staff,
            oldValues: ['roles' => $oldRoles],
            newValues: ['roles' => $newRoles],
            notes: 'Staff roles changed',
            context: [
                'module' => 'iam',
                'source' => 'staff_controller',
            ],
        );
    }

    /**
     * @param  array<int, string>  $oldPermissions
     * @param  array<int, string>  $newPermissions
     */
    private function auditPermissionOverrideChanges(User $staff, array $oldPermissions, array $newPermissions): void
    {
        $oldPermissions = collect($oldPermissions)->filter()->sort()->values()->all();
        $newPermissions = collect($newPermissions)->filter()->sort()->values()->all();

        if ($oldPermissions === $newPermissions) {
            return;
        }

        $this->audit->log(
            action: 'staff.permission_overrides.changed',
            target: $staff,
            oldValues: ['permission_overrides' => $oldPermissions],
            newValues: ['permission_overrides' => $newPermissions],
            notes: 'Staff direct permission overrides changed',
            context: [
                'module' => 'iam',
                'source' => 'staff_controller',
            ],
        );
    }

    /**
     * @return array<int, string>
     */
    private function directPermissionNames(User $staff): array
    {
        return $staff->getDirectPermissions()
            ->pluck('name')
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private function auditSensitiveAccountChanges(
        User $staff,
        array $oldValues,
        array $newValues,
        bool $passwordChanged,
    ): void {
        $trackedFields = [
            'email',
            'status',
            'organization_scope',
            'organization_id',
            'organization_label',
        ];

        $oldSensitive = [];
        $newSensitive = [];

        foreach ($trackedFields as $field) {
            $oldValue = $oldValues[$field] ?? null;
            $newValue = $newValues[$field] ?? null;

            if ($oldValue === $newValue) {
                continue;
            }

            $oldSensitive[$field] = $oldValue;
            $newSensitive[$field] = $newValue;
        }

        if ($oldSensitive !== [] || $newSensitive !== []) {
            $this->audit->log(
                action: 'staff.account_access.changed',
                target: $staff,
                oldValues: $oldSensitive,
                newValues: $newSensitive,
                notes: 'Sensitive staff account fields changed',
                context: [
                    'module' => 'iam',
                    'source' => 'staff_controller',
                ],
            );
        }

        if (! $passwordChanged) {
            return;
        }

        $this->audit->log(
            action: 'staff.password.changed',
            target: $staff,
            newValues: [
                'credentials_rotated' => true,
            ],
            notes: 'Staff password changed manually',
            context: [
                'module' => 'iam',
                'source' => 'staff_controller',
            ],
        );
    }

    private function auditStaffLifecycleChanges(User $staff, string $oldStatus, string $newStatus): void
    {
        if ($oldStatus === $newStatus) {
            return;
        }

        if ($oldStatus === 'active' && in_array($newStatus, ['inactive', 'suspended'], true)) {
            $this->audit->log(
                action: 'staff.deactivated',
                target: $staff,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $newStatus],
                notes: 'Staff deactivated',
                context: [
                    'module' => 'iam',
                    'source' => 'staff_controller',
                ],
            );
        }
    }

    private function revokeSessionsIfNeeded(
        User $actor,
        User $staff,
        string $oldStatus,
        bool $passwordChanged,
        string $source,
    ): void {
        $statusForcedLogout = $oldStatus !== $staff->status
            && in_array($staff->status, ['inactive', 'suspended'], true);

        if (! $passwordChanged && ! $statusForcedLogout) {
            return;
        }

        $keepCurrentSession = $actor->is($staff) && ! $statusForcedLogout;
        $revokedSessions = $this->sessions->revokeUserAccess(
            $staff,
            $keepCurrentSession ? request()->session()?->getId() : null,
        );

        $this->auditSessionRevocation(
            staff: $staff,
            source: $source,
            revokedSessions: $revokedSessions,
            reason: $statusForcedLogout ? 'status_changed' : 'password_changed',
            keptCurrentSession: $keepCurrentSession,
        );
    }

    private function auditSessionRevocation(
        User $staff,
        string $source,
        int $revokedSessions,
        string $reason,
        bool $keptCurrentSession,
    ): void {
        $this->audit->log(
            action: 'staff.sessions.revoked',
            target: $staff,
            newValues: [
                'revoked_sessions' => $revokedSessions,
                'reason' => $reason,
                'kept_current_session' => $keptCurrentSession,
            ],
            notes: 'Staff sessions revoked',
            context: [
                'module' => 'iam',
                'source' => $source,
            ],
        );
    }

    private function organizationLabel(Organization $organization): string
    {
        $prefix = match ($organization->type) {
            Organization::TYPE_PLATFORM => 'Platform',
            Organization::TYPE_FRANCHISE => 'Franchise',
            Organization::TYPE_BRANCH => 'Branch',
            default => ucfirst($organization->type),
        };

        return "{$prefix}: {$organization->name}";
    }
}
