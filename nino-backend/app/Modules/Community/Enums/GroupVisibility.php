<?php

namespace App\Modules\Community\Enums;

enum GroupVisibility: string
{
    case PUBLIC = 'public';
    case PRIVATE = 'private';
    case HIDDEN = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::PUBLIC => 'Public',
            self::PRIVATE => 'Private',
            self::HIDDEN => 'Hidden',
        };
    }
}
