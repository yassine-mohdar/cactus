<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Gateways\CmiPaymentGateway;
use App\Modules\Payments\Gateways\OfflinePaymentGateway;
use App\Modules\Payments\Gateways\PayzonePaymentGateway;
use App\Modules\Payments\Gateways\StripePaymentGateway;
use App\Modules\Payments\Models\GatewaySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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
}
