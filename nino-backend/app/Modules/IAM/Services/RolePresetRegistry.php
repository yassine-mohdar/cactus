<?php

namespace App\Modules\IAM\Services;

use App\Modules\IAM\Models\Role;
use Illuminate\Support\Collection;

class RolePresetRegistry
{
    /**
     * @return Collection<int, array{name:string,permissions:array<int, string>}>
     */
    public function definitions(): Collection
    {
        return collect([
            [
                'name' => Role::SUPER_ADMIN,
                'permissions' => ['*'],
            ],
            [
                'name' => Role::PLATFORM_ADMIN,
                'permissions' => ['*'],
            ],
            [
                'name' => Role::FRANCHISE_MANAGER,
                'permissions' => [
                    'users.viewAny', 'users.view', 'users.create', 'users.update',
                    'organizations.viewAny', 'organizations.view',
                    'products.viewAny', 'products.view',
                    'inventory.viewAny', 'inventory.adjust', 'inventory.transfer',
                    'orders.viewAny', 'orders.view', 'orders.create', 'orders.update', 'orders.override_status', 'orders.cancel',
                    'customers.viewAny', 'customers.view',
                    'reports.viewAny',
                ],
            ],
            [
                'name' => Role::BRANCH_MANAGER,
                'permissions' => [
                    'users.viewAny', 'users.view',
                    'organizations.view',
                    'products.viewAny', 'products.view',
                    'inventory.viewAny', 'inventory.adjust',
                    'orders.viewAny', 'orders.view', 'orders.update', 'orders.override_status',
                    'customers.viewAny', 'customers.view',
                    'shipping.viewAny', 'shipping.update',
                ],
            ],
            [
                'name' => Role::CUSTOMER_SUPPORT_AGENT,
                'permissions' => [
                    'users.viewAny', 'users.view',
                    'orders.viewAny', 'orders.view', 'orders.update', 'orders.cancel',
                    'customers.viewAny', 'customers.view', 'customers.update',
                    'support.viewAny', 'support.manage_tickets',
                    'shipping.viewAny',
                ],
            ],
            [
                'name' => Role::SHIPPING_AGENT,
                'permissions' => [
                    'orders.viewAny', 'orders.view', 'orders.update',
                    'shipping.viewAny', 'shipping.update',
                ],
            ],
            [
                'name' => Role::EMPLOYEE,
                'permissions' => [],
            ],
            [
                'name' => Role::FINANCE_MANAGER,
                'permissions' => [
                    'orders.viewAny', 'orders.view',
                    'finance.viewAny', 'finance.manage_gateways',
                    'payments.viewAny', 'payments.refund',
                    'reports.viewAny',
                ],
            ],
            [
                'name' => Role::SEO_CONTENT_MANAGER,
                'permissions' => [
                    'categories.viewAny', 'categories.create', 'categories.update',
                    'products.viewAny', 'products.view', 'products.create', 'products.update',
                    'cms.viewAny', 'cms.manage_pages', 'cms.manage_blog',
                    'settings.view',
                ],
            ],
            [
                'name' => Role::STOCK_MANAGER,
                'permissions' => [
                    'products.viewAny', 'products.view',
                    'inventory.viewAny', 'inventory.adjust', 'inventory.transfer',
                ],
            ],
            [
                'name' => Role::MARKETING_MANAGER,
                'permissions' => [
                    'products.viewAny', 'products.view',
                    'customers.viewAny', 'customers.view',
                    'coupons.viewAny', 'coupons.create', 'coupons.update', 'coupons.delete',
                    'cms.viewAny',
                ],
            ],
            [
                'name' => Role::SALES_MANAGER,
                'permissions' => [
                    'orders.viewAny', 'orders.view',
                    'customers.viewAny', 'customers.view', 'customers.update',
                    'reports.viewAny',
                ],
            ],
            [
                'name' => Role::COMMUNITY_MODERATOR,
                'permissions' => [
                    'community.viewAny',
                    'community.groups.viewAny',
                    'community.posts.viewAny',
                    'community.reports.viewAny',
                    'community.reports.update',
                    'community.moderation.viewAny',
                    'community.moderation.update',
                ],
            ],
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function roleNames(): array
    {
        return $this->definitions()->pluck('name')->all();
    }

    /**
     * @return array<int, string>
     */
    public function permissionsFor(string $roleName): array
    {
        return (array) $this->definitions()
            ->firstWhere('name', $roleName)['permissions'] ?? [];
    }
}
