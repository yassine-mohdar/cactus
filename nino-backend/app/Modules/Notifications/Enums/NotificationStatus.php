<?php

namespace App\Modules\Notifications\Enums;

enum NotificationStatus: string
{
    case QUEUED = 'queued';
    case SENDING = 'sending';
    case SENT = 'sent';
    case FAILED = 'failed';
    case RETRYING = 'retrying';

    public function label(): string
    {
        return match($this) {
            self::QUEUED => 'Queued',
            self::SENDING => 'Sending',
            self::SENT => 'Sent',
            self::FAILED => 'Failed',
            self::RETRYING => 'Retrying',
        };
    }

    public function badgeColor(): string
    {
        return match($this) {
            self::QUEUED => 'bg-yellow-100 text-yellow-800',
            self::SENDING => 'bg-blue-100 text-blue-800',
            self::SENT => 'bg-green-100 text-green-800',
            self::FAILED => 'bg-red-100 text-red-800',
            self::RETRYING => 'bg-orange-100 text-orange-800',
        };
    }
}
