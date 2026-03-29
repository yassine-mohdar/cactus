<?php

namespace App\Modules\Finance\Enums;

enum PaymentMethod: string
{
    case CASH_ON_DELIVERY = 'cod';
    case CREDIT_CARD = 'credit_card';
    case DEBIT_CARD = 'debit_card';
    case BANK_TRANSFER = 'bank_transfer';
    case PAYPAL = 'paypal';
    case STRIPE = 'stripe';
    case WALLET = 'wallet';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::CASH_ON_DELIVERY => 'Cash on Delivery',
            self::CREDIT_CARD => 'Credit Card',
            self::DEBIT_CARD => 'Debit Card',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::PAYPAL => 'PayPal',
            self::STRIPE => 'Stripe',
            self::WALLET => 'Wallet',
            self::OTHER => 'Other',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::CASH_ON_DELIVERY => '💵',
            self::CREDIT_CARD => '💳',
            self::DEBIT_CARD => '💳',
            self::BANK_TRANSFER => '🏦',
            self::PAYPAL => '🅿️',
            self::STRIPE => '⚡',
            self::WALLET => '👛',
            self::OTHER => '💰',
        };
    }

    public function isCOD(): bool
    {
        return $this === self::CASH_ON_DELIVERY;
    }
}
