<?php

namespace App\Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Gateways\CmiPaymentGateway;
use App\Modules\Payments\Gateways\PayzonePaymentGateway;
use App\Modules\Payments\Gateways\StripePaymentGateway;
use Illuminate\Http\Request;

/**
 * Handles synchronous browser callbacks and asynchronous webhooks
 * from all connected payment gateways.
 *
 * These endpoints are public-facing (no auth middleware) because
 * gateways redirect customers and send server-to-server notifications
 * without any authentication context from NinoWorld.
 */
class PaymentCallbackController extends Controller
{
    // ──────────────────────────────────────────────────────────────
    // CMI
    // ──────────────────────────────────────────────────────────────

    public function cmiCallback(Request $request)
    {
        $gateway = new CmiPaymentGateway();
        $response = $gateway->verifyPayment($request);

        if ($response->isSuccessful) {
            return redirect()->route('checkout.success', ['ref' => $response->gatewayReference]);
        }

        return redirect()->route('checkout.failed', ['ref' => $response->gatewayReference, 'error' => $response->message]);
    }

    public function cmiWebhook(Request $request)
    {
        $gateway = new CmiPaymentGateway();
        $response = $gateway->handleWebhook($request);

        // CMI expects "ACTION=POSTAUTH" response for successful processing
        return response($response->isSuccessful ? 'ACTION=POSTAUTH' : 'FAILURE', 200)
            ->header('Content-Type', 'text/plain');
    }

    // ──────────────────────────────────────────────────────────────
    // PAYZONE
    // ──────────────────────────────────────────────────────────────

    public function payzoneCallback(Request $request)
    {
        $gateway = new PayzonePaymentGateway();
        $response = $gateway->verifyPayment($request);

        if ($response->isSuccessful) {
            return redirect()->route('checkout.success', ['ref' => $response->gatewayReference]);
        }

        return redirect()->route('checkout.failed', ['ref' => $request->query('ref'), 'error' => $response->message]);
    }

    public function payzoneWebhook(Request $request)
    {
        $gateway = new PayzonePaymentGateway();
        $response = $gateway->handleWebhook($request);

        return response()->json(['status' => $response->isSuccessful ? 'ok' : 'error'], 200);
    }

    // ──────────────────────────────────────────────────────────────
    // STRIPE
    // ──────────────────────────────────────────────────────────────

    public function stripeCallback(Request $request)
    {
        $gateway = new StripePaymentGateway();
        $response = $gateway->verifyPayment($request);

        if ($response->isSuccessful) {
            return redirect()->route('checkout.success', ['ref' => $response->gatewayReference]);
        }

        return redirect()->route('checkout.failed', ['ref' => $request->query('ref'), 'error' => $response->message]);
    }

    public function stripeWebhook(Request $request)
    {
        $gateway = new StripePaymentGateway();
        $response = $gateway->handleWebhook($request);

        return response()->json(['received' => true], 200);
    }
}
