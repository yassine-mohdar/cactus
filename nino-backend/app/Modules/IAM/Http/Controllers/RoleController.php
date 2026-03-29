<?php

namespace App\Modules\IAM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\IAM\Models\Role;
use App\Modules\IAM\Services\RoleAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleAccessService $roleAccess,
        private readonly AuditLogger $audit,
    ) {}

    public function index()
    {
        $this->authorize('viewAny', Role::class);

        $roles = $this->roleAccess->manageableRolesFor(auth()->user());

        return view('admin.staff.roles.index', [
            'roles' => $roles,
            'protectedRoleNames' => $this->roleAccess->systemRoleNames(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Role::class);

        return view('admin.staff.roles.create', [
            'permissionGroups' => $this->roleAccess->permissionGroupsFor(auth()->user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        $role = Role::create([
            'name' => trim((string) $validated['name']),
            'guard_name' => 'web',
        ]);

        $permissions = $this->roleAccess->syncRolePermissions(
            $role,
            $validated['permissions'] ?? [],
            auth()->user(),
        );

        $this->audit->log(
            action: 'role.created',
            target: $role,
            newValues: $this->roleSnapshot($role, $permissions),
            notes: 'Custom role created',
            context: [
                'module' => 'iam',
                'source' => 'role_controller',
            ],
        );

        return redirect()
            ->route('admin.staff.roles.index')
            ->with('success', 'Custom role created successfully.');
    }

    public function edit(Role $role)
    {
        $this->authorize('update', $role);

        return view('admin.staff.roles.edit', [
            'role' => $role->load('permissions'),
            'permissionGroups' => $this->roleAccess->permissionGroupsFor(auth()->user()),
            'isProtectedRole' => $this->roleAccess->isProtectedRole($role),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($role->id),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        $oldValues = $this->roleSnapshot($role);

        if (! $this->roleAccess->isProtectedRole($role)) {
            $role->name = trim((string) $validated['name']);
            $role->save();
        }

        $permissions = $this->roleAccess->syncRolePermissions(
            $role,
            $validated['permissions'] ?? [],
            auth()->user(),
        );

        $role->refresh();

        $this->audit->log(
            action: 'role.updated',
            target: $role,
            oldValues: $oldValues,
            newValues: $this->roleSnapshot($role, $permissions),
            notes: 'Role updated',
            context: [
                'module' => 'iam',
                'source' => 'role_controller',
                'protected_role' => $this->roleAccess->isProtectedRole($role),
            ],
        );

        return redirect()
            ->route('admin.staff.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        $oldValues = $this->roleSnapshot($role);

        if ($role->users()->exists()) {
            return redirect()
                ->route('admin.staff.roles.index')
                ->withErrors(['role' => 'This role is still assigned to staff members and cannot be deleted.']);
        }

        $role->delete();

        $this->audit->log(
            action: 'role.deleted',
            target: $role,
            oldValues: $oldValues,
            notes: 'Custom role deleted',
            context: [
                'module' => 'iam',
                'source' => 'role_controller',
            ],
        );

        return redirect()
            ->route('admin.staff.roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    /**
     * @param  array<int, string>|null  $permissions
     * @return array<string, mixed>
     */
    private function roleSnapshot(Role $role, ?array $permissions = null): array
    {
        $role->loadMissing('permissions');

        return [
            'id' => $role->id,
            'name' => $role->name,
            'slug' => Str::slug($role->name),
            'permissions' => $permissions ?? $role->permissions->pluck('name')->sort()->values()->all(),
            'is_protected' => $this->roleAccess->isProtectedRole($role),
        ];
    }
}
