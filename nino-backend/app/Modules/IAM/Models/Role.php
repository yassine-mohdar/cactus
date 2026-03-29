<?php

namespace App\Modules\IAM\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    public const SUPER_ADMIN = 'Super Admin';
    public const PLATFORM_ADMIN = 'Platform Admin';
    public const FRANCHISE_MANAGER = 'Franchise Manager';
    public const BRANCH_MANAGER = 'Branch Manager';
    public const CUSTOMER_SUPPORT_AGENT = 'Customer Support Agent';
    public const SHIPPING_AGENT = 'Shipping Agent';
    public const EMPLOYEE = 'Employee';
    public const FINANCE_MANAGER = 'Finance Manager';
    public const SEO_CONTENT_MANAGER = 'SEO / Content Manager';
    public const MARKETING_MANAGER = 'Media Buying / Marketing Manager';
    public const SALES_MANAGER = 'Sales Manager';
    public const STOCK_MANAGER = 'Stock Manager';
    public const COMMUNITY_MODERATOR = 'Community Moderator';

    /**
     * @return array<int, string>
     */
    public static function systemRoleNames(): array
    {
        return [
            self::SUPER_ADMIN,
            self::PLATFORM_ADMIN,
            self::FRANCHISE_MANAGER,
            self::BRANCH_MANAGER,
            self::CUSTOMER_SUPPORT_AGENT,
            self::SHIPPING_AGENT,
            self::EMPLOYEE,
            self::FINANCE_MANAGER,
            self::SEO_CONTENT_MANAGER,
            self::MARKETING_MANAGER,
            self::SALES_MANAGER,
            self::STOCK_MANAGER,
            self::COMMUNITY_MODERATOR,
        ];
    }

    public function scopeSystemPresets(Builder $query): Builder
    {
        return $query->whereIn('name', self::systemRoleNames());
    }

    public function scopeCustom(Builder $query): Builder
    {
        return $query->whereNotIn('name', self::systemRoleNames());
    }

    public function isSystemRole(): bool
    {
        return in_array($this->name, self::systemRoleNames(), true);
    }

    public function isSuperAdminRole(): bool
    {
        return $this->name === self::SUPER_ADMIN;
    }

    public function isPlatformAdminRole(): bool
    {
        return $this->name === self::PLATFORM_ADMIN;
    }

    public function permissions(): BelongsToMany
    {
        /** @var BelongsToMany $relation */
        $relation = parent::permissions();

        return $relation;
    }

    public function users(): MorphToMany
    {
        /** @var MorphToMany $relation */
        $relation = parent::users();

        return $relation;
    }
}
