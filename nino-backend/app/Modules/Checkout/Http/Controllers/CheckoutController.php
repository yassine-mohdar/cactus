<?php

namespace App\Modules\Checkout\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Checkout\Exceptions\CheckoutException;
use App\Modules\Checkout\Http\Requests\ProcessCheckoutRequest;
use App\Modules\Checkout\Services\CheckoutService;
use App\Modules\Payments\Gateways\OfflinePaymentGateway;
use App\Modules\Payments\DTOs\PaymentResponse;
use App\Modules\Payments\Services\PaymentGatewayRegistry;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayRegistry $paymentGatewayRegistry,
    ) {}

    public function process(ProcessCheckoutRequest $request, CheckoutService $service)
    {
        try {
            $order = $service->processCheckout(
                $request->validated(),
                $request->user('sanctum'),
                $request->header('X-Cart-Session-Id', $request->input('cart_session_id'))
            );
            $payment = $this->buildCheckoutPaymentPayload($order);

            return response()->json([
                'message' => 'Checkout processed successfully',
                'order_reference' => $order->reference_number,
                'thank_you_url' => URL::temporarySignedRoute(
                    'checkout.success.signed',
                    now()->addHours(24),
                    ['reference' => $order->reference_number],
                ),
                'account' => [
                    'customer_email' => $order->customer?->email,
                    'account_created' => ! (bool) $request->user(),
                    'next_step' => $request->user()
                        ? 'View this order from your account workspace.'
                        : 'Check your email for your secure password setup link to access your new account.',
                    'account_home_url' => $request->user() ? route('customer.account.home') : null,
                ],
                'payment' => $payment,
                // Do not return raw DB IDs, only the reference for the frontend to track
            ], 201);

        } catch (CheckoutException $e) {
            Log::warning('Checkout processing rejected', [
                'error' => $e->getMessage(),
                'error_code' => $e->errorCode,
                'context' => $e->context,
            ]);

            return response()->json([
                'message' => 'Checkout failed.',
                'error' => $e->getMessage(),
                'error_code' => $e->errorCode,
                'context' => $e->context,
            ], 422);
        } catch (Exception $e) {
            Log::error('Checkout processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Checkout failed.',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.',
            ], 422);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildCheckoutPaymentPayload($order): ?array
    {
        $gateway = $this->paymentGatewayRegistry->resolveForPaymentMethod($order->payment_method);

        if (! $gateway) {
            return null;
        }

        $response = $gateway->initiatePayment($order);

        return $gateway instanceof OfflinePaymentGateway
            ? $this->offlinePaymentPayload($gateway, $response)
            : $this->onlinePaymentPayload($gateway->gatewayId(), $response);
    }

    private function offlinePaymentPayload(OfflinePaymentGateway $gateway, PaymentResponse $response): array
    {
        $details = $response->rawPayload['offline_method'] ?? $gateway->checkoutDetails(reference: $response->gatewayReference);

        return [
            'gateway' => $gateway->gatewayId(),
            'method_code' => $details['method_code'] ?? 'bank_transfer',
            'status' => $response->status,
            'reference' => $response->gatewayReference,
            'message' => $response->message,
            'offline_method' => $details,
        ];
    }

    private function onlinePaymentPayload(string $gatewayId, PaymentResponse $response): array
    {
        $rawPayload = $response->rawPayload ?? [];
        $gatewayUrl = $response->redirectUrl ?? data_get($rawPayload, 'gateway_url');

        return [
            'gateway' => $gatewayId,
            'status' => $response->status,
            'reference' => $response->gatewayReference,
            'message' => $response->message,
            'redirect_url' => $gatewayUrl,
            'redirect_method' => $gatewayId === 'cmi' ? 'POST' : 'GET',
            'form_fields' => $gatewayId === 'cmi' ? Arr::except($rawPayload, ['gateway_url']) : [],
            'raw' => $gatewayId === 'cmi' ? [] : $rawPayload,
        ];
    }
}
