<?php

namespace App\Modules\Inventory\Navigation;

use App\Modules\IAM\Models\Role;
use App\Modules\Shared\Navigation\Contracts\RegistersAdminMenu;
use App\Modules\Shared\Navigation\DTOs\MenuItem;
use App\Modules\Shared\Navigation\Services\MenuRegistry;

class InventoryMenu implements RegistersAdminMenu
{
    public function registerAdminMenu(MenuRegistry $registry): void
    {
        $registry->add(
            MenuItem::make('inventory_header')
                ->setLabel('Inventory')
                ->asHeader()
                ->prioritizeForRoles([Role::STOCK_MANAGER, Role::BRANCH_MANAGER], 78)
                ->setOrder(35)
        );

        $registry->add(
            MenuItem::make('inventory.overview')
                ->setLabel('Stock Overview')
                ->setIcon('<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>')
                ->setParent('inventory_header')
                ->setRoute('admin.inventory.index')
                ->requirePermission('inventory.viewAny')
                ->prioritizeForRoles([Role::STOCK_MANAGER, Role::BRANCH_MANAGER], 78)
                ->activeWhen('admin.inventory.index')
                ->setOrder(1)
        );

        $registry->add(
            MenuItem::make('inventory.reports.stock')
                ->setLabel('Stock Report')
                ->setIcon('<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"></path></svg>')
                ->setParent('inventory_header')
                ->setRoute('admin.inventory.reports.stock')
                ->requirePermission('inventory.viewAny')
                ->prioritizeForRoles([Role::STOCK_MANAGER, Role::BRANCH_MANAGER], 78)
                ->activeWhen('admin.inventory.reports.stock')
                ->setOrder(2)
        );

        $registry->add(
            MenuItem::make('inventory.reports.adjustments')
                ->setLabel('Movements & Damage')
                ->setIcon('<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>')
                ->setParent('inventory_header')
                ->setRoute('admin.inventory.reports.adjustments')
                ->requirePermission(['inventory.viewAny', 'inventory.adjust'])
                ->prioritizeForRoles([Role::STOCK_MANAGER, Role::BRANCH_MANAGER], 78)
                ->activeWhen('admin.inventory.reports.adjustments')
                ->setOrder(3)
        );

        $registry->add(
            MenuItem::make('inventory.suppliers')
                ->setLabel('Suppliers')
                ->setIcon('<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"></path></svg>')
                ->setParent('inventory_header')
                ->setRoute('admin.inventory.suppliers.index')
                ->requirePermission('inventory.viewAny')
                ->activeWhen('admin.inventory.suppliers.*')
                ->setOrder(4)
        );

        $registry->add(
            MenuItem::make('inventory.branches')
                ->setLabel('Branches')
                ->setIcon('<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-15 0V10.5a.75.75 0 01.75-.75h7.5a.75.75 0 01.75.75V21m-7.5-15h.008v.008H4.5V6zm3 0h.008v.008H7.5V6zm3 0h.008v.008H10.5V6zm-6 4.5h.008v.008H4.5v-.008zm3 0h.008v.008H7.5v-.008zm3 0h.008v.008H10.5v-.008z"></path></svg>')
                ->setParent('inventory_header')
                ->setRoute('admin.inventory.branches.index')
                ->requirePermission('inventory.viewAny')
                ->activeWhen('admin.inventory.branches.*')
                ->setOrder(5)
        );
    }
}
