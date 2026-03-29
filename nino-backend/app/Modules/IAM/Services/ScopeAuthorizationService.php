<?php

namespace App\Modules\IAM\Services;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;

class ScopeAuthorizationService
{
    /**
     * @param  array<int, string>|string  $requiredScopes
     */
    public function allowsRequiredScopes(User $user, array|string $requiredScopes): bool
    {
        $requiredScopes = array_values(array_filter((array) $requiredScopes));

        if ($requiredScopes === []) {
            return true;
        }

        $effectiveScopes = $this->effectiveScopesFor($user);

        foreach ($requiredScopes as $requiredScope) {
            if (in_array($requiredScope, $effectiveScopes, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    public function effectiveScopesFor(User $user): array
    {
        if ($user->isSuperAdmin()) {
            return ['platform', 'franchise', 'branch', 'own'];
        }

        return match ($user->organization_scope) {
            'platform' => ['platform', 'franchise', 'branch', 'own'],
            'franchise' => ['franchise', 'branch', 'own'],
            'branch' => ['branch', 'own'],
            'own' => ['own'],
            default => [],
        };
    }

    public function userIsInScope(User $actor, User $target): bool
    {
        if ($this->allowsRequiredScopes($actor, 'platform')) {
            return true;
        }

        if ($this->allowsRequiredScopes($actor, 'own') && $actor->is($target)) {
            return true;
        }

        if (! $target->organization_id) {
            return false;
        }

        if ($this->allowsRequiredScopes($actor, 'franchise')) {
            if ($actor->organization_id === $target->organization_id) {
                return true;
            }

            return $actor->organization_id !== null
                && $actor->organization_id === $target->organization?->parent_id;
        }

        if ($this->allowsRequiredScopes($actor, 'branch')) {
            return $actor->organization_id !== null
                && $actor->organization_id === $target->organization_id;
        }

        return false;
    }

    public function organizationIsInScope(User $actor, Organization $organization): bool
    {
        if ($this->allowsRequiredScopes($actor, 'platform')) {
            return true;
        }

        if ($this->allowsRequiredScopes($actor, 'franchise') && $actor->organization_id) {
            return $organization->id === $actor->organization_id
                || $organization->parent_id === $actor->organization_id;
        }

        if ($this->allowsRequiredScopes($actor, 'branch') && $actor->organization_id) {
            return $organization->id === $actor->organization_id;
        }

        if ($this->allowsRequiredScopes($actor, 'own') && $actor->organization_id) {
            return $organization->id === $actor->organization_id;
        }

        return false;
    }
}
