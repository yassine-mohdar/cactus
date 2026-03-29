<?php

namespace App\Modules\Cms\Navigation;

use App\Modules\IAM\Models\Role;
use App\Modules\Shared\Navigation\Contracts\RegistersAdminMenu;
use App\Modules\Shared\Navigation\DTOs\MenuItem;
use App\Modules\Shared\Navigation\Services\MenuRegistry;

class CmsMenu implements RegistersAdminMenu
{
    public function registerAdminMenu(MenuRegistry $registry): void
    {
        $registry->add(
            MenuItem::make('cms_header')
                ->setLabel('Content')
                ->asHeader()
                ->prioritizeForRoles([Role::SEO_CONTENT_MANAGER, Role::MARKETING_MANAGER], 80)
                ->setOrder(55)
        );

        $registry->add(
            MenuItem::make('cms.posts')
                ->setLabel('Blog Posts')
                ->setIcon('<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z"></path></svg>')
                ->setParent('cms_header')
                ->setRoute('admin.cms.posts.index')
                ->requirePermission('cms.viewAny')
                ->prioritizeForRoles([Role::SEO_CONTENT_MANAGER, Role::MARKETING_MANAGER], 80)
                ->activeWhen('admin.cms.posts.*')
                ->setOrder(1)
        );

        $registry->add(
            MenuItem::make('cms.categories')
                ->setLabel('Categories')
                ->setIcon('<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776"></path></svg>')
                ->setParent('cms_header')
                ->setRoute('admin.cms.categories.index')
                ->requirePermission('cms.viewAny')
                ->prioritizeForRoles([Role::SEO_CONTENT_MANAGER], 80)
                ->activeWhen('admin.cms.categories.*')
                ->setOrder(2)
        );

        $registry->add(
            MenuItem::make('cms.tags')
                ->setLabel('Tags')
                ->setIcon('<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"></path></svg>')
                ->setParent('cms_header')
                ->setRoute('admin.cms.tags.index')
                ->requirePermission('cms.viewAny')
                ->prioritizeForRoles([Role::SEO_CONTENT_MANAGER], 80)
                ->activeWhen('admin.cms.tags.*')
                ->setOrder(3)
        );

        $registry->add(
            MenuItem::make('cms.redirects')
                ->setLabel('Redirects')
                ->setIcon('<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"></path></svg>')
                ->setParent('cms_header')
                ->setRoute('admin.cms.redirects.index')
                ->requirePermission('cms.viewAny')
                ->prioritizeForRoles([Role::SEO_CONTENT_MANAGER], 80)
                ->activeWhen('admin.cms.redirects.*')
                ->setOrder(4)
        );
    }
}
