<?php

namespace App\Modules\Payments\Exceptions;

use InvalidArgumentException;

class UnsupportedPaymentGatewayException extends InvalidArgumentException
{
    public static function forGateway(string $gatewayId): self
    {
        return new self("Unsupported payment gateway [{$gatewayId}].");
    }
}
