<?php

namespace App\Modules\Community\Enums;

enum GroupMembershipPolicy: string
{
    case OPEN = 'open';
    case APPROVAL = 'approval';
    case INVITE_ONLY = 'invite_only';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::APPROVAL => 'Approval Required',
            self::INVITE_ONLY => 'Invite Only',
        };
    }
}
