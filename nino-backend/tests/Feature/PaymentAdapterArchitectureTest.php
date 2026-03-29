<?php

namespace Tests\Feature;

use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use App\Modules\Payments\DTOs\PaymentResponse;
use App\Modules\Payments\Exceptions\UnsupportedPaymentGatewayException;
use App\Modules\Payments\Gateways\StripePaymentGateway;
use App\Modules\Payments\Services\PaymentCallbackHandler;
use App\Modules\Payments\Services\PaymentGatewayRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PaymentAdapterArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_gateway_registry_resolves_supported_gateways_and_normalizes_checkout_payment_methods(): void
    {
        $registry = app(PaymentGatewayRegistry::class);

        $this->assertSame(['cmi', 'payzone', 'stripe', 'offline_transfer'], $registry->supportedGatewayIds());
        $this->assertSame('stripe', $registry->resolveForPaymentMethod('stripe')?->gatewayId());
        $this->assertSame('offline_transfer', $registry->resolveForPaymentMethod('bank_transfer')?->gatewayId());
        $this->assertNull($registry->resolveForPaymentMethod('cash_on_delivery'));
        $this->assertNull($registry->resolveForPaymentMethod(''));
    }

    public function test_gateway_registry_rejects_unknown_gateway_ids(): void
    {
        $this->expectException(UnsupportedPaymentGatewayException::class);

        app(PaymentGatewayRegistry::class)->resolve('unknown_gateway');
    }

    public function test_callback_handler_normalizes_success_statuses_from_gateway_adapters(): void
    {
        app()->bind(StripePaymentGateway::class, fn () => new class implements PaymentGatewayInterface
        {
            public function gatewayId(): string
            {
                return 'stripe';
            }

            public function initiatePayment(Order $order): PaymentResponse
            {
                return new PaymentResponse(true, 'success');
            }

            public function verifyPayment(Request $request): PaymentResponse
            {
                return new PaymentResponse(true, 'completed', gatewayReference: 'pi_test_123');
            }

            public function handleWebhook(Request $request): PaymentResponse
            {
                return new PaymentResponse(true, 'paid', gatewayReference: 'pi_test_123');
            }

            public function refund(string $gatewayReference, float $amount): PaymentResponse
            {
                return new PaymentResponse(true, 'refunded', gatewayReference: $gatewayReference);
            }
        });

        $handler = app(PaymentCallbackHandler::class);

        $verified = $handler->verify('stripe', Request::create('/payment/stripe/callback', 'GET', ['ref' => 'pi_test_123']));
        $webhook = $handler->webhook('stripe', Request::create('/payment/stripe/webhook', 'POST', ['id' => 'evt_test_123']));

        $this->assertTrue($verified->isSuccessful);
        $this->assertSame('captured', $verified->status);
        $this->assertSame('pi_test_123', $verified->gatewayReference);

        $this->assertTrue($webhook->isSuccessful);
        $this->assertSame('captured', $webhook->status);
    }

    public function test_callback_handler_logs_and_normalizes_gateway_exceptions(): void
    {
        app()->bind(StripePaymentGateway::class, fn () => new class implements PaymentGatewayInterface
        {
            public function gatewayId(): string
            {
                return 'stripe';
            }

            public function initiatePayment(Order $order): PaymentResponse
            {
                throw new \RuntimeException('Gateway boot failure.');
            }

            public function verifyPayment(Request $request): PaymentResponse
            {
                throw new \RuntimeException('Broken callback signature.');
            }

            public function handleWebhook(Request $request): PaymentResponse
            {
                throw new \RuntimeException('Broken webhook payload.');
            }

            public function refund(string $gatewayReference, float $amount): PaymentResponse
            {
                throw new \RuntimeException('Refund unavailable.');
            }
        });

        $handler = app(PaymentCallbackHandler::class);

        $callback = $handler->verify('stripe', Request::create('/payment/stripe/callback', 'GET', ['ref' => 'pi_fail_123']));
        $webhook = $handler->webhook('stripe', Request::create('/payment/stripe/webhook', 'POST', ['id' => 'evt_fail_123']));

        $this->assertFalse($callback->isSuccessful);
        $this->assertSame('failed', $callback->status);
        $this->assertSame('We could not verify the payment callback.', $callback->message);

        $this->assertFalse($webhook->isSuccessful);
        $this->assertSame('failed', $webhook->status);
        $this->assertSame('We could not process the payment webhook.', $webhook->message);

        $this->assertDatabaseHas('payment_logs', [
            'gateway' => 'stripe',
            'event_type' => 'error',
            'error_code' => 'CALLBACK_EXCEPTION',
            'gateway_reference' => 'pi_fail_123',
        ]);

        $this->assertDatabaseHas('payment_logs', [
            'gateway' => 'stripe',
            'event_type' => 'error',
            'error_code' => 'WEBHOOK_EXCEPTION',
            'gateway_reference' => 'evt_fail_123',
        ]);
    }
}
