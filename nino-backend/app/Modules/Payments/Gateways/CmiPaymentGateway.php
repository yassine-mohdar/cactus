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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * CMI (Centre Monétique Interbancaire) Payment Gateway Adapter
 *
 * CMI is the primary payment processor for Morocco. It uses a server-to-server
 * hash-signed POST form redirect flow similar to legacy 3DSecure.
 *
 * Flow:
 * 1. Merchant builds a form with order data + HMAC hash of concatenated fields
 * 2. Customer is redirected to CMI's hosted payment page
 * 3. CMI processes the card and redirects back with signed response parameters
 * 4. CMI also sends an asynchronous callback (webhook) for settlement confirmation
 *
 * Required credentials (stored encrypted in GatewaySetting):
 *  - store_id: CMI Merchant Store identifier
 *  - client_id: CMI Client identifier
 *  - hash_key: HMAC-SHA512 secret key for signing
 *  - terminal_id: Optional terminal identifier
 *
 * Required metadata (non-secret, stored in GatewaySetting.metadata):
 *  - currency_code: ISO 4217 numeric code (504 = MAD)
 *  - language: Payment page language (ar, fr, en)
 */
class CmiPaymentGateway implements PaymentGatewayInterface
{
    private const GATEWAY_ID = 'cmi';
    private const PRODUCTION_URL = 'https://payment.cmi.co.ma/fim/est3Dgate';
    private const TEST_URL = 'https://testpayment.cmi.co.ma/fim/est3Dgate';

    private PaymentLogger $logger;

    public function __construct()
    {
        $this->logger = new PaymentLogger();
    }

    public function gatewayId(): string
    {
        return self::GATEWAY_ID;
    }

    /**
     * Build the signed form data and return the redirect URL to CMI's hosted page.
     */
    public function initiatePayment(Order $order): PaymentResponse
    {
        $setting = $this->getSetting();

        if (!$setting || !$setting->is_enabled) {
            $this->logger->logFailure(self::GATEWAY_ID, 'CMI gateway is not enabled or configured.', 'GATEWAY_DISABLED', $order->id);
            return PaymentResponse::failure('CMI payment gateway is not available.');
        }

        $storeId = $setting->getCredential('store_id');
        $clientId = $setting->getCredential('client_id');
        $hashKey = $setting->getCredential('hash_key');
        $terminalId = $setting->getCredential('terminal_id');

        if (!$storeId || !$clientId || !$hashKey) {
            $this->logger->logFailure(self::GATEWAY_ID, 'Missing required CMI credentials (store_id, client_id, or hash_key).', 'MISSING_CREDENTIALS', $order->id);
            return PaymentResponse::failure('CMI gateway credentials are incomplete. Please contact the administrator.');
        }

        // Generate unique OID (Order ID for CMI, must be unique per attempt)
        $oid = 'CMI-' . $order->id . '-' . now()->format('YmdHis') . '-' . Str::random(4);
        $amount = number_format($order->grand_total, 2, '.', '');
        $currencyCode = $setting->metadata['currency_code'] ?? '504'; // 504 = MAD
        $language = $setting->metadata['language'] ?? 'fr';

        // Build parameters for hash computation
        $params = [
            'clientid' => $clientId,
            'amount' => $amount,
            'oid' => $oid,
            'okUrl' => route('payment.cmi.callback', ['status' => 'success']),
            'failUrl' => route('payment.cmi.callback', ['status' => 'fail']),
            'callbackUrl' => route('payment.cmi.webhook'),
            'shopurl' => config('app.url'),
            'TranType' => 'PreAuth',
            'currency' => $currencyCode,
            'lang' => $language,
            'storetype' => '3d_pay_hosting',
            'hashAlgorithm' => 'ver3',
            'encoding' => 'UTF-8',
            'rnd' => microtime(true),
            'BillToName' => $order->customer ? $order->customer->full_name : 'Guest',
            'email' => $order->customer?->email ?? '',
        ];

        if ($terminalId) {
            $params['terminalid'] = $terminalId;
        }

        // Generate HMAC hash (CMI uses pipe-delimited concatenation of sorted values)
        $params['hash'] = $this->generateHash($params, $hashKey, $storeId);

        // Create the transaction record
        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'gateway' => self::GATEWAY_ID,
            'status' => PaymentStatus::PENDING,
            'amount' => $order->grand_total,
            'currency' => $order->currency,
            'gateway_reference' => $oid,
            'payload' => ['cmi_oid' => $oid, 'currency_code' => $currencyCode],
        ]);

        // Log the initiation
        $this->logger->logInitiation(self::GATEWAY_ID, $order, $params, $transaction);

        $gatewayUrl = $setting->mode === 'live' ? self::PRODUCTION_URL : self::TEST_URL;

        return new PaymentResponse(
            isSuccessful: true,
            status: PaymentStatus::PENDING->value,
            redirectUrl: $gatewayUrl,
            gatewayReference: $oid,
            message: 'Redirect to CMI payment page.',
            rawPayload: array_merge($params, ['gateway_url' => $gatewayUrl])
        );
    }

    /**
     * Verify the synchronous browser callback from CMI.
     * CMI posts signed parameters back to our okUrl/failUrl.
     */
    public function verifyPayment(Request $request): PaymentResponse
    {
        $startTime = microtime(true);
        $responseData = $request->all();
        $setting = $this->getSetting();

        if (!$setting) {
            return PaymentResponse::failure('CMI gateway configuration not found.');
        }

        $hashKey = $setting->getCredential('hash_key');
        $storeId = $setting->getCredential('store_id');

        // Extract key response fields
        $oid = $responseData['oid'] ?? null;
        $procReturnCode = $responseData['ProcReturnCode'] ?? null;
        $responseHash = $responseData['HASH'] ?? $responseData['hash'] ?? null;
        $mdStatus = $responseData['mdStatus'] ?? null;

        // Find the transaction
        $transaction = $this->findTransactionByReference($oid);

        $statusBefore = $transaction?->status?->value;

        // Verify the response hash to prevent tampering
        $signedResponseData = $this->filterSignedResponseData($responseData);

        if (!$this->verifyResponseHash($signedResponseData, $hashKey, $storeId, $responseHash)) {
            $this->logger->logCallback(
                self::GATEWAY_ID, $request, $responseData,
                isSuccessful: false,
                gatewayReference: $oid,
                orderId: $transaction?->order_id,
                transactionId: $transaction?->id,
                errorMessage: 'Hash verification failed. Possible tampering detected.',
                errorCode: 'HASH_MISMATCH',
                statusBefore: $statusBefore,
                statusAfter: PaymentStatus::FAILED->value,
            );

            if ($transaction) {
                $transaction->update([
                    'status' => PaymentStatus::FAILED,
                    'error_message' => 'Hash verification failed',
                    'error_code' => 'HASH_MISMATCH',
                ]);
            }

            return PaymentResponse::failure('Payment verification failed. The response signature is invalid.', $signedResponseData);
        }

        // Check if 3D Secure and transaction were successful
        // mdStatus: 1 = full 3D auth, 2/5/6/7 = partial, 9 = attempted
        // ProcReturnCode: 00 = success
        $isApproved = $procReturnCode === '00' && in_array($mdStatus, ['1', '2', '5', '6', '7', '9']);

        if ($isApproved && $transaction) {
            $transaction->update([
                'status' => PaymentStatus::CAPTURED,
                'payload' => array_merge($transaction->payload ?? [], [
                    'proc_return_code' => $procReturnCode,
                    'md_status' => $mdStatus,
                    'auth_code' => $responseData['AuthCode'] ?? null,
                    'trans_id' => $responseData['TransId'] ?? null,
                ]),
            ]);

            $this->logger->logCallback(
                self::GATEWAY_ID, $request, $responseData,
                isSuccessful: true,
                gatewayReference: $oid,
                orderId: $transaction->order_id,
                transactionId: $transaction->id,
                statusBefore: $statusBefore,
                statusAfter: PaymentStatus::CAPTURED->value,
            );

            return PaymentResponse::success($oid, $signedResponseData);
        }

        // Payment was declined or 3D auth failed
        $errorMsg = $responseData['ErrMsg'] ?? $responseData['mdErrorMsg'] ?? 'Payment was declined by the issuing bank.';

        if ($transaction) {
            $transaction->update([
                'status' => PaymentStatus::FAILED,
                'error_code' => $procReturnCode,
                'error_message' => $errorMsg,
            ]);
        }

        $this->logger->logCallback(
            self::GATEWAY_ID, $request, $responseData,
            isSuccessful: false,
            gatewayReference: $oid,
            orderId: $transaction?->order_id,
            transactionId: $transaction?->id,
            errorMessage: $errorMsg,
            errorCode: $procReturnCode,
            statusBefore: $statusBefore,
            statusAfter: PaymentStatus::FAILED->value,
        );

        return PaymentResponse::failure($errorMsg, $signedResponseData);
    }

    /**
     * Handle the asynchronous server-to-server callback from CMI.
     */
    public function handleWebhook(Request $request): PaymentResponse
    {
        $responseData = $request->all();
        $setting = $this->getSetting();

        if (!$setting) {
            return PaymentResponse::failure('CMI gateway configuration not found.');
        }

        $hashKey = $setting->getCredential('hash_key');
        $storeId = $setting->getCredential('store_id');
        $oid = $responseData['oid'] ?? null;
        $responseHash = $responseData['HASH'] ?? $responseData['hash'] ?? null;

        $transaction = $this->findTransactionByReference($oid);

        // Verify hash
        $signedResponseData = $this->filterSignedResponseData($responseData);

        if (!$this->verifyResponseHash($signedResponseData, $hashKey, $storeId, $responseHash)) {
            $this->logger->logWebhook(
                self::GATEWAY_ID, $request, $responseData,
                isSuccessful: false,
                gatewayReference: $oid,
                orderId: $transaction?->order_id,
                transactionId: $transaction?->id,
                errorMessage: 'Webhook hash verification failed.',
                statusBefore: $transaction?->status?->value,
                statusAfter: $transaction?->status?->value,
            );
            return PaymentResponse::failure('Webhook hash verification failed.', $signedResponseData);
        }

        $procReturnCode = $responseData['ProcReturnCode'] ?? null;
        $isApproved = $procReturnCode === '00';

        if ($isApproved && $transaction) {
            $statusBefore = $transaction->status->value;

            // Only upgrade status if not already captured (idempotency)
            if ($transaction->status !== PaymentStatus::CAPTURED) {
                $transaction->update([
                    'status' => PaymentStatus::CAPTURED,
                    'payload' => array_merge($transaction->payload ?? [], $signedResponseData),
                ]);

                $this->logger->logStatusChange(
                    self::GATEWAY_ID,
                    $transaction->id,
                    $statusBefore,
                    PaymentStatus::CAPTURED->value,
                    $transaction->order_id,
                    $oid,
                    'Webhook confirmed payment capture.'
                );
            }

            $this->logger->logWebhook(
                self::GATEWAY_ID, $request, $responseData,
                isSuccessful: true,
                gatewayReference: $oid,
                orderId: $transaction->order_id,
                transactionId: $transaction->id,
                statusBefore: $statusBefore,
                statusAfter: PaymentStatus::CAPTURED->value,
            );

            return PaymentResponse::success($oid, $signedResponseData);
        }

        $this->logger->logWebhook(
            self::GATEWAY_ID, $request, $responseData,
            isSuccessful: false,
            gatewayReference: $oid,
            orderId: $transaction?->order_id,
            transactionId: $transaction?->id,
            errorMessage: $responseData['ErrMsg'] ?? 'Webhook reported failure.',
        );

        return PaymentResponse::failure($responseData['ErrMsg'] ?? 'Webhook reported payment failure.', $signedResponseData);
    }

    /**
     * CMI does not support automated refunds via API in all configurations.
     */
    public function refund(string $gatewayReference, float $amount): PaymentResponse
    {
        $this->logger->logFailure(self::GATEWAY_ID, 'Automated refunds are not supported for CMI. Please process manually via the CMI merchant portal.', 'REFUND_NOT_SUPPORTED');
        return PaymentResponse::failure('Automated refunds are not supported for CMI. Please process via the CMI merchant portal.');
    }

    // ──────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────

    private function getSetting(): ?GatewaySetting
    {
        return GatewaySetting::where('gateway_id', self::GATEWAY_ID)->first();
    }

    /**
     * Generate CMI hash using HMAC-SHA512.
     * CMI v3 protocol: hash = HMAC(storeKey, value1|value2|...|valueN)
     * Values are sorted alphabetically by key name, pipe-delimited.
     */
    private function generateHash(array $params, string $hashKey, string $storeId): string
    {
        // Remove hash-related keys before hashing
        $hashable = array_filter($params, fn($key) => !in_array(strtolower($key), ['hash', 'encoding']), ARRAY_FILTER_USE_KEY);

        // Sort by key name (case-insensitive)
        uksort($hashable, 'strcasecmp');

        // Pipe-delimit values and append storeId
        $hashString = implode('|', array_values($hashable)) . '|' . $storeId;

        // HMAC-SHA512, base64 encoded
        return base64_encode(hash_hmac('sha512', $hashString, $hashKey, true));
    }

    /**
     * Verify CMI response hash from callback/webhook.
     */
    private function verifyResponseHash(array $responseData, string $hashKey, string $storeId, ?string $receivedHash): bool
    {
        if (!$receivedHash) {
            return false;
        }

        // Recompute hash from response data (excluding the hash itself)
        $computed = $this->generateHash(
            collect($responseData)->except(['HASH', 'hash', 'encoding'])->toArray(),
            $hashKey,
            $storeId
        );

        return hash_equals($computed, $receivedHash);
    }

    /**
     * Strip local routing/query keys that are not part of CMI's signed payload.
     *
     * @param array<string, mixed> $responseData
     * @return array<string, mixed>
     */
    private function filterSignedResponseData(array $responseData): array
    {
        return collect($responseData)
            ->except([
                'status',
                '_token',
                '_method',
                'ref',
            ])
            ->toArray();
    }

    private function findTransactionByReference(?string $reference): ?PaymentTransaction
    {
        if (! $reference) {
            return null;
        }

        return PaymentTransaction::query()
            ->where('gateway', self::GATEWAY_ID)
            ->where(function ($query) use ($reference) {
                if (Schema::hasColumn('payment_transactions', 'gateway_reference')) {
                    $query->orWhere('gateway_reference', $reference);
                }

                if (Schema::hasColumn('payment_transactions', 'gateway_transaction_id')) {
                    $query->orWhere('gateway_transaction_id', $reference);
                }

                if (Schema::hasColumn('payment_transactions', 'reference')) {
                    $query->orWhere('reference', $reference);
                }

                if (Schema::hasColumn('payment_transactions', 'payload')) {
                    $query->orWhereJsonContains('payload->cmi_oid', $reference);
                }

                if (Schema::hasColumn('payment_transactions', 'metadata')) {
                    $query->orWhereJsonContains('metadata->cmi_oid', $reference)
                        ->orWhereJsonContains('metadata->gateway_reference', $reference);
                }
            })
            ->latest('id')
            ->first();
    }
}
