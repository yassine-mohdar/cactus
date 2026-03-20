<?php

namespace App\Modules\Payments\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case AUTHORIZED = 'authorized'; // Money is held but not acquired (common in B2B or hotels, perhaps less common in retail but good architecture)
    case CAPTURED = 'captured'; // Money is fully acquired
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case PARTIALLY_REFUNDED = 'partially_refunded';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::AUTHORIZED => 'Authorized',
            self::CAPTURED => 'Captured',
            self::FAILED => 'Failed',
            self::REFUNDED => 'Refunded',
            self::PARTIALLY_REFUNDED => 'Partially Refunded',
        };
    }
}
