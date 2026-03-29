<?php

namespace App\Modules\Community\Enums;

enum GroupMembershipRole: string
{
    case OWNER = 'owner';
    case MODERATOR = 'moderator';
    case MEMBER = 'member';

    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Owner',
            self::MODERATOR => 'Moderator',
            self::MEMBER => 'Member',
        };
    }
}
