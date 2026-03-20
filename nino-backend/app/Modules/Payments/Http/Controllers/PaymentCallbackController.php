<?php

namespace App\Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Gateways\CmiPaymentGateway;
use App\Modules\Payments\Gateways\PayzonePaymentGateway;
use App\Modules\Payments\Gateways\StripePaymentGateway;
use App\Modules\Payments\Services\PaymentLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

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
    public function __construct(
        private readonly PaymentLogger $paymentLogger,
    ) {}

    // ──────────────────────────────────────────────────────────────
    // CMI
    // ──────────────────────────────────────────────────────────────

    public function cmiCallback(Request $request)
    {
        try {
            $response = app(CmiPaymentGateway::class)->verifyPayment($request);
        } catch (Throwable $exception) {
            $this->paymentLogger->logFailure(
                gateway: 'cmi',
                errorMessage: $exception->getMessage(),
                errorCode: 'CALLBACK_EXCEPTION',
                requestPayload: $request->all(),
            );

            report($exception);

            return redirect()->route('checkout.failed', [
                'ref' => $request->input('oid') ?? $request->query('ref'),
                'error' => 'We could not verify the payment callback.',
            ]);
        }

        if ($response->isSuccessful) {
            return redirect()->route('checkout.success', ['ref' => $response->gatewayReference]);
        }

        return redirect()->route('checkout.failed', ['ref' => $response->gatewayReference, 'error' => $response->message]);
    }

    public function cmiWebhook(Request $request)
    {
        try {
            $response = app(CmiPaymentGateway::class)->handleWebhook($request);
        } catch (Throwable $exception) {
            $this->paymentLogger->logFailure(
                gateway: 'cmi',
                errorMessage: $exception->getMessage(),
                errorCode: 'WEBHOOK_EXCEPTION',
                requestPayload: $request->all(),
            );

            report($exception);

            return response('FAILURE', Response::HTTP_OK)
                ->header('Content-Type', 'text/plain');
        }

        // CMI expects "ACTION=POSTAUTH" response for successful processing
        return response($response->isSuccessful ? 'ACTION=POSTAUTH' : 'FAILURE', 200)
            ->header('Content-Type', 'text/plain');
    }

    // ──────────────────────────────────────────────────────────────
    // PAYZONE
    // ──────────────────────────────────────────────────────────────

    public function payzoneCallback(Request $request)
    {
        try {
            $response = app(PayzonePaymentGateway::class)->verifyPayment($request);
        } catch (Throwable $exception) {
            $this->paymentLogger->logFailure(
                gateway: 'payzone',
                errorMessage: $exception->getMessage(),
                errorCode: 'CALLBACK_EXCEPTION',
                requestPayload: $request->all(),
            );

            report($exception);

            return redirect()->route('checkout.failed', [
                'ref' => $request->query('ref'),
                'error' => 'We could not verify the payment callback.',
            ]);
        }

        if ($response->isSuccessful) {
            return redirect()->route('checkout.success', ['ref' => $response->gatewayReference]);
        }

        return redirect()->route('checkout.failed', ['ref' => $request->query('ref'), 'error' => $response->message]);
    }

    public function payzoneWebhook(Request $request)
    {
        try {
            $response = app(PayzonePaymentGateway::class)->handleWebhook($request);
        } catch (Throwable $exception) {
            $this->paymentLogger->logFailure(
                gateway: 'payzone',
                errorMessage: $exception->getMessage(),
                errorCode: 'WEBHOOK_EXCEPTION',
                requestPayload: $request->all(),
            );

            report($exception);

            return response()->json(['status' => 'error'], 200);
        }

        return response()->json(['status' => $response->isSuccessful ? 'ok' : 'error'], 200);
    }

    // ──────────────────────────────────────────────────────────────
    // STRIPE
    // ──────────────────────────────────────────────────────────────

    public function stripeCallback(Request $request)
    {
        try {
            $response = app(StripePaymentGateway::class)->verifyPayment($request);
        } catch (Throwable $exception) {
            $this->paymentLogger->logFailure(
                gateway: 'stripe',
                errorMessage: $exception->getMessage(),
                errorCode: 'CALLBACK_EXCEPTION',
                requestPayload: $request->all(),
            );

            report($exception);

            return redirect()->route('checkout.failed', [
                'ref' => $request->query('ref'),
                'error' => 'We could not verify the payment callback.',
            ]);
        }

        if ($response->isSuccessful) {
            return redirect()->route('checkout.success', ['ref' => $response->gatewayReference]);
        }

        return redirect()->route('checkout.failed', ['ref' => $request->query('ref'), 'error' => $response->message]);
    }

    public function stripeWebhook(Request $request)
    {
        try {
            $response = app(StripePaymentGateway::class)->handleWebhook($request);
        } catch (Throwable $exception) {
            $this->paymentLogger->logFailure(
                gateway: 'stripe',
                errorMessage: $exception->getMessage(),
                errorCode: 'WEBHOOK_EXCEPTION',
                requestPayload: $request->all(),
            );

            report($exception);

            return response()->json(['received' => false], 200);
        }

        return response()->json(['received' => true], 200);
    }
}
