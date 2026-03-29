<?php

namespace App\Modules\IAM\Support;

use InvalidArgumentException;

class PermissionNaming
{
    /**
     * @param  array<int, string>  $abilities
     * @return array<int, string>
     */
    public static function domain(string $domain, array $abilities): array
    {
        return array_map(
            static fn (string $ability): string => self::make($domain, $ability),
            $abilities,
        );
    }

    public static function make(string $domain, string $ability): string
    {
        $permission = trim($domain).'.'.trim($ability);

        if (! self::isValid($permission)) {
            throw new InvalidArgumentException("Invalid permission name [{$permission}].");
        }

        return $permission;
    }

    public static function isValid(string $permission): bool
    {
        return (bool) preg_match('/^[a-z]+(?:\.[A-Za-z_]+)+$/', $permission);
    }

    public static function groupKey(string $permission): string
    {
        if (str_starts_with($permission, 'catalog.')) {
            return 'catalog';
        }

        if (str_starts_with($permission, 'community.')) {
            return 'community';
        }

        return strtok($permission, '.');
    }

    public static function groupLabel(string $permission): string
    {
        return match (self::groupKey($permission)) {
            'users' => 'IAM',
            'organizations' => 'Organizations',
            'catalog', 'products', 'categories' => 'Catalog',
            'inventory' => 'Inventory',
            'orders' => 'Orders',
            'customers' => 'Customers',
            'shipping' => 'Shipping',
            'finance', 'payments' => 'Finance',
            'cms' => 'CMS',
            'coupons' => 'Promotions',
            'support' => 'Support',
            'reports' => 'Reports',
            'settings' => 'Settings',
            'community' => 'Community',
            default => ucwords(str_replace('_', ' ', self::groupKey($permission))),
        };
    }
}
