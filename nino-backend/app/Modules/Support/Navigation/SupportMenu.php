<?php

namespace App\Modules\Support\Navigation;

use App\Modules\Shared\Navigation\Contracts\RegistersAdminMenu;
use App\Modules\Shared\Navigation\DTOs\MenuItem;
use App\Modules\Shared\Navigation\Services\MenuRegistry;

class SupportMenu implements RegistersAdminMenu
{
    public function registerAdminMenu(MenuRegistry $registry): void
    {
        $registry->add(
            MenuItem::make('support_header')
                ->setLabel('Support')
                ->asHeader()
                ->setOrder(65)
        );

        $registry->add(
            MenuItem::make('support.lookup')
                ->setLabel('Lookup')
                ->setIcon('<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"></path></svg>')
                ->setParent('support_header')
                ->setRoute('admin.support.lookup')
                ->activeWhen('admin.support.lookup*')
                ->setOrder(1)
        );

        $registry->add(
            MenuItem::make('support.issues')
                ->setLabel('Issues')
                ->setIcon('<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"></path></svg>')
                ->setParent('support_header')
                ->setRoute('admin.support.issues.index')
                ->activeWhen('admin.support.issues.*')
                ->setOrder(2)
        );
    }
}
