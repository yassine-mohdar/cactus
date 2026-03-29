<?php

namespace App\Modules\Checkout\Exceptions;

use Exception;

class CheckoutException extends Exception
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message,
        public readonly string $errorCode = 'CHECKOUT_FAILED',
        public readonly array $context = [],
    ) {
        parent::__construct($message);
    }
}
