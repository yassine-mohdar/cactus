<?php

namespace App\Modules\Orders\Navigation;

use App\Modules\Shared\Navigation\Contracts\RegistersAdminMenu;
use App\Modules\Shared\Navigation\DTOs\MenuItem;
use App\Modules\Shared\Navigation\Services\MenuRegistry;

class OrdersMenu implements RegistersAdminMenu
{
    public function registerAdminMenu(MenuRegistry $registry): void
    {
        $registry->add(
            MenuItem::make('orders.list')
                ->setLabel('Orders')
                ->setIcon('<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>')
                ->setParent('commerce_header')
                ->setRoute('admin.orders.index')
                // Orders are core; assuming general access or a specific gate if needed. We'll use a placeholder or public for now.
                // Assuming "orders.viewAny" or just leaving it without strict gate for admin staff tests for now.
                ->requirePermission('orders.viewAny')
                ->activeWhen('admin.orders.*')
                ->setOrder(3)
        );
    }
}
