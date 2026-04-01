<?php

namespace App\Modules\Finance\Enums;

enum InvoiceStatus: string
{
    case ISSUED = 'issued';
    case PAID = 'paid';
    case VOID = 'void';

    public function label(): string
    {
        return match ($this) {
            self::ISSUED => 'Issued',
            self::PAID => 'Paid',
            self::VOID => 'Void',
        };
    }
}
