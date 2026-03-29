<?php

namespace App\Modules\Organizations\Services;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class OrganizationAssignmentService
{
    /**
     * @return array<int, string>
     */
    public function allowedScopesFor(User $actor): array
    {
        if ($actor->isSuperAdmin() || $actor->hasOrganizationScope('platform')) {
            return ['platform', 'franchise', 'branch', 'own'];
        }

        if ($actor->hasOrganizationScope('franchise')) {
            return ['franchise', 'branch', 'own'];
        }

        if ($actor->hasOrganizationScope('branch')) {
            return ['branch', 'own'];
        }

        return ['own'];
    }

    public function availableOrganizationsFor(User $actor, ?string $scope = null): Collection
    {
        $scope ??= $actor->organization_scope;

        if ($actor->isSuperAdmin() || $actor->hasOrganizationScope('platform')) {
            return match ($scope) {
                'franchise' => Organization::franchise()->active()->orderBy('name')->get(),
                'branch' => Organization::branch()->active()->orderBy('name')->get(),
                'own' => Organization::query()
                    ->whereIn('type', [Organization::TYPE_FRANCHISE, Organization::TYPE_BRANCH])
                    ->active()
                    ->orderBy('name')
                    ->get(),
                default => collect(),
            };
        }

        if ($actor->hasOrganizationScope('franchise')) {
            return match ($scope) {
                'franchise' => Organization::query()
                    ->whereKey($actor->organization_id)
                    ->active()
                    ->get(),
                'branch', 'own' => Organization::branch()
                    ->active()
                    ->where('parent_id', $actor->organization_id)
                    ->orderBy('name')
                    ->get(),
                default => collect(),
            };
        }

        if (in_array($actor->organization_scope, ['branch', 'own'], true)) {
            return Organization::query()
                ->whereKey($actor->organization_id)
                ->active()
                ->get();
        }

        return collect();
    }

    public function assignableOrganizationsFor(User $actor): Collection
    {
        if ($actor->isSuperAdmin() || $actor->hasOrganizationScope('platform')) {
            return Organization::query()
                ->whereIn('type', [Organization::TYPE_FRANCHISE, Organization::TYPE_BRANCH])
                ->active()
                ->orderBy('type')
                ->orderBy('name')
                ->get();
        }

        if ($actor->hasOrganizationScope('franchise')) {
            return Organization::query()
                ->where(function ($query) use ($actor) {
                    $query->whereKey($actor->organization_id)
                        ->orWhere('parent_id', $actor->organization_id);
                })
                ->active()
                ->orderBy('type')
                ->orderBy('name')
                ->get();
        }

        if ($actor->organization_id) {
            return Organization::query()
                ->whereKey($actor->organization_id)
                ->active()
                ->get();
        }

        return collect();
    }

    /**
     * @return array{scope: string, organization: Organization|null}
     */
    public function resolveAssignment(User $actor, ?string $requestedScope, ?int $requestedOrganizationId): array
    {
        $allowedScopes = $this->allowedScopesFor($actor);
        $scope = $requestedScope ?: ($allowedScopes[0] ?? 'own');

        if (! in_array($scope, $allowedScopes, true)) {
            throw ValidationException::withMessages([
                'organization_scope' => 'The selected organization scope is not allowed for your current access level.',
            ]);
        }

        if ($scope === 'platform') {
            return ['scope' => 'platform', 'organization' => null];
        }

        $organization = $requestedOrganizationId
            ? Organization::query()->find($requestedOrganizationId)
            : $this->defaultOrganizationForScope($actor, $scope);

        if (! $organization) {
            throw ValidationException::withMessages([
                'organization_id' => 'An organization unit is required for the selected scope.',
            ]);
        }

        if (! $this->isOrganizationAllowedForScope($actor, $scope, $organization)) {
            throw ValidationException::withMessages([
                'organization_id' => 'The selected organization unit is outside your allowed scope.',
            ]);
        }

        return [
            'scope' => $scope,
            'organization' => $organization,
        ];
    }

    public function assignPrimaryOrganization(User $user, ?Organization $organization): void
    {
        if (! $organization) {
            $user->organization_id = null;
            $user->save();
            $user->organizations()->detach();

            return;
        }

        $user->organization_id = $organization->id;
        $user->save();
        $user->organizations()->sync([
            $organization->id => ['is_primary' => true],
        ]);
    }

    private function defaultOrganizationForScope(User $actor, string $scope): ?Organization
    {
        if ($actor->isSuperAdmin() || $actor->hasOrganizationScope('platform')) {
            return null;
        }

        if ($scope === 'franchise') {
            return $actor->organization;
        }

        if (in_array($scope, ['branch', 'own'], true) && $actor->hasOrganizationScope('franchise')) {
            return Organization::branch()
                ->where('parent_id', $actor->organization_id)
                ->active()
                ->orderBy('name')
                ->first();
        }

        return $actor->organization;
    }

    private function isOrganizationAllowedForScope(User $actor, string $scope, Organization $organization): bool
    {
        if ($scope === 'franchise' && ! $organization->isFranchise()) {
            return false;
        }

        if ($scope === 'branch' && ! $organization->isBranch()) {
            return false;
        }

        if ($scope === 'own' && ! in_array($organization->type, [Organization::TYPE_FRANCHISE, Organization::TYPE_BRANCH], true)) {
            return false;
        }

        if ($actor->isSuperAdmin() || $actor->hasOrganizationScope('platform')) {
            return true;
        }

        if ($actor->hasOrganizationScope('franchise')) {
            if ($scope === 'franchise') {
                return $organization->is($actor->organization);
            }

            return $organization->isBranch() && $organization->parent_id === $actor->organization_id;
        }

        return $organization->id === $actor->organization_id;
    }
}
