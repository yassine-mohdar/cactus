<?php

namespace App\Modules\Community\Enums;

enum ReactionType: string
{
    case LIKE = 'like';
    case LOVE = 'love';
    case SUPPORT = 'support';
    case CELEBRATE = 'celebrate';

    public function label(): string
    {
        return match ($this) {
            self::LIKE => 'Like',
            self::LOVE => 'Love',
            self::SUPPORT => 'Support',
            self::CELEBRATE => 'Celebrate',
        };
    }
}
