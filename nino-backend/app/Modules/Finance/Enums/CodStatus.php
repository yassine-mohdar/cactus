<?php

namespace App\Modules\Finance\Enums;

enum CodStatus: string
{
    case PENDING = 'pending';
    case COLLECTED = 'collected';
    case DEPOSITED = 'deposited';
    case RECONCILED = 'reconciled';
    case DISCREPANCY = 'discrepancy';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending Collection',
            self::COLLECTED => 'Collected',
            self::DEPOSITED => 'Deposited',
            self::RECONCILED => 'Reconciled',
            self::DISCREPANCY => 'Discrepancy',
        };
    }

    public function badgeColor(): string
    {
        return match($this) {
            self::PENDING => 'bg-yellow-100 text-yellow-800',
            self::COLLECTED => 'bg-blue-100 text-blue-800',
            self::DEPOSITED => 'bg-indigo-100 text-indigo-800',
            self::RECONCILED => 'bg-green-100 text-green-800',
            self::DISCREPANCY => 'bg-red-100 text-red-800',
        };
    }
}
