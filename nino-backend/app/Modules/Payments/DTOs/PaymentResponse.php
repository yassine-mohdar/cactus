<?php

namespace App\Modules\Payments\DTOs;

/**
 * Normalizes all Gateway responses so NinoWorld controllers 
 * never have to understand CMI vs Stripe specifics.
 */
class PaymentResponse
{
    public function __construct(
        public readonly bool $isSuccessful,
        public readonly string $status,           // Should map to App\Modules\Payments\Enums\PaymentStatus
        public readonly ?string $redirectUrl = null, // The Hosted URL to send the customer to (e.g. CMI gateway page)
        public readonly ?string $gatewayReference = null, // The external bank ID
        public readonly ?string $message = null,      // Clean error or success strings
        public readonly ?array $rawPayload = null     // Full array dumped from the webhook 
    ) {}

    public static function redirect(string $url, string $gatewayReference = null, array $raw = []): self
    {
        return new self(
            isSuccessful: true,
            status: 'pending',
            redirectUrl: $url,
            gatewayReference: $gatewayReference,
            rawPayload: $raw
        );
    }

    public static function success(string $gatewayReference, array $raw = []): self
    {
        return new self(
            isSuccessful: true,
            status: 'captured',
            gatewayReference: $gatewayReference,
            message: 'Payment verified and captured successfully.',
            rawPayload: $raw
        );
    }

    public static function failure(string $message, array $raw = []): self
    {
        return new self(
            isSuccessful: false,
            status: 'failed',
            message: $message,
            rawPayload: $raw
        );
    }
}
