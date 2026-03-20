<?php

namespace App\Modules\IAM\Services;

use App\Models\User;

class AdminHomeRouteService
{
    public function routeNameFor(User $user): string
    {
        if (! $user->isStaff()) {
            return 'customer.account.home';
        }

        if ($user->isSuperAdmin() || $user->hasAnyRole([
            'Platform Admin',
            'Franchise Manager',
            'Branch Manager',
            'Sales Manager',
        ])) {
            return 'admin.dashboard';
        }

        if ($user->canAny(['support.viewAny', 'support.manage_tickets'])) {
            return 'admin.support.lookup';
        }

        if ($user->canAny(['shipping.viewAny', 'shipping.update', 'shipping.manage_carriers'])) {
            return 'admin.shipping.shipments.index';
        }

        if ($user->canAny(['finance.viewAny', 'payments.viewAny'])) {
            return 'admin.finance.reports.index';
        }

        if ($user->canAny(['cms.viewAny', 'cms.manage_blog', 'cms.manage_pages'])) {
            return 'admin.cms.posts.index';
        }

        if ($user->canAny(['coupons.viewAny', 'coupons.create', 'coupons.update'])) {
            return 'admin.promotions.coupons.index';
        }

        if ($user->canAny(['inventory.viewAny', 'inventory.adjust', 'inventory.transfer'])) {
            return 'admin.inventory.index';
        }

        if ($user->canAny(['orders.viewAny', 'orders.view'])) {
            return 'admin.orders.index';
        }

        if ($user->canAny(['users.viewAny', 'users.view'])) {
            return 'admin.staff.index';
        }

        return 'admin.dashboard';
    }
}
