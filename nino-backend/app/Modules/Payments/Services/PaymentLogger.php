<?php

namespace App\Modules\Payments\Services;

use App\Modules\Payments\Models\PaymentLog;
use App\Modules\Payments\Models\PaymentTransaction;
use App\Modules\Orders\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Centralized Payment Logger
 *
 * Every gateway adapter MUST use this service to record all interactions.
 * This ensures a uniform forensic trail across CMI, Stripe, Payzone, and Offline methods.
 *
 * Key features:
 *  - Automatic redaction of sensitive fields (card numbers, CVVs, secret keys)
 *  - Response time tracking for gateway latency monitoring
 *  - IP/User-Agent capture for webhook source verification
 *  - Status transition auditing for compliance
 */
class PaymentLogger
{
    /**
     * Fields that must NEVER be stored in plain text in logs.
     */
    private const REDACTED_KEYS = [
        'card_number', 'cardNumber', 'pan', 'PAN',
        'cvv', 'CVV', 'cvv2', 'cvc',
        'secret_key', 'secretKey', 'hash_key', 'hashKey',
        'password', 'client_secret',
        'storeKey', 'store_key',
    ];

    private const REDACTION_PLACEHOLDER = '***REDACTED***';

    /**
     * Log an outbound initiation request to a gateway.
     */
    public function logInitiation(
        string $gateway,
        Order $order,
        array $requestPayload,
        ?PaymentTransaction $transaction = null,
    ): PaymentLog {
        return $this->createLog(
            gateway: $gateway,
            direction: 'outbound',
            eventType: 'initiation',
            orderId: $order->id,
            transactionId: $transaction?->id,
            requestPayload: $requestPayload,
            amount: $order->grand_total,
            currency: $order->currency,
        );
    }

    /**
     * Log a synchronous callback (browser redirect) from a gateway.
     */
    public function logCallback(
        string $gateway,
        Request $request,
        array $responsePayload,
        bool $isSuccessful,
        ?string $gatewayReference = null,
        ?int $orderId = null,
        ?int $transactionId = null,
        ?string $errorMessage = null,
        ?string $errorCode = null,
        ?string $statusBefore = null,
        ?string $statusAfter = null,
    ): PaymentLog {
        return $this->createLog(
            gateway: $gateway,
            direction: 'inbound',
            eventType: 'callback',
            orderId: $orderId,
            transactionId: $transactionId,
            responsePayload: $responsePayload,
            isSuccessful: $isSuccessful,
            gatewayReference: $gatewayReference,
            errorMessage: $errorMessage,
            errorCode: $errorCode,
            statusBefore: $statusBefore,
            statusAfter: $statusAfter,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );
    }

    /**
     * Log an asynchronous webhook from a gateway.
     */
    public function logWebhook(
        string $gateway,
        Request $request,
        array $responsePayload,
        bool $isSuccessful,
        ?string $gatewayReference = null,
        ?int $orderId = null,
        ?int $transactionId = null,
        ?string $errorMessage = null,
        ?string $statusBefore = null,
        ?string $statusAfter = null,
    ): PaymentLog {
        return $this->createLog(
            gateway: $gateway,
            direction: 'inbound',
            eventType: 'webhook',
            orderId: $orderId,
            transactionId: $transactionId,
            responsePayload: $responsePayload,
            isSuccessful: $isSuccessful,
            gatewayReference: $gatewayReference,
            errorMessage: $errorMessage,
            statusBefore: $statusBefore,
            statusAfter: $statusAfter,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );
    }

    /**
     * Log a payment status transition (internal audit).
     */
    public function logStatusChange(
        string $gateway,
        int $transactionId,
        string $statusBefore,
        string $statusAfter,
        ?int $orderId = null,
        ?string $gatewayReference = null,
        ?string $reason = null,
    ): PaymentLog {
        return $this->createLog(
            gateway: $gateway,
            direction: 'outbound',
            eventType: 'status_change',
            orderId: $orderId,
            transactionId: $transactionId,
            isSuccessful: true,
            gatewayReference: $gatewayReference,
            statusBefore: $statusBefore,
            statusAfter: $statusAfter,
            errorMessage: $reason,
        );
    }

    /**
     * Log a caught failure or exception during payment processing.
     */
    public function logFailure(
        string $gateway,
        string $errorMessage,
        ?string $errorCode = null,
        ?int $orderId = null,
        ?int $transactionId = null,
        ?array $requestPayload = null,
        ?array $responsePayload = null,
        ?string $gatewayReference = null,
        ?float $amount = null,
        ?string $currency = null,
    ): PaymentLog {
        // Also write to Laravel's error log channel for ops alerting
        Log::error("[PaymentGateway:{$gateway}] {$errorMessage}", [
            'error_code' => $errorCode,
            'order_id' => $orderId,
            'transaction_id' => $transactionId,
            'gateway_reference' => $gatewayReference,
        ]);

        return $this->createLog(
            gateway: $gateway,
            direction: 'inbound',
            eventType: 'error',
            orderId: $orderId,
            transactionId: $transactionId,
            requestPayload: $requestPayload,
            responsePayload: $responsePayload,
            isSuccessful: false,
            gatewayReference: $gatewayReference,
            errorMessage: $errorMessage,
            errorCode: $errorCode,
            amount: $amount,
            currency: $currency,
        );
    }

    /**
     * Log a refund request/response pair.
     */
    public function logRefund(
        string $gateway,
        string $eventType,
        int $transactionId,
        float $amount,
        string $currency,
        bool $isSuccessful,
        ?string $gatewayReference = null,
        ?int $orderId = null,
        ?array $requestPayload = null,
        ?array $responsePayload = null,
        ?string $errorMessage = null,
        ?int $responseTimeMs = null,
    ): PaymentLog {
        return $this->createLog(
            gateway: $gateway,
            direction: $eventType === 'refund_request' ? 'outbound' : 'inbound',
            eventType: $eventType,
            orderId: $orderId,
            transactionId: $transactionId,
            requestPayload: $requestPayload,
            responsePayload: $responsePayload,
            isSuccessful: $isSuccessful,
            gatewayReference: $gatewayReference,
            errorMessage: $errorMessage,
            amount: $amount,
            currency: $currency,
            responseTimeMs: $responseTimeMs,
        );
    }

    /**
     * Core log creation with automatic redaction.
     */
    private function createLog(
        string $gateway,
        string $direction,
        string $eventType,
        ?int $orderId = null,
        ?int $transactionId = null,
        ?array $requestPayload = null,
        ?array $responsePayload = null,
        bool $isSuccessful = false,
        ?string $gatewayReference = null,
        ?string $errorMessage = null,
        ?string $errorCode = null,
        ?string $statusBefore = null,
        ?string $statusAfter = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?float $amount = null,
        ?string $currency = null,
        ?int $responseTimeMs = null,
    ): PaymentLog {
        return PaymentLog::create([
            'payment_transaction_id' => $transactionId,
            'order_id' => $orderId,
            'gateway' => $gateway,
            'direction' => $direction,
            'event_type' => $eventType,
            'status_before' => $statusBefore,
            'status_after' => $statusAfter,
            'is_successful' => $isSuccessful,
            'gateway_reference' => $gatewayReference,
            'request_payload' => $requestPayload ? $this->redact($requestPayload) : null,
            'response_payload' => $responsePayload ? $this->redact($responsePayload) : null,
            'error_message' => $errorMessage,
            'error_code' => $errorCode,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent ? mb_substr($userAgent, 0, 255) : null,
            'amount' => $amount,
            'currency' => $currency,
            'response_time_ms' => $responseTimeMs,
        ]);
    }

    /**
     * Recursively redacts sensitive keys from a payload array.
     * This ensures card numbers, CVVs, and secret keys NEVER persist in logs.
     */
    private function redact(array $data): array
    {
        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                $value = $this->redact($value);
            } elseif (in_array($key, self::REDACTED_KEYS, true)) {
                $value = self::REDACTION_PLACEHOLDER;
            }
        }
        return $data;
    }
}
