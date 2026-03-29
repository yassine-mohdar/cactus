<?php

namespace App\Modules\Community\Enums;

enum CommunityReportState: string
{
    case SUBMITTED = 'submitted';
    case QUEUED = 'queued';
    case IN_REVIEW = 'in_review';
    case ACTION_TAKEN = 'action_taken';
    case DISMISSED = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::SUBMITTED => 'Submitted',
            self::QUEUED => 'Queued',
            self::IN_REVIEW => 'In Review',
            self::ACTION_TAKEN => 'Action Taken',
            self::DISMISSED => 'Dismissed',
        };
    }
}
