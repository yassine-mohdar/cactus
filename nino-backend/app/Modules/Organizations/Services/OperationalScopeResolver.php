<?php

namespace App\Modules\Organizations\Services;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Builder;

class OperationalScopeResolver
{
    /**
     * @return array<int, int>
     */
    public function branchIdsFor(User $actor): array
    {
        if ($actor->isSuperAdmin() || $actor->hasOrganizationScope('platform')) {
            return Organization::branch()->pluck('id')->all();
        }

        if ($actor->hasOrganizationScope('franchise')) {
            return Organization::branch()
                ->where('parent_id', $actor->organization_id)
                ->pluck('id')
                ->all();
        }

        if (in_array($actor->organization_scope, ['branch', 'own'], true) && $actor->organization?->isBranch()) {
            return [$actor->organization_id];
        }

        return [];
    }

    /**
     * @return array<int, int>
     */
    public function organizationIdsFor(User $actor): array
    {
        if ($actor->isSuperAdmin() || $actor->hasOrganizationScope('platform')) {
            return Organization::query()->pluck('id')->all();
        }

        if ($actor->hasOrganizationScope('franchise')) {
            return array_merge(
                [$actor->organization_id],
                $this->branchIdsFor($actor),
            );
        }

        return $actor->organization_id ? [$actor->organization_id] : [];
    }

    public function applyBranchScope(Builder $query, User $actor, string $column = 'branch_id'): Builder
    {
        $branchIds = $this->branchIdsFor($actor);

        if ($branchIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($column, $branchIds);
    }
}
