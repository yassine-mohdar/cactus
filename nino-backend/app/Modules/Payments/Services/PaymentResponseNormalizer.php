<?php

namespace App\Modules\Payments\Services;

use App\Modules\Payments\DTOs\PaymentResponse;
use App\Modules\Payments\Enums\PaymentStatus;

class PaymentResponseNormalizer
{
    /**
     * @var array<string, string>
     */
    private const STATUS_MAP = [
        'pending' => 'pending',
        'authorized' => 'authorized',
        'captured' => 'captured',
        'completed' => 'captured',
        'paid' => 'captured',
        'success' => 'captured',
        'failed' => 'failed',
        'error' => 'failed',
        'cancelled' => 'failed',
        'canceled' => 'failed',
        'declined' => 'failed',
        'refunded' => 'refunded',
        'partially_refunded' => 'partially_refunded',
    ];

    public function normalize(PaymentResponse $response): PaymentResponse
    {
        $status = $this->normalizeStatus($response);
        $gatewayReference = $this->normalizeNullableString($response->gatewayReference);
        $message = $this->normalizeNullableString($response->message);

        return new PaymentResponse(
            isSuccessful: $response->isSuccessful,
            status: $status,
            redirectUrl: $this->normalizeNullableString($response->redirectUrl),
            gatewayReference: $gatewayReference,
            message: $message,
            rawPayload: $response->rawPayload ?? [],
        );
    }

    private function normalizeStatus(PaymentResponse $response): string
    {
        $status = strtolower(trim($response->status));

        if ($status !== '' && isset(self::STATUS_MAP[$status])) {
            return self::STATUS_MAP[$status];
        }

        if ($response->isSuccessful && $response->redirectUrl) {
            return PaymentStatus::PENDING->value;
        }

        if ($response->isSuccessful) {
            return PaymentStatus::CAPTURED->value;
        }

        return PaymentStatus::FAILED->value;
    }

    private function normalizeNullableString(?string $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
