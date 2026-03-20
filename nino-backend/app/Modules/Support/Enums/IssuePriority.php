<?php

namespace App\Modules\Support\Enums;

enum IssuePriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';

    public function label(): string
    {
        return match($this) {
            self::LOW => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH => 'High',
            self::URGENT => 'Urgent',
        };
    }

    public function badgeColor(): string
    {
        return match($this) {
            self::LOW => 'bg-gray-100 text-gray-600',
            self::MEDIUM => 'bg-blue-100 text-blue-800',
            self::HIGH => 'bg-orange-100 text-orange-800',
            self::URGENT => 'bg-red-100 text-red-800',
        };
    }
}
