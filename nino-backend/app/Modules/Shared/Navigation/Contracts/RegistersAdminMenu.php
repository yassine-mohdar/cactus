<?php

namespace App\Modules\Shared\Navigation\Contracts;

use App\Modules\Shared\Navigation\Services\MenuRegistry;

interface RegistersAdminMenu
{
    public function registerAdminMenu(MenuRegistry $registry): void;
}
