<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Organizations\Services\OperationalScopeResolver;

class StockItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.viewAny');
    }

    public function view(User $user, StockItem $stockItem): bool
    {
        if (! $user->can('inventory.viewAny')) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->hasOrganizationScope('platform')) {
            return true;
        }

        if ($stockItem->branch_id === null) {
            return false;
        }

        $branchIds = app(OperationalScopeResolver::class)->branchIdsFor($user);

        return in_array($stockItem->branch_id, $branchIds, true);
    }

    public function update(User $user, StockItem $stockItem): bool
    {
        if (! $user->can('inventory.adjust')) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->hasOrganizationScope('platform')) {
            return true;
        }

        if ($stockItem->branch_id === null) {
            return false;
        }

        $branchIds = app(OperationalScopeResolver::class)->branchIdsFor($user);

        return in_array($stockItem->branch_id, $branchIds, true);
    }
}
