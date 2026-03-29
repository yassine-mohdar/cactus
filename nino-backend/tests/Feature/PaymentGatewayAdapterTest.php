<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Gateways\CmiPaymentGateway;
use App\Modules\Payments\Gateways\OfflinePaymentGateway;
use App\Modules\Payments\Gateways\PayzonePaymentGateway;
use App\Modules\Payments\Gateways\StripePaymentGateway;
use App\Modules\Payments\Models\GatewaySetting;
use App\Modules\Payments\Models\PaymentTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentGatewayAdapterTest extends TestCase
{
    use RefreshDatabase;

    public function test_offline_gateway_rejects_automated_verification_and_refunds(): void
    {
        $gateway = new OfflinePaymentGateway();

        $verification = $gateway->verifyPayment(Request::create('/payment/offline/callback', 'GET'));
        $webhook = $gateway->handleWebhook(Request::create('/payment/offline/webhook', 'POST'));
        $refund = $gateway->refund('OFFLINE-REF-001', 150.0);

        $this->assertFalse($verification->isSuccessful);
        $this->assertSame('failed', $verification->status);
        $this->assertSame('Offline methods do not support automated browser verification.', $verification->message);

        $this->assertFalse($webhook->isSuccessful);
        $this->assertSame('Offline methods do not support automated webhooks.', $webhook->message);

        $this->assertFalse($refund->isSuccessful);
        $this->assertSame('Offline methods cannot be refunded automatically. Please refund manually and update the status.', $refund->message);
    }

    public function test_offline_gateway_initiation_creates_pending_bank_transfer_transaction_with_checkout_details(): void
    {
        GatewaySetting::create([
            'gateway_id' => 'offline_transfer',
            'name' => 'Offline Bank Transfer',
            'is_enabled' => true,
            'mode' => 'live',
            'credentials' => [],
            'metadata' => [
                'method_label' => 'Manual Bank Transfer',
                'checkout_title' => 'Pay by bank transfer',
                'checkout_description' => 'Use the company banking details below to complete payment.',
                'instructions' => 'Transfer the funds and include the provided reference.',
                'admin_instructions' => 'Finance should confirm the transfer before fulfilment.',
                'bank_name' => 'Attijariwafa Bank',
                'account_holder' => 'NinoWorld SARL AU',
                'iban' => 'MA64001122334455667788990011',
                'payment_window_hours' => 72,
                'reference_prefix' => 'NW',
                'require_receipt' => true,
            ],
        ]);

        $order = $this->makeOrder('ORD-OFFLINE-001');

        $response = (new OfflinePaymentGateway())->initiatePayment($order);

        $this->assertTrue($response->isSuccessful);
        $this->assertSame('pending', $response->status);
        $this->assertNotNull($response->gatewayReference);
        $this->assertStringContainsString('Manual Bank Transfer', $response->message ?? '');
        $this->assertSame('bank_transfer', data_get($response->rawPayload, 'offline_method.method_code'));
        $this->assertSame('Pay by bank transfer', data_get($response->rawPayload, 'offline_method.checkout_title'));

        $this->assertDatabaseHas('payment_transactions', [
            'order_id' => $order->id,
            'gateway' => 'offline_transfer',
            'gateway_transaction_id' => $response->gatewayReference,
        ]);
    }

    public function test_cmi_gateway_fails_fast_when_the_gateway_is_disabled_or_missing(): void
    {
        $order = $this->makeOrder('ORD-CMI-001');

        $response = (new CmiPaymentGateway())->initiatePayment($order);

        $this->assertFalse($response->isSuccessful);
        $this->assertSame('failed', $response->status);
        $this->assertSame('CMI payment gateway is not available.', $response->message);

        $this->assertDatabaseHas('payment_logs', [
            'gateway' => 'cmi',
            'event_type' => 'error',
            'error_code' => 'GATEWAY_DISABLED',
            'order_id' => $order->id,
        ]);
    }

    public function test_payzone_gateway_fails_fast_when_required_credentials_are_missing(): void
    {
        GatewaySetting::create([
            'gateway_id' => 'payzone',
            'name' => 'Payzone',
            'is_enabled' => true,
            'mode' => 'test',
            'credentials' => [
                'api_key' => 'payzone-api-key-only',
            ],
            'metadata' => [
                'currency' => 'MAD',
            ],
        ]);

        $order = $this->makeOrder('ORD-PAYZONE-001');

        $response = (new PayzonePaymentGateway())->initiatePayment($order);

        $this->assertFalse($response->isSuccessful);
        $this->assertSame('failed', $response->status);
        $this->assertSame('Payzone gateway credentials are incomplete.', $response->message);

        $this->assertDatabaseHas('payment_logs', [
            'gateway' => 'payzone',
            'event_type' => 'error',
            'error_code' => 'MISSING_CREDENTIALS',
            'order_id' => $order->id,
        ]);
    }

    public function test_payzone_callback_ignores_local_ref_query_when_verifying_signed_response(): void
    {
        GatewaySetting::create([
            'gateway_id' => 'payzone',
            'name' => 'Payzone',
            'is_enabled' => true,
            'mode' => 'test',
            'credentials' => [
                'api_key' => 'payzone-api-key',
                'secret_key' => 'payzone-secret',
                'merchant_id' => 'merchant-001',
            ],
            'metadata' => [
                'currency' => 'MAD',
            ],
        ]);

        $order = $this->makeOrder('ORD-PAYZONE-CALLBACK-001');

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'gateway' => 'payzone',
            'status' => 'pending',
            'amount' => $order->grand_total,
            'currency' => $order->currency,
            'gateway_reference' => 'PZ-CALLBACK-REF-001',
            'payload' => ['payzone_ref' => 'PZ-CALLBACK-REF-001'],
        ]);

        $signedPayload = [
            'payment_status' => 'approved',
            'payment_id' => 'PZ-CALLBACK-REF-001',
            'order_id' => 'PZ-CALLBACK-REF-001',
        ];
        $signedPayload['signature'] = $this->computePayzoneSignature($signedPayload, 'payzone-secret');

        $response = $this->get(route('payment.payzone.callback', ['ref' => 'PZ-CALLBACK-REF-001']) . '&' . http_build_query($signedPayload));

        $response->assertRedirect(route('checkout.success', ['ref' => $order->reference_number]));

        $transaction->refresh();

        $this->assertSame('completed', $transaction->status->value);

        $this->assertDatabaseHas('payment_logs', [
            'gateway' => 'payzone',
            'event_type' => 'callback',
            'is_successful' => true,
            'gateway_reference' => 'PZ-CALLBACK-REF-001',
        ]);
    }

    public function test_payzone_webhook_verifies_signature_and_captures_pending_transaction(): void
    {
        GatewaySetting::create([
            'gateway_id' => 'payzone',
            'name' => 'Payzone',
            'is_enabled' => true,
            'mode' => 'test',
            'credentials' => [
                'api_key' => 'payzone-api-key',
                'secret_key' => 'payzone-secret',
                'merchant_id' => 'merchant-001',
            ],
            'metadata' => [
                'currency' => 'MAD',
            ],
        ]);

        $order = $this->makeOrder('ORD-PAYZONE-WEBHOOK-001');

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'gateway' => 'payzone',
            'status' => 'pending',
            'amount' => $order->grand_total,
            'currency' => $order->currency,
            'gateway_reference' => 'PZ-WEBHOOK-REF-001',
            'payload' => ['payzone_payment_id' => 'PZ-WEBHOOK-REF-001'],
        ]);

        $signedPayload = [
            'payment_id' => 'PZ-WEBHOOK-REF-001',
            'payment_status' => 'captured',
        ];
        $signedPayload['signature'] = $this->computePayzoneSignature($signedPayload, 'payzone-secret');

        $this->post(route('payment.payzone.webhook'), $signedPayload)
            ->assertOk()
            ->assertJson(['status' => 'ok']);

        $transaction->refresh();

        $this->assertSame('completed', $transaction->status->value);

        $this->assertDatabaseHas('payment_logs', [
            'gateway' => 'payzone',
            'event_type' => 'webhook',
            'is_successful' => true,
            'gateway_reference' => 'PZ-WEBHOOK-REF-001',
        ]);
    }

    public function test_stripe_gateway_fails_fast_when_secret_key_is_missing(): void
    {
        GatewaySetting::create([
            'gateway_id' => 'stripe',
            'name' => 'Stripe',
            'is_enabled' => true,
            'mode' => 'test',
            'credentials' => [],
            'metadata' => [
                'currency' => 'mad',
            ],
        ]);

        $order = $this->makeOrder('ORD-STRIPE-001');

        $response = (new StripePaymentGateway())->initiatePayment($order);

        $this->assertFalse($response->isSuccessful);
        $this->assertSame('failed', $response->status);
        $this->assertSame('Stripe gateway credentials are incomplete.', $response->message);

        $this->assertDatabaseHas('payment_logs', [
            'gateway' => 'stripe',
            'event_type' => 'error',
            'error_code' => 'MISSING_CREDENTIALS',
            'order_id' => $order->id,
        ]);
    }

    public function test_stripe_webhook_with_valid_signature_captures_transaction_and_promotes_order(): void
    {
        GatewaySetting::create([
            'gateway_id' => 'stripe',
            'name' => 'Stripe',
            'is_enabled' => true,
            'mode' => 'test',
            'credentials' => [
                'secret_key' => 'sk_test_valid_secret',
                'webhook_secret' => 'whsec_test_secret',
            ],
            'metadata' => [
                'publishable_key' => 'pk_test_123',
                'currency' => 'mad',
            ],
        ]);

        $order = $this->makeOrder('ORD-STRIPE-WEBHOOK-001');
        $order->update(['status' => OrderStatus::AWAITING_PAYMENT]);

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'gateway' => 'stripe',
            'status' => 'pending',
            'amount' => $order->grand_total,
            'currency' => $order->currency,
            'gateway_reference' => 'STRIPE-CHECKOUT-REF-001',
            'payload' => ['stripe_ref' => 'STRIPE-CHECKOUT-REF-001'],
        ]);

        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_123',
                    'client_reference_id' => 'STRIPE-CHECKOUT-REF-001',
                    'payment_intent' => 'pi_test_paid_123',
                    'payment_status' => 'paid',
                    'metadata' => [
                        'nino_order_id' => (string) $order->id,
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $signatureHeader = $this->computeStripeSignatureHeader($payload, 'whsec_test_secret');

        $this->call(
            'POST',
            route('payment.stripe.webhook'),
            [],
            [],
            [],
            [
                'HTTP_Stripe-Signature' => $signatureHeader,
                'CONTENT_TYPE' => 'application/json',
            ],
            $payload
        )
            ->assertOk()
            ->assertJson(['received' => true]);

        $transaction->refresh();
        $order->refresh();

        $this->assertSame('completed', $transaction->status->value);
        $this->assertSame(OrderStatus::PAID, $order->status);

        $this->assertDatabaseHas('payment_logs', [
            'gateway' => 'stripe',
            'event_type' => 'webhook',
            'is_successful' => true,
            'gateway_reference' => 'pi_test_paid_123',
        ]);
    }

    public function test_stripe_webhook_rejects_invalid_signature_and_logs_failure(): void
    {
        GatewaySetting::create([
            'gateway_id' => 'stripe',
            'name' => 'Stripe',
            'is_enabled' => true,
            'mode' => 'test',
            'credentials' => [
                'secret_key' => 'sk_test_valid_secret',
                'webhook_secret' => 'whsec_test_secret',
            ],
            'metadata' => [
                'publishable_key' => 'pk_test_123',
                'currency' => 'mad',
            ],
        ]);

        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_invalid',
                    'client_reference_id' => 'STRIPE-CHECKOUT-INVALID',
                    'payment_intent' => 'pi_test_invalid',
                    'payment_status' => 'paid',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->call(
            'POST',
            route('payment.stripe.webhook'),
            [],
            [],
            [],
            [
                'HTTP_Stripe-Signature' => 't='.time().',v1='.Str::random(64),
                'CONTENT_TYPE' => 'application/json',
            ],
            $payload
        )
            ->assertOk()
            ->assertJson(['received' => true]);

        $this->assertDatabaseHas('payment_logs', [
            'gateway' => 'stripe',
            'event_type' => 'webhook',
            'is_successful' => false,
            'error_message' => 'Webhook signature verification failed.',
        ]);
    }

    public function test_cmi_gateway_initiates_payment_with_signed_payload_and_terminal_support(): void
    {
        GatewaySetting::create([
            'gateway_id' => 'cmi',
            'name' => 'CMI',
            'is_enabled' => true,
            'mode' => 'test',
            'credentials' => [
                'store_id' => 'STORE-123',
                'client_id' => 'CLIENT-456',
                'hash_key' => 'super-secret-hash-key',
                'terminal_id' => 'TERM-789',
            ],
            'metadata' => [
                'currency_code' => '504',
                'language' => 'fr',
            ],
        ]);

        $order = $this->makeOrder('ORD-CMI-INIT-001');

        $response = (new CmiPaymentGateway())->initiatePayment($order);

        $this->assertTrue($response->isSuccessful);
        $this->assertSame('pending', $response->status);
        $this->assertSame('https://testpayment.cmi.co.ma/fim/est3Dgate', $response->redirectUrl);
        $this->assertNotNull($response->gatewayReference);
        $this->assertSame('TERM-789', $response->rawPayload['terminalid'] ?? null);
        $this->assertNotEmpty($response->rawPayload['hash'] ?? null);

        $this->assertDatabaseHas('payment_transactions', [
            'order_id' => $order->id,
            'gateway' => 'cmi',
        ]);

        $this->assertDatabaseHas('payment_logs', [
            'gateway' => 'cmi',
            'event_type' => 'initiation',
            'order_id' => $order->id,
        ]);
    }

    public function test_cmi_callback_ignores_local_status_query_when_verifying_signed_response(): void
    {
        GatewaySetting::create([
            'gateway_id' => 'cmi',
            'name' => 'CMI',
            'is_enabled' => true,
            'mode' => 'test',
            'credentials' => [
                'store_id' => 'STORE-123',
                'client_id' => 'CLIENT-456',
                'hash_key' => 'super-secret-hash-key',
            ],
            'metadata' => [
                'currency_code' => '504',
                'language' => 'fr',
            ],
        ]);

        $order = $this->makeOrder('ORD-CMI-CALLBACK-001');

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'gateway' => 'cmi',
            'status' => 'pending',
            'amount' => $order->grand_total,
            'currency' => $order->currency,
            'gateway_reference' => 'CMI-CALLBACK-REF-001',
            'payload' => ['cmi_oid' => 'CMI-CALLBACK-REF-001'],
        ]);

        $signedPayload = [
            'oid' => 'CMI-CALLBACK-REF-001',
            'ProcReturnCode' => '00',
            'mdStatus' => '1',
            'AuthCode' => 'AUTH-123',
            'TransId' => 'TRANS-456',
        ];
        $signedPayload['HASH'] = $this->computeCmiHash($signedPayload, 'super-secret-hash-key', 'STORE-123');

        $response = $this->get(route('payment.cmi.callback', ['status' => 'success']) . '&' . http_build_query($signedPayload));

        $response->assertRedirect(route('checkout.success', ['ref' => $order->reference_number]));

        $transaction->refresh();
        $order->refresh();

        $this->assertSame('completed', $transaction->status->value);
        $this->assertSame(OrderStatus::PAID, $order->status);

        $this->assertDatabaseHas('payment_logs', [
            'gateway' => 'cmi',
            'event_type' => 'callback',
            'is_successful' => true,
            'gateway_reference' => 'CMI-CALLBACK-REF-001',
        ]);
    }

    public function test_cmi_webhook_captures_pending_transaction_and_returns_postauth_ack(): void
    {
        GatewaySetting::create([
            'gateway_id' => 'cmi',
            'name' => 'CMI',
            'is_enabled' => true,
            'mode' => 'test',
            'credentials' => [
                'store_id' => 'STORE-123',
                'client_id' => 'CLIENT-456',
                'hash_key' => 'super-secret-hash-key',
            ],
            'metadata' => [
                'currency_code' => '504',
                'language' => 'fr',
            ],
        ]);

        $order = $this->makeOrder('ORD-CMI-WEBHOOK-001');

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'gateway' => 'cmi',
            'status' => 'pending',
            'amount' => $order->grand_total,
            'currency' => $order->currency,
            'gateway_reference' => 'CMI-WEBHOOK-REF-001',
            'payload' => ['cmi_oid' => 'CMI-WEBHOOK-REF-001'],
        ]);

        $signedPayload = [
            'oid' => 'CMI-WEBHOOK-REF-001',
            'ProcReturnCode' => '00',
            'AuthCode' => 'AUTH-789',
            'TransId' => 'TRANS-999',
        ];
        $signedPayload['HASH'] = $this->computeCmiHash($signedPayload, 'super-secret-hash-key', 'STORE-123');

        $response = $this->post(route('payment.cmi.webhook'), $signedPayload);

        $response->assertOk();
        $response->assertSeeText('ACTION=POSTAUTH');

        $transaction->refresh();

        $this->assertSame('completed', $transaction->status->value);

        $this->assertDatabaseHas('payment_logs', [
            'gateway' => 'cmi',
            'event_type' => 'webhook',
            'is_successful' => true,
            'gateway_reference' => 'CMI-WEBHOOK-REF-001',
        ]);
    }

    private function makeOrder(string $referenceNumber): Order
    {
        $customer = User::factory()->create([
            'name' => 'Payment Test Customer',
            'type' => 'customer',
            'status' => 'active',
        ]);

        return Order::create([
            'reference_number' => $referenceNumber,
            'customer_id' => $customer->id,
            'status' => 'pending',
            'currency' => 'MAD',
            'subtotal' => 150,
            'tax_total' => 0,
            'shipping_total' => 0,
            'discount_total' => 0,
            'grand_total' => 150,
            'payment_method' => 'card',
            'shipping_method' => 'standard',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function computeCmiHash(array $payload, string $hashKey, string $storeId): string
    {
        unset($payload['HASH'], $payload['hash'], $payload['encoding']);

        uksort($payload, 'strcasecmp');

        $hashString = implode('|', array_values($payload)) . '|' . $storeId;

        return base64_encode(hash_hmac('sha512', $hashString, $hashKey, true));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function computePayzoneSignature(array $payload, string $secret): string
    {
        ksort($payload);

        return hash_hmac(
            'sha256',
            json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $secret
        );
    }

    private function computeStripeSignatureHeader(string $payload, string $secret): string
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return 't='.$timestamp.',v1='.$signature;
    }
}
