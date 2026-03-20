<?php

namespace App\Modules\Support\Enums;

enum IssueStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case WAITING_CUSTOMER = 'waiting_customer';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match($this) {
            self::OPEN => 'Open',
            self::IN_PROGRESS => 'In Progress',
            self::WAITING_CUSTOMER => 'Waiting on Customer',
            self::RESOLVED => 'Resolved',
            self::CLOSED => 'Closed',
        };
    }

    public function badgeColor(): string
    {
        return match($this) {
            self::OPEN => 'bg-red-100 text-red-800',
            self::IN_PROGRESS => 'bg-blue-100 text-blue-800',
            self::WAITING_CUSTOMER => 'bg-yellow-100 text-yellow-800',
            self::RESOLVED => 'bg-green-100 text-green-800',
            self::CLOSED => 'bg-gray-100 text-gray-600',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::OPEN, self::IN_PROGRESS, self::WAITING_CUSTOMER]);
    }
}
