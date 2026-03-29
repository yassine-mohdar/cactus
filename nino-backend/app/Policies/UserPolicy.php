<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\IAM\Services\ScopeAuthorizationService;

class UserPolicy
{
    public function __construct(
        private readonly ScopeAuthorizationService $scopes = new ScopeAuthorizationService(),
    ) {}

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

    public function viewAnyCustomers(User $user): bool
    {
        return $user->can('customers.viewAny');
    }

    public function viewCustomer(User $user, User $model): bool
    {
        if (! $model->isCustomer() || ! $user->can('customers.view')) {
            return false;
        }

        if (! $model->organization_id) {
            return true;
        }

        return $this->isInScope($user, $model);
    }

    public function createCustomer(User $user): bool
    {
        return $user->can('customers.create');
    }

    public function updateCustomer(User $user, User $model): bool
    {
        if (! $model->isCustomer() || ! $user->can('customers.update')) {
            return false;
        }

        if (! $model->organization_id) {
            return true;
        }

        return $this->isInScope($user, $model);
    }

    public function deleteCustomer(User $user, User $model): bool
    {
        if (! $model->isCustomer() || ! $user->can('customers.delete')) {
            return false;
        }

        if (! $model->organization_id) {
            return true;
        }

        return $this->isInScope($user, $model);
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
        return $this->scopes->userIsInScope($user, $target);
    }
}
