<?php

namespace App\Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Notifications\Services\NotificationTriggerService;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Payments\Models\PaymentTransaction;
use App\Modules\Payments\Services\PaymentCallbackHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;

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
        private readonly PaymentCallbackHandler $callbackHandler,
        private readonly InventoryService $inventoryService,
        private readonly NotificationTriggerService $notificationTriggerService,
    ) {}

    // ──────────────────────────────────────────────────────────────
    // CMI
    // ──────────────────────────────────────────────────────────────

    public function cmiCallback(Request $request)
    {
        $response = $this->callbackHandler->verify('cmi', $request);

        if ($response->isSuccessful) {
            $reference = $this->markOrderPaidForGatewaySuccess(
                gateway: 'cmi',
                gatewayReference: $response->gatewayReference ?? $request->input('oid') ?? $request->query('ref'),
            );

            return redirect()->route('checkout.success', ['ref' => $reference ?? $response->gatewayReference]);
        }

        $this->releaseReservationsForGatewayFailure(
            gateway: 'cmi',
            gatewayReference: $response->gatewayReference ?? $request->input('oid') ?? $request->query('ref'),
            reason: 'payment_failed',
        );

        return redirect()->route('checkout.failed', ['ref' => $response->gatewayReference, 'error' => $response->message]);
    }

    public function cmiWebhook(Request $request)
    {
        $response = $this->callbackHandler->webhook('cmi', $request);

        if ($response->isSuccessful) {
            $this->markOrderPaidForGatewaySuccess(
                gateway: 'cmi',
                gatewayReference: $response->gatewayReference ?? $request->input('oid') ?? $request->query('ref'),
            );
        }

        if (! $response->isSuccessful) {
            $this->releaseReservationsForGatewayFailure(
                gateway: 'cmi',
                gatewayReference: $request->input('oid') ?? $request->query('ref'),
                reason: 'payment_failed',
            );
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
        $response = $this->callbackHandler->verify('payzone', $request);

        if ($response->isSuccessful) {
            $reference = $this->markOrderPaidForGatewaySuccess(
                gateway: 'payzone',
                gatewayReference: $request->query('ref') ?? $response->gatewayReference,
            );

            return redirect()->route('checkout.success', ['ref' => $reference ?? $response->gatewayReference]);
        }

        $this->releaseReservationsForGatewayFailure(
            gateway: 'payzone',
            gatewayReference: $request->query('ref') ?? $response->gatewayReference,
            reason: $request->query('status') === 'cancelled' ? 'order_cancelled' : 'payment_failed',
        );

        return redirect()->route('checkout.failed', ['ref' => $request->query('ref'), 'error' => $response->message]);
    }

    public function payzoneWebhook(Request $request)
    {
        $response = $this->callbackHandler->webhook('payzone', $request);

        if ($response->isSuccessful) {
            $this->markOrderPaidForGatewaySuccess(
                gateway: 'payzone',
                gatewayReference: $response->gatewayReference ?? $request->input('payment_id') ?? $request->input('order_id') ?? $request->query('ref'),
            );
        }

        if (! $response->isSuccessful) {
            $this->releaseReservationsForGatewayFailure(
                gateway: 'payzone',
                gatewayReference: $request->input('reference') ?? $request->query('ref') ?? $response->gatewayReference,
                reason: 'payment_failed',
            );
        }

        return response()->json(['status' => $response->isSuccessful ? 'ok' : 'error'], 200);
    }

    // ──────────────────────────────────────────────────────────────
    // STRIPE
    // ──────────────────────────────────────────────────────────────

    public function stripeCallback(Request $request)
    {
        $response = $this->callbackHandler->verify('stripe', $request);

        if ($response->isSuccessful) {
            $reference = $this->markOrderPaidForGatewaySuccess(
                gateway: 'stripe',
                gatewayReference: $request->query('ref') ?? $response->gatewayReference,
            );

            return redirect()->route('checkout.success', ['ref' => $reference ?? $response->gatewayReference]);
        }

        $this->releaseReservationsForGatewayFailure(
            gateway: 'stripe',
            gatewayReference: $request->query('ref') ?? $response->gatewayReference,
            reason: $request->query('status') === 'cancelled' ? 'order_cancelled' : 'payment_failed',
        );

        return redirect()->route('checkout.failed', ['ref' => $request->query('ref'), 'error' => $response->message]);
    }

    public function stripeWebhook(Request $request)
    {
        $response = $this->callbackHandler->webhook('stripe', $request);

        if ($response->isSuccessful) {
            $this->markOrderPaidForGatewaySuccess(
                gateway: 'stripe',
                gatewayReference: $response->gatewayReference
                    ?? data_get($response->rawPayload, 'payment_intent')
                    ?? data_get($response->rawPayload, 'id'),
                orderId: data_get($response->rawPayload, 'metadata.nino_order_id'),
            );
        }

        if (! $response->isSuccessful) {
            $gatewayReference = $response->gatewayReference
                ?? data_get($response->rawPayload, 'payment_intent')
                ?? data_get($response->rawPayload, 'id');

            $this->releaseReservationsForGatewayFailure(
                gateway: 'stripe',
                gatewayReference: $gatewayReference,
                reason: 'payment_failed',
                orderId: data_get($response->rawPayload, 'metadata.nino_order_id'),
            );
        }

        return response()->json(['received' => true], 200);
    }

    private function releaseReservationsForGatewayFailure(
        string $gateway,
        ?string $gatewayReference,
        string $reason,
        ?int $orderId = null,
    ): void {
        $transaction = PaymentTransaction::query()
            ->with('order.lineItems')
            ->where('gateway', $gateway)
            ->where(function ($query) use ($gatewayReference, $orderId) {
                if ($orderId) {
                    $query->orWhere('order_id', $orderId);
                }

                if (! $gatewayReference) {
                    return;
                }

                if (Schema::hasColumn('payment_transactions', 'gateway_reference')) {
                    $query->orWhere('gateway_reference', $gatewayReference);
                }

                if (Schema::hasColumn('payment_transactions', 'gateway_transaction_id')) {
                    $query->orWhere('gateway_transaction_id', $gatewayReference);
                }

                if (Schema::hasColumn('payment_transactions', 'reference')) {
                    $query->orWhere('reference', $gatewayReference);
                }

                if (Schema::hasColumn('payment_transactions', 'payload')) {
                    $query->orWhereJsonContains('payload->stripe_session_id', $gatewayReference);
                }

                if (Schema::hasColumn('payment_transactions', 'metadata')) {
                    $query->orWhereJsonContains('metadata->stripe_session_id', $gatewayReference);
                }
            })
            ->latest('id')
            ->first();

        if (! $transaction?->order) {
            return;
        }

        $order = $transaction->order;
        $shouldTriggerFailure = $reason === 'payment_failed'
            && ! in_array($order->status, [OrderStatus::FAILED, OrderStatus::CANCELLED, OrderStatus::REFUNDED], true);

        if (in_array($order->status, [OrderStatus::PAID, OrderStatus::PREPARING, OrderStatus::SHIPPED, OrderStatus::DELIVERED], true)) {
            return;
        }

        $this->inventoryService->releaseReservationsForOrder($order, $reason);

        $order->update([
            'status' => $reason === 'order_cancelled' ? OrderStatus::CANCELLED : OrderStatus::FAILED,
        ]);

        if ($shouldTriggerFailure) {
            $this->notificationTriggerService->paymentFailed(
                $order->fresh(),
                $this->resolveTransactionPaymentMethod($transaction),
                $this->resolveTransactionReference($transaction),
            );
        }
    }

    private function markOrderPaidForGatewaySuccess(
        string $gateway,
        ?string $gatewayReference,
        ?int $orderId = null,
    ): ?string {
        $transaction = PaymentTransaction::query()
            ->with('order')
            ->where('gateway', $gateway)
            ->where(function ($query) use ($gatewayReference, $orderId) {
                if ($orderId) {
                    $query->orWhere('order_id', $orderId);
                }

                if (! $gatewayReference) {
                    return;
                }

                if (Schema::hasColumn('payment_transactions', 'gateway_reference')) {
                    $query->orWhere('gateway_reference', $gatewayReference);
                }

                if (Schema::hasColumn('payment_transactions', 'gateway_transaction_id')) {
                    $query->orWhere('gateway_transaction_id', $gatewayReference);
                }

                if (Schema::hasColumn('payment_transactions', 'reference')) {
                    $query->orWhere('reference', $gatewayReference);
                }

                if (Schema::hasColumn('payment_transactions', 'payload')) {
                    $query->orWhereJsonContains('payload->stripe_session_id', $gatewayReference)
                        ->orWhereJsonContains('payload->stripe_payment_intent', $gatewayReference)
                        ->orWhereJsonContains('payload->payzone_ref', $gatewayReference)
                        ->orWhereJsonContains('payload->payzone_payment_id', $gatewayReference)
                        ->orWhereJsonContains('payload->cmi_oid', $gatewayReference);
                }

                if (Schema::hasColumn('payment_transactions', 'metadata')) {
                    $query->orWhereJsonContains('metadata->stripe_session_id', $gatewayReference);
                }
            })
            ->latest('id')
            ->first();

        if (! $transaction?->order) {
            return null;
        }

        $order = $transaction->order;
        $shouldTriggerSuccess = in_array($order->status, [OrderStatus::PENDING, OrderStatus::AWAITING_PAYMENT], true);

        if ($shouldTriggerSuccess) {
            $order->update([
                'status' => OrderStatus::PAID,
            ]);

            $this->notificationTriggerService->paymentSuccess(
                $order->fresh(),
                $this->resolveTransactionPaymentMethod($transaction),
                $this->resolveTransactionReference($transaction),
            );
        }

        return $order->reference_number;
    }

    private function resolveTransactionPaymentMethod(PaymentTransaction $transaction): ?string
    {
        $rawPaymentMethod = $transaction->getRawOriginal('payment_method');

        if (is_string($rawPaymentMethod) && $rawPaymentMethod !== '') {
            return $rawPaymentMethod;
        }

        return $transaction->getRawOriginal('gateway') ?: null;
    }

    private function resolveTransactionReference(PaymentTransaction $transaction): ?string
    {
        return $transaction->getRawOriginal('gateway_transaction_id')
            ?: $transaction->getRawOriginal('gateway_reference')
            ?: $transaction->getRawOriginal('reference')
            ?: null;
    }
}
