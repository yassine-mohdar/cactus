<?php

namespace App\Modules\Finance\Enums;

enum RefundStatus: string
{
    case REQUESTED = 'requested';
    case APPROVED = 'approved';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match($this) {
            self::REQUESTED => 'Requested',
            self::APPROVED => 'Approved',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::REJECTED => 'Rejected',
        };
    }

    public function badgeColor(): string
    {
        return match($this) {
            self::REQUESTED => 'bg-yellow-100 text-yellow-800',
            self::APPROVED => 'bg-blue-100 text-blue-800',
            self::PROCESSING => 'bg-indigo-100 text-indigo-800',
            self::COMPLETED => 'bg-green-100 text-green-800',
            self::REJECTED => 'bg-red-100 text-red-800',
        };
    }
}
