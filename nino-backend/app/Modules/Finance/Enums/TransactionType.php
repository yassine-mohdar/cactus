<?php

namespace App\Modules\Finance\Enums;

enum TransactionType: string
{
    case PAYMENT = 'payment';
    case REFUND = 'refund';
    case PARTIAL_REFUND = 'partial_refund';
    case CHARGEBACK = 'chargeback';
    case ADJUSTMENT = 'adjustment';
    case FEE = 'fee';

    public function label(): string
    {
        return match($this) {
            self::PAYMENT => 'Payment',
            self::REFUND => 'Refund',
            self::PARTIAL_REFUND => 'Partial Refund',
            self::CHARGEBACK => 'Chargeback',
            self::ADJUSTMENT => 'Adjustment',
            self::FEE => 'Fee',
        };
    }

    public function badgeColor(): string
    {
        return match($this) {
            self::PAYMENT => 'bg-green-100 text-green-800',
            self::REFUND, self::PARTIAL_REFUND => 'bg-red-100 text-red-800',
            self::CHARGEBACK => 'bg-orange-100 text-orange-800',
            self::ADJUSTMENT => 'bg-blue-100 text-blue-800',
            self::FEE => 'bg-gray-100 text-gray-600',
        };
    }
}
