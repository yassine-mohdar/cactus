<?php

namespace App\Modules\IAM\Navigation;

use App\Modules\IAM\Models\Role;
use App\Modules\Shared\Navigation\Contracts\RegistersAdminMenu;
use App\Modules\Shared\Navigation\DTOs\MenuItem;
use App\Modules\Shared\Navigation\Services\MenuRegistry;

class IAMMenu implements RegistersAdminMenu
{
    public function registerAdminMenu(MenuRegistry $registry): void
    {
        $registry->add(
            MenuItem::make('system_header')
                ->setLabel('System')
                ->asHeader()
                ->prioritizeForRoles([Role::SUPER_ADMIN, Role::PLATFORM_ADMIN, Role::FRANCHISE_MANAGER, Role::BRANCH_MANAGER], 120)
                ->setOrder(90)
        );

        $registry->add(
            MenuItem::make('iam.staff')
                ->setLabel('Staff & Roles')
                ->setIcon('<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>')
                ->setParent('system_header')
                ->setRoute('admin.staff.index')
                ->requirePermission('users.viewAny')
                ->prioritizeForRoles([Role::SUPER_ADMIN, Role::PLATFORM_ADMIN, Role::FRANCHISE_MANAGER, Role::BRANCH_MANAGER], 120)
                ->activeWhen('admin.staff.*')
                ->setOrder(1)
        );

        $registry->add(
            MenuItem::make('iam.role_library')
                ->setLabel('Role Library')
                ->setIcon('<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m5.25 2.25a8.25 8.25 0 11-16.5 0 8.25 8.25 0 0116.5 0Z" /></svg>')
                ->setParent('system_header')
                ->setRoute('admin.staff.roles.index')
                ->requirePermission('users.manage_roles')
                ->prioritizeForRoles([Role::SUPER_ADMIN, Role::PLATFORM_ADMIN], 120)
                ->activeWhen('admin.staff.roles.*')
                ->setOrder(2)
        );
    }
}
