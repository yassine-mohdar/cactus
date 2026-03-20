<?php

namespace App\Modules\Community\Enums;

enum ModerationQueueStatus: string
{
    case OPEN = 'open';
    case ASSIGNED = 'assigned';
    case IN_REVIEW = 'in_review';
    case RESOLVED = 'resolved';
    case DISMISSED = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::ASSIGNED => 'Assigned',
            self::IN_REVIEW => 'In Review',
            self::RESOLVED => 'Resolved',
            self::DISMISSED => 'Dismissed',
        };
    }
}
