<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('users.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        if (! $user->hasPermissionTo('users.view')) return false;

        return $this->isInScope($user, $model);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('users.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        if (! $user->hasPermissionTo('users.update')) return false;

        // Prevent modifying someone outside scope
        if (! $this->isInScope($user, $model)) return false;

        // Users can edit themselves if updating (unless specific fields are locked)
        // Prevent lowering Super Admin role by non-super admin
        if ($model->isSuperAdmin() && ! $user->isSuperAdmin()) return false;

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        if (! $user->hasPermissionTo('users.delete')) return false;

        // Prevent self deletion
        if ($user->id === $model->id) return false;

        // Prevent Super Admin deletion
        if ($model->isSuperAdmin()) return false;

        return $this->isInScope($user, $model);
    }

    /**
     * Check if the target user is within the authenticated user's organization scope.
     */
    protected function isInScope(User $user, User $target): bool
    {
        // Platform scope covers all users
        if ($user->organization_scope === 'platform' || $user->isSuperAdmin()) {
            return true;
        }

        // If target has no org, only platform/super admins can see them
        if (! $target->organization_id) {
            return false;
        }

        if ($user->organization_scope === 'franchise') {
            // Can see users in exact franchise, OR in a branch that belongs to this franchise
            if ($user->organization_id === $target->organization_id) return true;
            
            // Check if target is in a child branch 
            $targetFranchiseId = $target->organization?->parent_id;
            return $user->organization_id === $targetFranchiseId;
        }

        if ($user->organization_scope === 'branch') {
            // Can only see users in the exact same branch
            return $user->organization_id === $target->organization_id;
        }

        return false;
    }
}
