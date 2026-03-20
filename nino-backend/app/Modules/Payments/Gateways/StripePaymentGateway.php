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
 * Stripe Payment Gateway Adapter
 *
 * Stripe is the global standard for online payments. This adapter uses
 * Stripe Checkout Sessions for the hosted payment flow instead of Elements,
 * which avoids PCI DSS scope entirely.
 *
 * Flow:
 * 1. Merchant creates a Checkout Session via Stripe API
 * 2. Customer is redirected to Stripe's hosted checkout page
 * 3. Stripe redirects back to success/cancel URL with session_id
 * 4. Stripe sends a webhook (checkout.session.completed) for settlement confirmation
 *
 * Required credentials (stored encrypted in GatewaySetting):
 *  - secret_key: Stripe Secret Key (sk_test_... or sk_live_...)
 *  - webhook_secret: Stripe Webhook Signing Secret (whsec_...)
 *
 * Required metadata (non-secret, stored in GatewaySetting.metadata):
 *  - publishable_key: Stripe Publishable Key (pk_test_... or pk_live_...)
 *  - currency: ISO 4217 alpha code (default: mad)
 */
class StripePaymentGateway implements PaymentGatewayInterface
{
    private const GATEWAY_ID = 'stripe';
    private const API_BASE = 'https://api.stripe.com/v1';

    private PaymentLogger $logger;

    public function __construct()
    {
        $this->logger = new PaymentLogger();
    }

    /**
     * Create a Stripe Checkout Session and return the hosted URL.
     */
    public function initiatePayment(Order $order): PaymentResponse
    {
        $setting = $this->getSetting();

        if (!$setting || !$setting->is_enabled) {
            $this->logger->logFailure(self::GATEWAY_ID, 'Stripe gateway is not enabled or configured.', 'GATEWAY_DISABLED', $order->id);
            return PaymentResponse::failure('Stripe payment gateway is not available.');
        }

        $secretKey = $setting->getCredential('secret_key');

        if (!$secretKey) {
            $this->logger->logFailure(self::GATEWAY_ID, 'Missing Stripe secret key.', 'MISSING_CREDENTIALS', $order->id);
            return PaymentResponse::failure('Stripe gateway credentials are incomplete.');
        }

        $currency = strtolower($setting->metadata['currency'] ?? 'mad');
        $transactionRef = 'STRIPE-' . $order->id . '-' . now()->format('YmdHis');

        // Build line items for Stripe Checkout
        $lineItems = [];
        foreach ($order->lineItems as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => (int) round($item->unit_price * 100), // Stripe uses centimes
                    'product_data' => [
                        'name' => $item->product_name . ($item->variant_name ? " - {$item->variant_name}" : ''),
                        'description' => "SKU: {$item->sku}",
                    ],
                ],
                'quantity' => $item->quantity,
            ];
        }

        // Add shipping as a line item if applicable
        if ($order->shipping_total > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => (int) round($order->shipping_total * 100),
                    'product_data' => [
                        'name' => 'Shipping',
                    ],
                ],
                'quantity' => 1,
            ];
        }

        // Build Checkout Session parameters
        $sessionParams = [
            'mode' => 'payment',
            'success_url' => route('payment.stripe.callback', ['ref' => $transactionRef, 'session_id' => '{CHECKOUT_SESSION_ID}']),
            'cancel_url' => route('payment.stripe.callback', ['ref' => $transactionRef, 'status' => 'cancelled']),
            'client_reference_id' => $transactionRef,
            'customer_email' => $order->customer?->email,
            'metadata[nino_order_id]' => (string) $order->id,
            'metadata[nino_reference]' => $order->reference_number,
            'payment_intent_data[metadata][nino_order_id]' => (string) $order->id,
        ];

        // Add line items in Stripe's expected array format
        foreach ($lineItems as $idx => $item) {
            foreach ($item['price_data'] as $pdKey => $pdVal) {
                if (is_array($pdVal)) {
                    foreach ($pdVal as $subKey => $subVal) {
                        $sessionParams["line_items[{$idx}][price_data][{$pdKey}][{$subKey}]"] = $subVal;
                    }
                } else {
                    $sessionParams["line_items[{$idx}][price_data][{$pdKey}]"] = $pdVal;
                }
            }
            $sessionParams["line_items[{$idx}][quantity]"] = $item['quantity'];
        }

        // Handle discount as a coupon if applicable
        if ($order->discount_total > 0) {
            $discountAmount = (int) round($order->discount_total * 100);
            // We'll apply discount using a custom session total override approach
            // For now, include it in metadata for reconciliation
            $sessionParams['metadata[discount_amount]'] = (string) $discountAmount;
        }

        // Create the transaction record
        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'gateway' => self::GATEWAY_ID,
            'status' => PaymentStatus::PENDING,
            'amount' => $order->grand_total,
            'currency' => $order->currency,
            'gateway_reference' => $transactionRef,
            'payload' => ['stripe_ref' => $transactionRef],
        ]);

        $this->logger->logInitiation(self::GATEWAY_ID, $order, $sessionParams, $transaction);

        // Call Stripe API to create Checkout Session
        $startTime = microtime(true);

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => self::API_BASE . '/checkout/sessions',
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($sessionParams),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $secretKey,
                    'Content-Type: application/x-www-form-urlencoded',
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
                    $order->id, $transaction->id, $sessionParams
                );
                $transaction->update(['status' => PaymentStatus::FAILED, 'error_message' => $curlError]);
                return PaymentResponse::failure("Connection to Stripe failed: {$curlError}");
            }

            $responseData = json_decode($responseBody, true) ?? [];

            if ($httpCode >= 200 && $httpCode < 300 && isset($responseData['url'])) {
                $stripeSessionId = $responseData['id'];
                $checkoutUrl = $responseData['url'];

                $transaction->update([
                    'payload' => array_merge($transaction->payload ?? [], [
                        'stripe_session_id' => $stripeSessionId,
                        'checkout_url' => $checkoutUrl,
                    ]),
                ]);

                return PaymentResponse::redirect($checkoutUrl, $stripeSessionId, $responseData);
            }

            // Stripe API error
            $errorMsg = $responseData['error']['message'] ?? "Stripe API error (HTTP {$httpCode})";
            $errorCode = $responseData['error']['code'] ?? (string) $httpCode;

            $this->logger->logFailure(
                self::GATEWAY_ID, $errorMsg, $errorCode,
                $order->id, $transaction->id, $sessionParams, $responseData
            );

            $transaction->update([
                'status' => PaymentStatus::FAILED,
                'error_message' => $errorMsg,
                'error_code' => $errorCode,
            ]);

            return PaymentResponse::failure($errorMsg, $responseData);

        } catch (\Throwable $e) {
            $this->logger->logFailure(
                self::GATEWAY_ID,
                "Exception: {$e->getMessage()}",
                'EXCEPTION',
                $order->id,
                $transaction->id,
                $sessionParams
            );
            $transaction->update(['status' => PaymentStatus::FAILED, 'error_message' => $e->getMessage()]);
            return PaymentResponse::failure('An unexpected error occurred while connecting to Stripe.');
        }
    }

    /**
     * Verify the Stripe Checkout Session after customer redirect.
     */
    public function verifyPayment(Request $request): PaymentResponse
    {
        $setting = $this->getSetting();
        if (!$setting) {
            return PaymentResponse::failure('Stripe gateway is not configured.');
        }

        $secretKey = $setting->getCredential('secret_key');
        $sessionId = $request->query('session_id');
        $ref = $request->query('ref');

        $transaction = PaymentTransaction::where('gateway', self::GATEWAY_ID)
            ->where(function($q) use ($ref, $sessionId) {
                $q->where('gateway_reference', $ref)
                  ->orWhereJsonContains('payload->stripe_session_id', $sessionId);
            })->first();

        $statusBefore = $transaction?->status?->value;

        if ($request->query('status') === 'cancelled') {
            $this->logger->logCallback(
                self::GATEWAY_ID, $request, ['status' => 'cancelled', 'ref' => $ref],
                isSuccessful: false, gatewayReference: $ref,
                orderId: $transaction?->order_id, transactionId: $transaction?->id,
                errorMessage: 'Customer cancelled checkout.',
                statusBefore: $statusBefore, statusAfter: PaymentStatus::FAILED->value,
            );

            if ($transaction && $transaction->status === PaymentStatus::PENDING) {
                $transaction->update(['status' => PaymentStatus::FAILED, 'error_message' => 'Cancelled by customer']);
            }

            return PaymentResponse::failure('Payment was cancelled.');
        }

        // Verify the session with Stripe API
        if (!$sessionId) {
            return PaymentResponse::failure('Missing session ID.');
        }

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => self::API_BASE . '/checkout/sessions/' . $sessionId,
                CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $secretKey],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
            ]);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $sessionData = json_decode($responseBody, true) ?? [];

            if ($httpCode >= 200 && $httpCode < 300 && ($sessionData['payment_status'] ?? '') === 'paid') {
                $paymentIntentId = $sessionData['payment_intent'] ?? $sessionId;

                if ($transaction && $transaction->status !== PaymentStatus::CAPTURED) {
                    $transaction->update([
                        'status' => PaymentStatus::CAPTURED,
                        'gateway_reference' => $paymentIntentId,
                        'payload' => array_merge($transaction->payload ?? [], [
                            'stripe_payment_intent' => $paymentIntentId,
                            'stripe_payment_status' => $sessionData['payment_status'],
                        ]),
                    ]);
                }

                $this->logger->logCallback(
                    self::GATEWAY_ID, $request, $sessionData,
                    isSuccessful: true, gatewayReference: $paymentIntentId,
                    orderId: $transaction?->order_id, transactionId: $transaction?->id,
                    statusBefore: $statusBefore, statusAfter: PaymentStatus::CAPTURED->value,
                );

                return PaymentResponse::success($paymentIntentId, $sessionData);
            }

            $errorMsg = $sessionData['error']['message'] ?? 'Payment not completed.';

            $this->logger->logCallback(
                self::GATEWAY_ID, $request, $sessionData,
                isSuccessful: false, gatewayReference: $sessionId,
                orderId: $transaction?->order_id, transactionId: $transaction?->id,
                errorMessage: $errorMsg,
                statusBefore: $statusBefore, statusAfter: PaymentStatus::FAILED->value,
            );

            return PaymentResponse::failure($errorMsg, $sessionData);

        } catch (\Throwable $e) {
            $this->logger->logFailure(self::GATEWAY_ID, $e->getMessage(), 'VERIFICATION_EXCEPTION');
            return PaymentResponse::failure('Failed to verify payment with Stripe.');
        }
    }

    /**
     * Handle Stripe Webhook (checkout.session.completed event).
     *
     * Stripe signs webhooks with HMAC-SHA256 using the webhook signing secret.
     * This is the most reliable way to confirm payment settlement.
     */
    public function handleWebhook(Request $request): PaymentResponse
    {
        $setting = $this->getSetting();
        if (!$setting) {
            return PaymentResponse::failure('Stripe gateway not configured.');
        }

        $webhookSecret = $setting->getCredential('webhook_secret');
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature', '');

        // Verify webhook signature
        if ($webhookSecret && $sigHeader) {
            $isValid = $this->verifyWebhookSignature($payload, $sigHeader, $webhookSecret);

            if (!$isValid) {
                $this->logger->logWebhook(
                    self::GATEWAY_ID, $request, ['raw' => 'signature_verification_failed'],
                    isSuccessful: false,
                    errorMessage: 'Webhook signature verification failed.',
                );
                return PaymentResponse::failure('Invalid webhook signature.');
            }
        }

        $event = json_decode($payload, true);
        $eventType = $event['type'] ?? '';
        $eventData = $event['data']['object'] ?? [];

        // We only care about checkout.session.completed and payment_intent.succeeded
        if (!in_array($eventType, ['checkout.session.completed', 'payment_intent.succeeded', 'payment_intent.payment_failed'])) {
            // Acknowledge but ignore unrelated events
            return new PaymentResponse(isSuccessful: true, status: 'ignored', message: "Event type {$eventType} ignored.");
        }

        // Find the transaction
        $ninoOrderId = $eventData['metadata']['nino_order_id'] ?? null;
        $clientRef = $eventData['client_reference_id'] ?? null;
        $paymentIntentId = $eventData['payment_intent'] ?? $eventData['id'] ?? null;

        $transaction = PaymentTransaction::where('gateway', self::GATEWAY_ID)
            ->where(function ($q) use ($clientRef, $paymentIntentId, $ninoOrderId) {
                $q->where('gateway_reference', $clientRef)
                  ->orWhere('gateway_reference', $paymentIntentId)
                  ->orWhereJsonContains('payload->stripe_session_id', $clientRef);
                if ($ninoOrderId) {
                    $q->orWhere('order_id', $ninoOrderId);
                }
            })->first();

        if (!$transaction) {
            $this->logger->logWebhook(
                self::GATEWAY_ID, $request, $eventData,
                isSuccessful: false, gatewayReference: $paymentIntentId,
                errorMessage: "No matching transaction found for webhook event.",
            );
            return PaymentResponse::failure('Transaction not found.');
        }

        $statusBefore = $transaction->status->value;

        if ($eventType === 'payment_intent.payment_failed') {
            $failMsg = $eventData['last_payment_error']['message'] ?? 'Payment failed.';
            $transaction->update([
                'status' => PaymentStatus::FAILED,
                'error_message' => $failMsg,
                'error_code' => $eventData['last_payment_error']['code'] ?? null,
            ]);

            $this->logger->logWebhook(
                self::GATEWAY_ID, $request, $eventData,
                isSuccessful: false, gatewayReference: $paymentIntentId,
                orderId: $transaction->order_id, transactionId: $transaction->id,
                errorMessage: $failMsg,
                statusBefore: $statusBefore, statusAfter: PaymentStatus::FAILED->value,
            );

            return PaymentResponse::failure($failMsg, $eventData);
        }

        // checkout.session.completed or payment_intent.succeeded
        $paymentStatus = $eventData['payment_status'] ?? $eventData['status'] ?? '';

        if (in_array($paymentStatus, ['paid', 'succeeded']) && $transaction->status !== PaymentStatus::CAPTURED) {
            $transaction->update([
                'status' => PaymentStatus::CAPTURED,
                'gateway_reference' => $paymentIntentId ?? $transaction->gateway_reference,
                'payload' => array_merge($transaction->payload ?? [], [
                    'stripe_payment_intent' => $paymentIntentId,
                    'stripe_event_type' => $eventType,
                    'stripe_payment_status' => $paymentStatus,
                ]),
            ]);

            $this->logger->logStatusChange(
                self::GATEWAY_ID, $transaction->id, $statusBefore,
                PaymentStatus::CAPTURED->value, $transaction->order_id,
                $paymentIntentId, "Webhook event: {$eventType}"
            );
        }

        $this->logger->logWebhook(
            self::GATEWAY_ID, $request, $eventData,
            isSuccessful: true, gatewayReference: $paymentIntentId,
            orderId: $transaction->order_id, transactionId: $transaction->id,
            statusBefore: $statusBefore, statusAfter: PaymentStatus::CAPTURED->value,
        );

        return PaymentResponse::success($paymentIntentId ?? $transaction->gateway_reference, $eventData);
    }

    /**
     * Process a refund via Stripe API.
     */
    public function refund(string $gatewayReference, float $amount): PaymentResponse
    {
        $setting = $this->getSetting();
        if (!$setting) {
            return PaymentResponse::failure('Stripe is not configured.');
        }

        $secretKey = $setting->getCredential('secret_key');
        $transaction = PaymentTransaction::where('gateway_reference', $gatewayReference)->first();

        $refundParams = [
            'payment_intent' => $gatewayReference,
            'amount' => (int) round($amount * 100),
        ];

        $this->logger->logRefund(
            self::GATEWAY_ID, 'refund_request', $transaction?->id ?? 0,
            $amount, $transaction?->currency ?? 'MAD', false,
            $gatewayReference, $transaction?->order_id, $refundParams,
        );

        try {
            $startTime = microtime(true);
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => self::API_BASE . '/refunds',
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($refundParams),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $secretKey,
                    'Content-Type: application/x-www-form-urlencoded',
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
            ]);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);
            curl_close($ch);

            $responseData = json_decode($responseBody, true) ?? [];
            $isSuccess = $httpCode >= 200 && $httpCode < 300 && ($responseData['status'] ?? '') === 'succeeded';

            if ($isSuccess && $transaction) {
                $newStatus = $amount >= $transaction->amount ? PaymentStatus::REFUNDED : PaymentStatus::PARTIALLY_REFUNDED;
                $statusBefore = $transaction->status->value;
                $transaction->update(['status' => $newStatus]);

                $this->logger->logStatusChange(
                    self::GATEWAY_ID, $transaction->id, $statusBefore,
                    $newStatus->value, $transaction->order_id,
                    $gatewayReference, "Refund of {$amount} processed."
                );
            }

            $this->logger->logRefund(
                self::GATEWAY_ID, 'refund_response', $transaction?->id ?? 0,
                $amount, $transaction?->currency ?? 'MAD', $isSuccess,
                $gatewayReference, $transaction?->order_id, null, $responseData,
                $isSuccess ? null : ($responseData['error']['message'] ?? 'Refund failed'),
                $responseTimeMs,
            );

            return $isSuccess
                ? PaymentResponse::success($responseData['id'] ?? $gatewayReference, $responseData)
                : PaymentResponse::failure($responseData['error']['message'] ?? 'Refund failed.', $responseData);

        } catch (\Throwable $e) {
            $this->logger->logFailure(self::GATEWAY_ID, $e->getMessage(), 'REFUND_EXCEPTION', $transaction?->order_id, $transaction?->id);
            return PaymentResponse::failure("Refund error: {$e->getMessage()}");
        }
    }

    // ──────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────

    private function getSetting(): ?GatewaySetting
    {
        return GatewaySetting::where('gateway_id', self::GATEWAY_ID)->first();
    }

    /**
     * Verify Stripe webhook signature (HMAC-SHA256).
     *
     * Stripe-Signature header format: t=timestamp,v1=signature
     */
    private function verifyWebhookSignature(string $payload, string $sigHeader, string $secret): bool
    {
        // Parse the signature header
        $parts = [];
        foreach (explode(',', $sigHeader) as $item) {
            [$key, $value] = explode('=', $item, 2);
            $parts[trim($key)] = trim($value);
        }

        $timestamp = $parts['t'] ?? '';
        $signature = $parts['v1'] ?? '';

        if (!$timestamp || !$signature) {
            return false;
        }

        // Prevent replay attacks — reject if older than 5 minutes
        if (abs(time() - (int)$timestamp) > 300) {
            return false;
        }

        // Compute expected signature
        $signedPayload = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
