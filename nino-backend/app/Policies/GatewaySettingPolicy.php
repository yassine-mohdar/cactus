<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Payments\Models\GatewaySetting;

class GatewaySettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('finance.manage_gateways');
    }

    public function update(User $user, GatewaySetting $gatewaySetting): bool
    {
        return $user->can('finance.manage_gateways');
    }
}
