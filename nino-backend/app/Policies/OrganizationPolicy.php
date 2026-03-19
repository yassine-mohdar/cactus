<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;

class OrganizationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('organizations.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Organization $organization): bool
    {
        if (! $user->hasPermissionTo('organizations.view')) return false;

        return $this->isInScope($user, $organization);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('organizations.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Organization $organization): bool
    {
        if (! $user->hasPermissionTo('organizations.update')) return false;

        return $this->isInScope($user, $organization);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Organization $organization): bool
    {
        if (! $user->hasPermissionTo('organizations.delete')) return false;

        return $this->isInScope($user, $organization);
    }

    /**
     * Determine if organization is within scope.
     */
    protected function isInScope(User $user, Organization $organization): bool
    {
        if ($user->organization_scope === 'platform' || $user->isSuperAdmin()) {
            return true;
        }

        if ($user->organization_scope === 'franchise' && $user->organization_id) {
            // Franchise can only see/edit itself or its children branches
            return $organization->id === $user->organization_id || $organization->parent_id === $user->organization_id;
        }

        if ($user->organization_scope === 'branch' && $user->organization_id) {
            // Branch can only see/edit itself
            return $organization->id === $user->organization_id;
        }

        return false;
    }
}
