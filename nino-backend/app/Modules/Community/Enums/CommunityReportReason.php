<?php

namespace App\Modules\Community\Enums;

enum CommunityReportReason: string
{
    case SPAM = 'spam';
    case HARASSMENT = 'harassment';
    case HATEFUL_CONDUCT = 'hateful_conduct';
    case EXPLICIT_CONTENT = 'explicit_content';
    case MISINFORMATION = 'misinformation';
    case COPYRIGHT = 'copyright';
    case FRAUD = 'fraud';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::SPAM => 'Spam',
            self::HARASSMENT => 'Harassment',
            self::HATEFUL_CONDUCT => 'Hateful Conduct',
            self::EXPLICIT_CONTENT => 'Explicit Content',
            self::MISINFORMATION => 'Misinformation',
            self::COPYRIGHT => 'Copyright',
            self::FRAUD => 'Fraud',
            self::OTHER => 'Other',
        };
    }
}
