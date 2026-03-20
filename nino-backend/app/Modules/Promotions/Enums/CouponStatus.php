<?php

namespace App\Modules\Promotions\Enums;

enum CouponStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case EXPIRED = 'expired';
    case EXHAUSTED = 'exhausted';     // usage limit reached
    case SCHEDULED = 'scheduled';     // starts_at in future

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::EXPIRED => 'Expired',
            self::EXHAUSTED => 'Exhausted',
            self::SCHEDULED => 'Scheduled',
        };
    }

    public function badgeColor(): string
    {
        return match($this) {
            self::ACTIVE => 'bg-green-100 text-green-800',
            self::INACTIVE => 'bg-gray-100 text-gray-600',
            self::EXPIRED => 'bg-red-100 text-red-800',
            self::EXHAUSTED => 'bg-yellow-100 text-yellow-800',
            self::SCHEDULED => 'bg-blue-100 text-blue-800',
        };
    }
}
