<?php

namespace App\Modules\Catalog\Navigation;

use App\Modules\Shared\Navigation\Contracts\RegistersAdminMenu;
use App\Modules\Shared\Navigation\DTOs\MenuItem;
use App\Modules\Shared\Navigation\Services\MenuRegistry;

class CatalogMenu implements RegistersAdminMenu
{
    public function registerAdminMenu(MenuRegistry $registry): void
    {
        $registry->add(
            MenuItem::make('commerce_header')
                ->setLabel('Commerce')
                ->asHeader()
                ->setOrder(10)
        );

        $registry->add(
            MenuItem::make('catalog.categories')
                ->setLabel('Categories')
                ->setIcon('<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>')
                ->setParent('commerce_header')
                ->setRoute('admin.catalog.categories.index')
                ->requirePermission('catalog.categories.viewAny')
                ->activeWhen('admin.catalog.categories.*')
                ->setOrder(1)
        );

        $registry->add(
            MenuItem::make('catalog.products')
                ->setLabel('Products')
                ->setIcon('<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>')
                ->setParent('commerce_header')
                ->setRoute('admin.catalog.products.index')
                ->requirePermission('catalog.products.viewAny')
                ->activeWhen('admin.catalog.products.*')
                ->setOrder(2)
        );
    }
}
