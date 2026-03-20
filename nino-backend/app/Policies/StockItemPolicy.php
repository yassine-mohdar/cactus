<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Inventory\Models\StockItem;

class StockItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.viewAny');
    }

    public function update(User $user, StockItem $stockItem): bool
    {
        return $user->can('inventory.adjust');
    }
}
