<?php

namespace App\Modules\Promotions\Enums;

enum CouponType: string
{
    case FIXED = 'fixed';
    case PERCENTAGE = 'percentage';

    public function label(): string
    {
        return match($this) {
            self::FIXED => 'Fixed Amount',
            self::PERCENTAGE => 'Percentage',
        };
    }

    /**
     * Format the discount value for display.
     */
    public function formatValue(float $value, string $currency = 'MAD'): string
    {
        return match($this) {
            self::FIXED => number_format($value, 2) . ' ' . $currency,
            self::PERCENTAGE => number_format($value, 0) . '%',
        };
    }
}
