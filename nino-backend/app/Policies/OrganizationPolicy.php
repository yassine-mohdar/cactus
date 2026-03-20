<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\IAM\Services\ScopeAuthorizationService;
use App\Modules\Organizations\Models\Organization;

class OrganizationPolicy
{
    public function __construct(
        private readonly ScopeAuthorizationService $scopes = new ScopeAuthorizationService(),
    ) {}

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
        return $this->scopes->organizationIsInScope($user, $organization);
    }
}
