<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('users.manage_roles');
    }

    /**
     * Determine whether the user can update roles.
     * Note: "users.manage_roles" handles the overarching capability.
     */
    public function update(User $user, Role $role): bool
    {
        if (! $user->hasPermissionTo('users.manage_roles')) return false;

        // Only super-admins can edit the super admin role
        if ($role->name === 'Super Admin' && ! $user->isSuperAdmin()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can assign this role to someone.
     */
    public function assign(User $user, Role $role): bool
    {
        if (! $user->hasPermissionTo('users.manage_roles')) return false;

        // Only Super Admins can assign Super Admin
        if ($role->name === 'Super Admin' && ! $user->isSuperAdmin()) {
            return false;
        }

        // Platform Admins can assign Platform Admin, but Franchises cannot
        if ($role->name === 'Platform Admin' && $user->organization_scope !== 'platform' && ! $user->isSuperAdmin()) {
            return false;
        }

        // Note: Actual organization constraints on the *target* user are checked by UserPolicy::update

        return true;
    }
}
