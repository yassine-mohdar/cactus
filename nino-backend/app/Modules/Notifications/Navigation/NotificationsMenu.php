<?php

namespace App\Modules\Notifications\Navigation;

use App\Modules\Shared\Navigation\Contracts\RegistersAdminMenu;
use App\Modules\Shared\Navigation\DTOs\MenuItem;
use App\Modules\Shared\Navigation\Services\MenuRegistry;

class NotificationsMenu implements RegistersAdminMenu
{
    public function registerAdminMenu(MenuRegistry $registry): void
    {
        $registry->add(
            MenuItem::make('notifications_header')
                ->setLabel('Notifications')
                ->asHeader()
                ->setOrder(45)
        );

        $registry->add(
            MenuItem::make('notifications.templates')
                ->setLabel('Templates')
                ->setIcon('<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"></path></svg>')
                ->setParent('notifications_header')
                ->setRoute('admin.notifications.templates.index')
                ->activeWhen('admin.notifications.templates.*')
                ->setOrder(1)
        );

        $registry->add(
            MenuItem::make('notifications.logs')
                ->setLabel('Delivery Logs')
                ->setIcon('<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>')
                ->setParent('notifications_header')
                ->setRoute('admin.notifications.logs.index')
                ->activeWhen('admin.notifications.logs.*')
                ->setOrder(2)
        );

        $registry->add(
            MenuItem::make('notifications.integrations')
                ->setLabel('Integrations')
                ->setIcon('<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"></path></svg>')
                ->setParent('notifications_header')
                ->setRoute('admin.notifications.integrations.index')
                ->activeWhen('admin.notifications.integrations.*')
                ->setOrder(3)
        );
    }
}
