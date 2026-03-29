<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Finance\Enums\PaymentMethod;
use App\Modules\Finance\Enums\TransactionStatus;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\PaymentTransaction;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Services\PaymentLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentLoggingFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_logger_redacts_sensitive_request_and_response_payloads(): void
    {
        $logger = new PaymentLogger();
        $order = $this->makeOrder();

        $log = $logger->logInitiation('stripe', $order, [
            'card_number' => '4242424242424242',
            'cvv' => '123',
            'secret_key' => 'sk_live_secret',
            'api_key' => 'api_live_secret',
            'nested' => [
                'webhook_secret' => 'whsec_secret',
                'client_secret' => 'client_secret_value',
            ],
        ]);

        $this->assertSame('***REDACTED***', data_get($log->request_payload, 'card_number'));
        $this->assertSame('***REDACTED***', data_get($log->request_payload, 'cvv'));
        $this->assertSame('***REDACTED***', data_get($log->request_payload, 'secret_key'));
        $this->assertSame('***REDACTED***', data_get($log->request_payload, 'api_key'));
        $this->assertSame('***REDACTED***', data_get($log->request_payload, 'nested.webhook_secret'));
        $this->assertSame('***REDACTED***', data_get($log->request_payload, 'nested.client_secret'));
    }

    public function test_payment_logger_persists_failure_logs_with_gateway_context(): void
    {
        $logger = new PaymentLogger();
        $order = $this->makeOrder();

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'type' => TransactionType::PAYMENT,
            'status' => TransactionStatus::PENDING,
            'payment_method' => PaymentMethod::STRIPE,
            'gateway' => 'stripe',
            'amount' => 250,
            'currency' => 'MAD',
            'gateway_reference' => 'STRIPE-FAIL-001',
        ]);

        $log = $logger->logFailure(
            gateway: 'stripe',
            errorMessage: 'Gateway timeout.',
            errorCode: 'TIMEOUT',
            orderId: $order->id,
            transactionId: $transaction->id,
            requestPayload: ['api_key' => 'secret-timeout-key'],
            responsePayload: ['message' => 'upstream timeout'],
            gatewayReference: 'STRIPE-FAIL-001',
            amount: 250,
            currency: 'MAD',
        );

        $this->assertSame('error', $log->event_type);
        $this->assertFalse($log->is_successful);
        $this->assertSame('TIMEOUT', $log->error_code);
        $this->assertSame('Gateway timeout.', $log->error_message);
        $this->assertSame('***REDACTED***', data_get($log->request_payload, 'api_key'));
    }

    public function test_payment_logger_records_status_change_audit_rows(): void
    {
        $logger = new PaymentLogger();
        $order = $this->makeOrder();

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'type' => TransactionType::PAYMENT,
            'status' => TransactionStatus::PENDING,
            'payment_method' => PaymentMethod::BANK_TRANSFER,
            'gateway' => 'offline_transfer',
            'amount' => 250,
            'currency' => 'MAD',
            'gateway_reference' => 'OFFLINE-STATUS-001',
        ]);

        $log = $logger->logStatusChange(
            gateway: 'offline_transfer',
            transactionId: $transaction->id,
            statusBefore: TransactionStatus::PENDING->value,
            statusAfter: TransactionStatus::COMPLETED->value,
            orderId: $order->id,
            gatewayReference: 'OFFLINE-STATUS-001',
            reason: 'manual offline verification',
        );

        $this->assertSame('status_change', $log->event_type);
        $this->assertTrue($log->is_successful);
        $this->assertSame(TransactionStatus::PENDING->value, $log->status_before);
        $this->assertSame(TransactionStatus::COMPLETED->value, $log->status_after);
        $this->assertSame('manual offline verification', $log->error_message);
    }

    private function makeOrder(): Order
    {
        $customer = User::factory()->customer()->create([
            'status' => 'active',
        ]);

        return Order::create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::AWAITING_PAYMENT,
            'currency' => 'MAD',
            'subtotal' => 200,
            'tax_total' => 0,
            'shipping_total' => 50,
            'discount_total' => 0,
            'grand_total' => 250,
            'payment_method' => PaymentMethod::STRIPE->value,
            'shipping_method' => 'standard',
        ]);
    }
}
