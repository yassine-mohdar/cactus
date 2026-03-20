<?php

namespace App\Modules\Payments\Contracts;

use App\Modules\Orders\Models\Order;
use App\Modules\Payments\DTOs\PaymentResponse;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Bootstraps the payment attempt. 
     * For Stripe, this generates a Checkout Session URL.
     * For CMI, this generates the payload hash required for their hosted form.
     */
    public function initiatePayment(Order $order): PaymentResponse;

    /**
     * Resolves the synchronous redirect from a gateway (Callback URL).
     * Typically validates a signature in the URL to ensure it wasn't tampered with.
     */
    public function verifyPayment(Request $request): PaymentResponse;

    /**
     * Handles the asynchronous server-to-server webhook.
     * Guaranteed way to capture funds even if the user closes their browser post-payment.
     */
    public function handleWebhook(Request $request): PaymentResponse;

    /**
     * Optional: Support reversing a settled transaction.
     */
    public function refund(string $gatewayReference, float $amount): PaymentResponse;
}
