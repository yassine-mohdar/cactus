<?php

namespace App\Modules\Payments\Gateways;

use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use App\Modules\Payments\DTOs\PaymentResponse;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Models\GatewaySetting;
use App\Modules\Payments\Models\PaymentTransaction;
use App\Modules\Payments\Services\PaymentLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Payzone Morocco Payment Gateway Adapter
 *
 * Payzone is a Moroccan fintech payment aggregator supporting:
 *  - Credit/Debit cards (CMI network)
 *  - Mobile wallets
 *  - Cash-in points
 *
 * Flow:
 * 1. Merchant POSTs order data to Payzone API to create a payment intent
 * 2. Payzone returns a hosted checkout URL
 * 3. Customer completes payment on Payzone's hosted page
 * 4. Payzone redirects back with status parameters
 * 5. Payzone sends a server-to-server webhook for settlement confirmation
 *
 * Required credentials (stored encrypted in GatewaySetting):
 *  - api_key: Payzone API key
 *  - secret_key: HMAC signing secret
 *  - merchant_id: Payzone merchant identifier
 *
 * Required metadata (non-secret):
 *  - currency: ISO 4217 alpha code (default: MAD)
 */
class PayzonePaymentGateway implements PaymentGatewayInterface
{
    private const GATEWAY_ID = 'payzone';
    private const PRODUCTION_URL = 'https://api.payzone.ma/v1/payments';
    private const TEST_URL = 'https://sandbox.payzone.ma/v1/payments';

    private PaymentLogger $logger;

    public function __construct()
    {
        $this->logger = new PaymentLogger();
    }

    /**
     * Create a payment intent on Payzone and return the hosted checkout URL.
     */
    public function initiatePayment(Order $order): PaymentResponse
    {
        $setting = $this->getSetting();

        if (!$setting || !$setting->is_enabled) {
            $this->logger->logFailure(self::GATEWAY_ID, 'Payzone gateway is not enabled or configured.', 'GATEWAY_DISABLED', $order->id);
            return PaymentResponse::failure('Payzone payment gateway is not available.');
        }

        $apiKey = $setting->getCredential('api_key');
        $secretKey = $setting->getCredential('secret_key');
        $merchantId = $setting->getCredential('merchant_id');

        if (!$apiKey || !$secretKey || !$merchantId) {
            $this->logger->logFailure(self::GATEWAY_ID, 'Missing required Payzone credentials.', 'MISSING_CREDENTIALS', $order->id);
            return PaymentResponse::failure('Payzone gateway credentials are incomplete.');
        }

        $transactionRef = 'PZ-' . $order->id . '-' . now()->format('YmdHis') . '-' . Str::random(4);
        $amount = (int) round($order->grand_total * 100); // Payzone expects centimes
        $currency = $setting->metadata['currency'] ?? 'MAD';

        $payload = [
            'merchant_id' => $merchantId,
            'order_id' => $transactionRef,
            'amount' => $amount,
            'currency' => $currency,
            'description' => "Order #{$order->reference_number}",
            'customer' => [
                'name' => $order->customer ? $order->customer->full_name : 'Guest',
                'email' => $order->customer?->email ?? '',
                'phone' => '',
            ],
            'return_url' => route('payment.payzone.callback', ['ref' => $transactionRef]),
            'cancel_url' => route('payment.payzone.callback', ['ref' => $transactionRef, 'status' => 'cancelled']),
            'webhook_url' => route('payment.payzone.webhook'),
            'metadata' => [
                'order_reference' => $order->reference_number,
                'nino_order_id' => $order->id,
            ],
        ];

        // Sign the request
        $payload['signature'] = $this->generateSignature($payload, $secretKey);

        // Create the transaction record
        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'gateway' => self::GATEWAY_ID,
            'status' => PaymentStatus::PENDING,
            'amount' => $order->grand_total,
            'currency' => $order->currency,
            'gateway_reference' => $transactionRef,
            'payload' => ['payzone_ref' => $transactionRef],
        ]);

        // Log the initiation
        $this->logger->logInitiation(self::GATEWAY_ID, $order, $payload, $transaction);

        // Make API call to Payzone
        $startTime = microtime(true);
        $apiUrl = $setting->mode === 'live' ? self::PRODUCTION_URL : self::TEST_URL;

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey,
                    'Accept: application/json',
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                $this->logger->logFailure(
                    self::GATEWAY_ID, "cURL error: {$curlError}", 'CURL_ERROR',
                    $order->id, $transaction->id, $payload
                );
                $transaction->update(['status' => PaymentStatus::FAILED, 'error_message' => $curlError]);
                return PaymentResponse::failure("Connection to Payzone failed: {$curlError}");
            }

            $responseData = json_decode($responseBody, true) ?? [];

            if ($httpCode >= 200 && $httpCode < 300 && isset($responseData['checkout_url'])) {
                $payzoneId = $responseData['payment_id'] ?? $transactionRef;

                $transaction->update([
                    'gateway_reference' => $payzoneId,
                    'payload' => array_merge($transaction->payload ?? [], [
                        'payzone_payment_id' => $payzoneId,
                        'checkout_url' => $responseData['checkout_url'],
                    ]),
                ]);

                return PaymentResponse::redirect(
                    $responseData['checkout_url'],
                    $payzoneId,
                    $responseData
                );
            }

            // API returned an error
            $errorMsg = $responseData['message'] ?? $responseData['error'] ?? "Payzone API error (HTTP {$httpCode})";

            $this->logger->logFailure(
                self::GATEWAY_ID, $errorMsg, (string) $httpCode,
                $order->id, $transaction->id, $payload, $responseData
            );

            $transaction->update([
                'status' => PaymentStatus::FAILED,
                'error_message' => $errorMsg,
                'error_code' => (string) $httpCode,
            ]);

            return PaymentResponse::failure($errorMsg, $responseData);

        } catch (\Throwable $e) {
            $this->logger->logFailure(
                self::GATEWAY_ID,
                "Exception during Payzone initiation: {$e->getMessage()}",
                'EXCEPTION',
                $order->id,
                $transaction->id,
                $payload
            );
            $transaction->update(['status' => PaymentStatus::FAILED, 'error_message' => $e->getMessage()]);
            return PaymentResponse::failure('An unexpected error occurred while connecting to Payzone.');
        }
    }

    /**
     * Verify the synchronous callback from Payzone.
     */
    public function verifyPayment(Request $request): PaymentResponse
    {
        $responseData = $request->all();
        $setting = $this->getSetting();

        if (!$setting) {
            return PaymentResponse::failure('Payzone gateway configuration not found.');
        }

        $secretKey = $setting->getCredential('secret_key');
        $ref = $request->query('ref');
        $status = $responseData['status'] ?? $responseData['payment_status'] ?? null;

        $transaction = PaymentTransaction::where('gateway_reference', $ref)
            ->orWhere(function ($q) use ($ref) {
                $q->where('gateway', self::GATEWAY_ID)
                  ->whereJsonContains('payload->payzone_ref', $ref);
            })->first();

        $statusBefore = $transaction?->status?->value;

        // Verify signature if provided
        $receivedSig = $responseData['signature'] ?? null;
        if ($receivedSig && $secretKey) {
            $dataToVerify = collect($responseData)->except('signature')->toArray();
            $computedSig = $this->generateSignature($dataToVerify, $secretKey);

            if (!hash_equals($computedSig, $receivedSig)) {
                $this->logger->logCallback(
                    self::GATEWAY_ID, $request, $responseData,
                    isSuccessful: false,
                    gatewayReference: $ref,
                    orderId: $transaction?->order_id,
                    transactionId: $transaction?->id,
                    errorMessage: 'Signature verification failed.',
                    errorCode: 'SIG_MISMATCH',
                    statusBefore: $statusBefore,
                    statusAfter: PaymentStatus::FAILED->value,
                );

                if ($transaction) {
                    $transaction->update(['status' => PaymentStatus::FAILED, 'error_message' => 'Signature mismatch']);
                }

                return PaymentResponse::failure('Payment verification failed.', $responseData);
            }
        }

        $isSuccess = in_array($status, ['completed', 'approved', 'captured', 'success']);

        if ($isSuccess && $transaction) {
            if ($transaction->status !== PaymentStatus::CAPTURED) {
                $transaction->update(['status' => PaymentStatus::CAPTURED, 'payload' => array_merge($transaction->payload ?? [], $responseData)]);
            }

            $this->logger->logCallback(
                self::GATEWAY_ID, $request, $responseData,
                isSuccessful: true,
                gatewayReference: $ref,
                orderId: $transaction->order_id,
                transactionId: $transaction->id,
                statusBefore: $statusBefore,
                statusAfter: PaymentStatus::CAPTURED->value,
            );

            return PaymentResponse::success($ref, $responseData);
        }

        $errorMsg = $responseData['error_message'] ?? 'Payment was not completed.';
        if ($transaction && $transaction->status === PaymentStatus::PENDING) {
            $transaction->update(['status' => PaymentStatus::FAILED, 'error_message' => $errorMsg]);
        }

        $this->logger->logCallback(
            self::GATEWAY_ID, $request, $responseData,
            isSuccessful: false, gatewayReference: $ref,
            orderId: $transaction?->order_id, transactionId: $transaction?->id,
            errorMessage: $errorMsg, statusBefore: $statusBefore, statusAfter: PaymentStatus::FAILED->value,
        );

        return PaymentResponse::failure($errorMsg, $responseData);
    }

    /**
     * Handle asynchronous webhook from Payzone.
     */
    public function handleWebhook(Request $request): PaymentResponse
    {
        $responseData = $request->all();
        $setting = $this->getSetting();

        if (!$setting) {
            return PaymentResponse::failure('Payzone gateway not configured.');
        }

        $secretKey = $setting->getCredential('secret_key');
        $paymentId = $responseData['payment_id'] ?? $responseData['order_id'] ?? null;
        $status = $responseData['status'] ?? $responseData['payment_status'] ?? null;

        $transaction = PaymentTransaction::where('gateway', self::GATEWAY_ID)
            ->where(function($q) use ($paymentId) {
                $q->where('gateway_reference', $paymentId)
                  ->orWhereJsonContains('payload->payzone_payment_id', $paymentId);
            })->first();

        if (!$transaction) {
            $this->logger->logWebhook(
                self::GATEWAY_ID, $request, $responseData,
                isSuccessful: false, gatewayReference: $paymentId,
                errorMessage: "Transaction not found for payment_id: {$paymentId}",
            );
            return PaymentResponse::failure('Transaction not found.');
        }

        $statusBefore = $transaction->status->value;
        $isSuccess = in_array($status, ['completed', 'approved', 'captured', 'success']);

        if ($isSuccess && $transaction->status !== PaymentStatus::CAPTURED) {
            $transaction->update([
                'status' => PaymentStatus::CAPTURED,
                'payload' => array_merge($transaction->payload ?? [], $responseData),
            ]);

            $this->logger->logStatusChange(
                self::GATEWAY_ID, $transaction->id, $statusBefore,
                PaymentStatus::CAPTURED->value, $transaction->order_id,
                $paymentId, 'Webhook confirmed capture.'
            );
        }

        $this->logger->logWebhook(
            self::GATEWAY_ID, $request, $responseData,
            isSuccessful: $isSuccess, gatewayReference: $paymentId,
            orderId: $transaction->order_id, transactionId: $transaction->id,
            statusBefore: $statusBefore,
            statusAfter: $isSuccess ? PaymentStatus::CAPTURED->value : $statusBefore,
        );

        return $isSuccess
            ? PaymentResponse::success($paymentId, $responseData)
            : PaymentResponse::failure($responseData['error_message'] ?? 'Payment failed.', $responseData);
    }

    /**
     * Payzone refunds via API.
     */
    public function refund(string $gatewayReference, float $amount): PaymentResponse
    {
        $setting = $this->getSetting();
        if (!$setting) {
            return PaymentResponse::failure('Payzone is not configured.');
        }

        $apiKey = $setting->getCredential('api_key');
        $apiUrl = ($setting->mode === 'live' ? self::PRODUCTION_URL : self::TEST_URL) . "/{$gatewayReference}/refund";

        $transaction = PaymentTransaction::where('gateway_reference', $gatewayReference)->first();

        $refundPayload = [
            'amount' => (int) round($amount * 100),
            'reason' => 'Customer refund request',
        ];

        $this->logger->logRefund(
            self::GATEWAY_ID, 'refund_request', $transaction?->id ?? 0,
            $amount, $transaction?->currency ?? 'MAD', false,
            $gatewayReference, $transaction?->order_id, $refundPayload,
        );

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($refundPayload),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey,
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
            ]);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $responseTimeMs = (int) ((microtime(true)) * 1000);
            curl_close($ch);

            $responseData = json_decode($responseBody, true) ?? [];
            $isSuccess = $httpCode >= 200 && $httpCode < 300;

            if ($isSuccess && $transaction) {
                $newStatus = $amount >= $transaction->amount ? PaymentStatus::REFUNDED : PaymentStatus::PARTIALLY_REFUNDED;
                $transaction->update(['status' => $newStatus]);
            }

            $this->logger->logRefund(
                self::GATEWAY_ID, 'refund_response', $transaction?->id ?? 0,
                $amount, $transaction?->currency ?? 'MAD', $isSuccess,
                $gatewayReference, $transaction?->order_id, null, $responseData,
                $isSuccess ? null : ($responseData['message'] ?? 'Refund failed'),
                $responseTimeMs,
            );

            return $isSuccess
                ? PaymentResponse::success($gatewayReference, $responseData)
                : PaymentResponse::failure($responseData['message'] ?? 'Refund failed.', $responseData);

        } catch (\Throwable $e) {
            $this->logger->logFailure(self::GATEWAY_ID, $e->getMessage(), 'REFUND_EXCEPTION', $transaction?->order_id, $transaction?->id);
            return PaymentResponse::failure("Refund error: {$e->getMessage()}");
        }
    }

    private function getSetting(): ?GatewaySetting
    {
        return GatewaySetting::where('gateway_id', self::GATEWAY_ID)->first();
    }

    /**
     * Generate an HMAC-SHA256 signature for Payzone API requests.
     */
    private function generateSignature(array $data, string $secret): string
    {
        ksort($data);
        $serialized = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return hash_hmac('sha256', $serialized, $secret);
    }
}
