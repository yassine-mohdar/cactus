<?php

namespace App\Modules\Payments\Services;

use App\Modules\Payments\DTOs\PaymentResponse;
use Illuminate\Http\Request;
use Throwable;

class PaymentCallbackHandler
{
    public function __construct(
        private readonly PaymentGatewayRegistry $registry,
        private readonly PaymentResponseNormalizer $normalizer,
        private readonly PaymentLogger $logger,
    ) {}

    public function verify(string $gatewayId, Request $request): PaymentResponse
    {
        return $this->handle(
            gatewayId: $gatewayId,
            request: $request,
            callbackType: 'callback',
        );
    }

    public function webhook(string $gatewayId, Request $request): PaymentResponse
    {
        return $this->handle(
            gatewayId: $gatewayId,
            request: $request,
            callbackType: 'webhook',
        );
    }

    private function handle(string $gatewayId, Request $request, string $callbackType): PaymentResponse
    {
        try {
            $gateway = $this->registry->resolve($gatewayId);
            $response = $callbackType === 'webhook'
                ? $gateway->handleWebhook($request)
                : $gateway->verifyPayment($request);

            return $this->normalizer->normalize($response);
        } catch (Throwable $exception) {
            $errorCode = $callbackType === 'webhook' ? 'WEBHOOK_EXCEPTION' : 'CALLBACK_EXCEPTION';

            $this->logger->logFailure(
                gateway: $gatewayId,
                errorMessage: $exception->getMessage(),
                errorCode: $errorCode,
                requestPayload: $request->all(),
                gatewayReference: $this->extractGatewayReference($request),
            );

            report($exception);

            return PaymentResponse::failure(
                $callbackType === 'webhook'
                    ? 'We could not process the payment webhook.'
                    : 'We could not verify the payment callback.',
                $request->all(),
            );
        }
    }

    private function extractGatewayReference(Request $request): ?string
    {
        foreach (['ref', 'reference', 'oid', 'payment_id', 'session_id', 'id'] as $key) {
            $value = $request->input($key, $request->query($key));

            if (filled($value)) {
                return (string) $value;
            }
        }

        return null;
    }
}
