<?php

namespace App\Modules\Promotions\Navigation;

use App\Modules\Shared\Navigation\Contracts\RegistersAdminMenu;
use App\Modules\Shared\Navigation\DTOs\MenuItem;
use App\Modules\Shared\Navigation\Services\MenuRegistry;

class PromotionsMenu implements RegistersAdminMenu
{
    public function registerAdminMenu(MenuRegistry $registry): void
    {
        $registry->add(
            MenuItem::make('promotions_header')
                ->setLabel('Promotions')
                ->asHeader()
                ->setOrder(50)
        );

        $registry->add(
            MenuItem::make('promotions.coupons')
                ->setLabel('Coupons')
                ->setIcon('<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l2.25-2.25L9 9.75M15 14.25l-2.25-2.25L15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>')
                ->setParent('promotions_header')
                ->setRoute('admin.promotions.coupons.index')
                ->requirePermission('coupons.viewAny')
                ->activeWhen('admin.promotions.coupons.*')
                ->setOrder(1)
        );

        $registry->add(
            MenuItem::make('promotions.reports')
                ->setLabel('Promo Reports')
                ->setIcon('<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"></path></svg>')
                ->setParent('promotions_header')
                ->setRoute('admin.promotions.reports')
                ->requirePermission('coupons.viewAny')
                ->activeWhen('admin.promotions.reports')
                ->setOrder(2)
        );
    }
}
