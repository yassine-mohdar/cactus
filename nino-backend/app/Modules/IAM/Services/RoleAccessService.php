<?php

namespace App\Modules\IAM\Services;

use App\Models\User;
use App\Modules\IAM\Models\Permission;
use App\Modules\IAM\Models\Role;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;

class RoleAccessService
{
    /**
     * @return array<int, string>
     */
    public function systemRoleNames(): array
    {
        return Role::systemRoleNames();
    }

    public function isProtectedRole(Role $role): bool
    {
        return $role->isSystemRole();
    }

    public function userCanManageRoles(User $actor): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        if (! Permission::query()->where('name', Permission::MANAGE_ROLES)->where('guard_name', 'web')->exists()) {
            return false;
        }

        return $actor->hasPermissionTo(Permission::MANAGE_ROLES);
    }

    /**
     * @return Collection<int, Role>
     */
    public function assignableRolesFor(User $actor): Collection
    {
        return Role::query()
            ->with('permissions')
            ->orderBy('name')
            ->get()
            ->filter(fn (Role $role): bool => $this->canAssignRole($actor, $role))
            ->values();
    }

    /**
     * @return Collection<int, Role>
     */
    public function manageableRolesFor(User $actor): Collection
    {
        return Role::query()
            ->withCount(['permissions', 'users'])
            ->with('permissions')
            ->orderByRaw("CASE WHEN name IN ('".implode("','", Role::systemRoleNames())."') THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get()
            ->filter(function (Role $role) use ($actor): bool {
                if ($this->isProtectedRole($role)) {
                    return $actor->can('update', $role) || $actor->can('assign', $role);
                }

                return $actor->can('viewAny', Role::class);
            })
            ->values();
    }

    /**
     * @return Collection<int, array{key:string,label:string,permissions:Collection<int, Permission>}>
     */
    public function permissionGroupsFor(User $actor): Collection
    {
        $grantable = $this->grantablePermissionNamesFor($actor);

        return Permission::query()
            ->ordered()
            ->get()
            ->filter(fn (Permission $permission): bool => in_array($permission->name, $grantable, true))
            ->groupBy(fn (Permission $permission): string => $permission->groupKey())
            ->map(function (Collection $permissions, string $groupKey): array {
                return [
                    'key' => $groupKey,
                    'label' => $permissions->first()?->groupLabel() ?? $groupKey,
                    'permissions' => $permissions->values(),
                ];
            })
            ->values();
    }

    /**
     * @param  array<int, string>  $permissionNames
     * @return array<int, string>
     */
    public function normalizePermissionSelection(array $permissionNames, User $actor): array
    {
        $grantable = $this->grantablePermissionNamesFor($actor);

        return Permission::query()
            ->whereIn('name', $permissionNames)
            ->pluck('name')
            ->filter(fn (string $permission): bool => in_array($permission, $grantable, true))
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $permissionNames
     * @return array<int, string>
     */
    public function syncRolePermissions(Role $role, array $permissionNames, User $actor): array
    {
        $normalized = $this->normalizePermissionSelection($permissionNames, $actor);

        $role->syncPermissions($normalized);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $normalized;
    }

    /**
     * @param  array<int, string>  $permissionNames
     * @return array<int, string>
     */
    public function syncDirectPermissions(User $staff, array $permissionNames, User $actor): array
    {
        $normalized = $this->normalizePermissionSelection($permissionNames, $actor);

        $staff->syncPermissions($normalized);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $normalized;
    }

    /**
     * @return array<int, string>
     */
    public function grantablePermissionNamesFor(User $actor): array
    {
        if ($actor->isSuperAdmin()) {
            return Permission::query()->pluck('name')->sort()->values()->all();
        }

        return $actor->getAllPermissions()
            ->pluck('name')
            ->sort()
            ->values()
            ->all();
    }

    public function canAssignRole(User $actor, Role $role): bool
    {
        if (! $actor->can('assign', $role)) {
            return false;
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        $grantable = $this->grantablePermissionNamesFor($actor);
        $rolePermissions = $role->permissions->pluck('name')->all();

        return empty(array_diff($rolePermissions, $grantable));
    }

}
