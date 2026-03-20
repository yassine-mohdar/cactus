<?php

namespace App\Modules\Payments\Gateways;

use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use App\Modules\Payments\DTOs\PaymentResponse;
use App\Modules\Payments\Models\GatewaySetting;
use App\Modules\Payments\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OfflinePaymentGateway implements PaymentGatewayInterface
{
    private const GATEWAY_ID = 'offline_transfer';

    public function initiatePayment(Order $order): PaymentResponse
    {
        // 1. Fetch Admin defined bank instructions (if any)
        $setting = GatewaySetting::where('gateway_id', self::GATEWAY_ID)->first();
        $instructions = $setting->metadata['instructions'] ?? 'Your order has been recorded. We will contact you shortly for payment.';

        // 2. Generate a unique offline reference for tracing
        $reference = 'OFFLINE-' . now()->format('Ymd') . '-' . Str::random(6);

        // 3. Register the intent in the database preventing order tampering
        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'gateway' => self::GATEWAY_ID,
            'status' => 'pending', // Awaiting manual admin verification
            'amount' => $order->grand_total,
            'currency' => $order->currency,
            'gateway_reference' => $reference,
            'payload' => [
                'instructions_shown' => $instructions
            ]
        ]);

        return new PaymentResponse(
            isSuccessful: true,
            status: 'pending', // Not 'captured', as funds aren't technically settled
            gatewayReference: $reference,
            message: $instructions,
            rawPayload: $transaction->toArray()
        );
    }

    public function verifyPayment(Request $request): PaymentResponse
    {
        // Offline payments don't have automated browser callbacks. This is a no-op protecting the interface.
        return PaymentResponse::failure('Offline methods do not support automated browser verification.');
    }

    public function handleWebhook(Request $request): PaymentResponse
    {
        // Offline payments don't have webhooks.
        return PaymentResponse::failure('Offline methods do not support automated webhooks.');
    }

    public function refund(string $gatewayReference, float $amount): PaymentResponse
    {
        // Refunding an offline payment requires the admin to manually send money back. We just update the DB status elsewhere.
        return PaymentResponse::failure('Offline methods cannot be refunded automatically. Please refund manually and update the status.');
    }
}
