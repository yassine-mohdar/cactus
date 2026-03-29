<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Finance\Enums\PaymentMethod;
use App\Modules\Finance\Enums\TransactionStatus;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Gateways\CmiPaymentGateway;
use App\Modules\Payments\Gateways\OfflinePaymentGateway;
use App\Modules\Payments\Gateways\PayzonePaymentGateway;
use App\Modules\Payments\Gateways\StripePaymentGateway;
use App\Modules\Payments\Models\PaymentTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentDomainFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_transaction_alias_uses_canonical_finance_schema_and_compatibility_fields(): void
    {
        $order = $this->makeOrder('ORD-PAY-DOMAIN-001');

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'type' => TransactionType::PAYMENT,
            'status' => PaymentStatus::CAPTURED,
            'payment_method' => PaymentMethod::STRIPE,
            'gateway' => 'stripe',
            'amount' => 150.00,
            'fee_amount' => 5.00,
            'net_amount' => 145.00,
            'currency' => 'MAD',
            'gateway_reference' => 'STRIPE-REF-001',
            'payload' => ['stripe_session_id' => 'cs_test_123'],
            'error_code' => 'NONE',
            'error_message' => 'Captured successfully',
        ]);

        $transaction->refresh();

        $this->assertSame(TransactionStatus::COMPLETED, $transaction->status);
        $this->assertSame('captured', $transaction->gatewayStatusValue());
        $this->assertSame('STRIPE-REF-001', $transaction->gateway_reference);
        $this->assertSame('STRIPE-REF-001', $transaction->gateway_transaction_id);
        $this->assertSame(['stripe_session_id' => 'cs_test_123', 'error_code' => 'NONE'], $transaction->payload);
        $this->assertSame('Captured successfully', $transaction->failure_reason);
    }

    public function test_order_maps_transactions_through_both_canonical_and_payment_alias_relations(): void
    {
        $order = $this->makeOrder('ORD-PAY-DOMAIN-002');

        PaymentTransaction::create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'type' => TransactionType::PAYMENT,
            'status' => TransactionStatus::PENDING,
            'payment_method' => PaymentMethod::BANK_TRANSFER,
            'gateway' => 'offline_transfer',
            'amount' => 150.00,
            'fee_amount' => 0.00,
            'net_amount' => 150.00,
            'currency' => 'MAD',
        ]);

        $order->refresh();

        $this->assertCount(1, $order->transactions);
        $this->assertCount(1, $order->paymentTransactions);
        $this->assertSame($order->transactions->first()?->id, $order->paymentTransactions->first()?->id);
    }

    public function test_gateways_expose_stable_internal_identifiers_through_the_contract(): void
    {
        $gateways = [
            new CmiPaymentGateway(),
            new PayzonePaymentGateway(),
            new StripePaymentGateway(),
            new OfflinePaymentGateway(),
        ];

        foreach ($gateways as $gateway) {
            $this->assertInstanceOf(PaymentGatewayInterface::class, $gateway);
            $this->assertNotSame('', $gateway->gatewayId());
        }

        $this->assertSame(['cmi', 'payzone', 'stripe', 'offline_transfer'], array_map(
            fn (PaymentGatewayInterface $gateway) => $gateway->gatewayId(),
            $gateways,
        ));
    }

    private function makeOrder(string $referenceNumber): Order
    {
        $customer = User::factory()->create([
            'name' => 'Payment Domain Customer',
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
            'payment_method' => 'stripe',
            'shipping_method' => 'standard',
        ]);
    }
}
