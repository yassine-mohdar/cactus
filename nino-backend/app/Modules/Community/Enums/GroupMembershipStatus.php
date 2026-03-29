<?php

namespace App\Modules\Community\Enums;

enum GroupMembershipStatus: string
{
    case ACTIVE = 'active';
    case INVITED = 'invited';
    case LEFT = 'left';
    case REMOVED = 'removed';
    case BANNED = 'banned';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INVITED => 'Invited',
            self::LEFT => 'Left',
            self::REMOVED => 'Removed',
            self::BANNED => 'Banned',
        };
    }
}
