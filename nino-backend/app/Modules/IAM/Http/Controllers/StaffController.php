<?php

namespace App\Modules\IAM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Audit\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class StaffController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Display a listing of the staff members.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $query = User::staff()->with('roles');
        $summaryBaseQuery = User::staff();

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
        }

        $staff = $query->paginate(20)->withQueryString();

        $summary = [
            'total_staff' => (clone $summaryBaseQuery)->count(),
            'active' => (clone $summaryBaseQuery)->where('status', 'active')->count(),
            'suspended' => (clone $summaryBaseQuery)->where('status', 'suspended')->count(),
            'super_admins' => (clone $summaryBaseQuery)->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'Super Admin'))->count(),
        ];

        return view('admin.staff.index', compact('staff', 'summary'));
    }

    /**
     * Show the form for creating a new staff member.
     */
    public function create()
    {
        $this->authorize('create', User::class);

        // Fetch roles that the current user is allowed to assign.
        // For now, let's simplify and just exclude Super Admin if not super admin.
        $rolesQuery = Role::query();
        if (!auth()->user()->isSuperAdmin()) {
            $rolesQuery->where('name', '!=', 'Super Admin');
        }
        $roles = $rolesQuery->get();

        return view('admin.staff.create', compact('roles'));
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
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'roles' => ['array'],
            'roles.*' => ['exists:roles,name'],
            'organization_scope' => ['nullable', Rule::in(['platform', 'franchise', 'branch'])],
        ]);

        $creator = auth()->user();
        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->password = Hash::make($validated['password']);
        $user->type = 'staff';
        $user->status = $validated['status'];
        
        // Scope constraints based on creator
        if ($creator->organization_scope !== 'platform' && !$creator->isSuperAdmin()) {
            // Force inheritance of scope if not a platform admin
            $user->organization_scope = $creator->organization_scope;
            $user->organization_id = $creator->organization_id;
        } else {
            $user->organization_scope = $validated['organization_scope'] ?? 'platform';
            // TODO: Organization ID selection UI for platform admins
        }

        $user->save();

        if (!empty($validated['roles'])) {
            // Check if creator can assign these roles
            $allowedRoles = [];
            foreach ($validated['roles'] as $roleName) {
                $role = Role::findByName($roleName);
                if ($creator->can('assign', $role)) {
                    $allowedRoles[] = $roleName;
                }
            }
            $user->assignRole($allowedRoles);
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

        return redirect()->route('admin.staff.index')->with('success', 'Staff member created successfully.');
    }

    /**
     * Show the form for editing the specified staff member.
     */
    public function edit(User $staff)
    {
        $this->authorize('update', $staff);

        $rolesQuery = Role::query();
        if (!auth()->user()->isSuperAdmin()) {
            $rolesQuery->where('name', '!=', 'Super Admin');
        }
        $roles = $rolesQuery->get();

        return view('admin.staff.edit', compact('staff', 'roles'));
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
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'roles' => ['array'],
            'roles.*' => ['exists:roles,name'],
            'organization_scope' => ['nullable', Rule::in(['platform', 'franchise', 'branch'])],
        ]);

        $oldValues = $this->staffSnapshot($staff);

        $staff->name = $validated['name'];
        $staff->email = $validated['email'];
        $staff->phone = $validated['phone'] ?? null;
        $staff->status = $validated['status'];
        
        if (!empty($validated['password'])) {
            $staff->password = Hash::make($validated['password']);
        }

        $creator = auth()->user();
        if ($creator->organization_scope === 'platform' || $creator->isSuperAdmin()) {
            $staff->organization_scope = $validated['organization_scope'] ?? $staff->organization_scope;
        }

        $staff->save();

        if (isset($validated['roles'])) {
            // Check if creator can assign these roles
            $allowedRoles = [];
            foreach ($validated['roles'] as $roleName) {
                $role = Role::findByName($roleName);
                if ($creator->can('assign', $role)) {
                    $allowedRoles[] = $roleName;
                }
            }
            // Preserve Super Admin if the current editor is not Super Admin but the target is
            if ($staff->isSuperAdmin() && !$creator->isSuperAdmin()) {
                $allowedRoles[] = 'Super Admin';
            }
            $staff->syncRoles($allowedRoles);
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
        $this->auditStaffLifecycleChanges($updatedStaff, (string) ($oldValues['status'] ?? ''), (string) ($newValues['status'] ?? ''));

        return redirect()->route('admin.staff.index')->with('success', 'Staff member updated successfully.');
    }

    /**
     * Remove the specified staff member from storage.
     */
    public function destroy(User $staff)
    {
        $this->authorize('delete', $staff);
        
        $oldValues = $this->staffSnapshot($staff);
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
        $staff->loadMissing('roles');

        return [
            'id' => $staff->id,
            'name' => $staff->name,
            'email' => $staff->email,
            'phone' => $staff->phone,
            'status' => $staff->status,
            'type' => $staff->type,
            'organization_id' => $staff->organization_id,
            'organization_scope' => $staff->organization_scope,
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
}
