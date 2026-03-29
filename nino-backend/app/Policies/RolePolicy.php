<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\IAM\Models\Permission;
use App\Modules\IAM\Models\Role;

class RolePolicy
{
    public function create(User $user): bool
    {
        return $this->canManageRoles($user);
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->canManageRoles($user);
    }

    /**
     * Determine whether the user can update roles.
     * Note: "users.manage_roles" handles the overarching capability.
     */
    public function update(User $user, Role $role): bool
    {
        if (! $this->canManageRoles($user)) return false;

        // Only super-admins can edit the super admin role
        if ($role->isSuperAdminRole() && ! $user->isSuperAdmin()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can assign this role to someone.
     */
    public function assign(User $user, Role $role): bool
    {
        if (! $this->canManageRoles($user)) return false;

        // Only Super Admins can assign Super Admin
        if ($role->isSuperAdminRole() && ! $user->isSuperAdmin()) {
            return false;
        }

        // Platform Admins can assign Platform Admin, but Franchises cannot
        if ($role->isPlatformAdminRole() && $user->organization_scope !== 'platform' && ! $user->isSuperAdmin()) {
            return false;
        }

        // Note: Actual organization constraints on the *target* user are checked by UserPolicy::update

        return true;
    }

    public function delete(User $user, Role $role): bool
    {
        if (! $this->canManageRoles($user)) return false;

        if ($role->isSystemRole()) {
            return false;
        }

        return true;
    }

    private function canManageRoles(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! Permission::query()->where('name', Permission::MANAGE_ROLES)->where('guard_name', 'web')->exists()) {
            return false;
        }

        return $user->hasPermissionTo(Permission::MANAGE_ROLES);
    }
}
