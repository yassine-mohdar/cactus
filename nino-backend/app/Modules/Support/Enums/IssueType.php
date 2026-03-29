<?php

namespace App\Modules\Support\Enums;

enum IssueType: string
{
    case ORDER = 'order';
    case PAYMENT = 'payment';
    case SHIPPING = 'shipping';
    case REFUND = 'refund';
    case PRODUCT = 'product';
    case ACCOUNT = 'account';
    case GENERAL = 'general';

    public function label(): string
    {
        return match($this) {
            self::ORDER => 'Order Issue',
            self::PAYMENT => 'Payment Issue',
            self::SHIPPING => 'Shipping Issue',
            self::REFUND => 'Refund/Return',
            self::PRODUCT => 'Product Issue',
            self::ACCOUNT => 'Account Issue',
            self::GENERAL => 'General',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::ORDER => '📦',
            self::PAYMENT => '💳',
            self::SHIPPING => '🚚',
            self::REFUND => '↩️',
            self::PRODUCT => '🏷️',
            self::ACCOUNT => '👤',
            self::GENERAL => '💬',
        };
    }
}
