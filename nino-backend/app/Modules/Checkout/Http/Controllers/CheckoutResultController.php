<?php

namespace App\Modules\Checkout\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Gateways\OfflinePaymentGateway;
use App\Modules\Payments\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CheckoutResultController extends Controller
{
    public function success(Request $request, ?string $reference = null)
    {
        $reference ??= (string) $request->query('ref', '');

        abort_unless($reference !== '', 404);

        $resolved = $this->resolveOrderReference($reference);
        $order = $resolved['order'];

        abort_unless($order, 404);

        return view('customer.checkout.success', [
            'order' => $order,
            'summary' => [
                'reference_number' => $order->reference_number,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'currency' => $order->currency,
                'subtotal' => number_format((float) $order->subtotal, 2, '.', ''),
                'tax_total' => number_format((float) $order->tax_total, 2, '.', ''),
                'shipping_total' => number_format((float) $order->shipping_total, 2, '.', ''),
                'discount_total' => number_format((float) $order->discount_total, 2, '.', ''),
                'grand_total' => number_format((float) $order->grand_total, 2, '.', ''),
                'items_count' => (int) $order->lineItems->sum('quantity'),
                'shipping_method' => $order->shipping_method,
                'payment_method' => $order->resolvedPaymentMethodLabel(),
            ],
            'account' => $this->buildAccountSummary($request->user(), $order),
            'lookup' => [
                'tracking_api_url' => route('api.orders.track', $order->reference_number),
                'public_status_url' => route('checkout.success', ['ref' => $order->reference_number]),
            ],
            'offlinePayment' => $this->buildOfflinePaymentSummary($order),
        ]);
    }

    public function failed(Request $request)
    {
        $reference = trim((string) $request->query('ref', ''));
        $error = trim((string) $request->query('error', ''));
        $paymentReference = trim((string) $request->query('payment_ref', ''));
        $resolved = $reference !== ''
            ? $this->resolveOrderReference($reference)
            : ['order' => null, 'order_reference' => null, 'payment_reference' => null];
        $order = $resolved['order'];
        $orderReference = $resolved['order_reference'];
        $paymentReference = $paymentReference !== ''
            ? $paymentReference
            : $resolved['payment_reference'];

        $authenticatedCustomer = $request->user() instanceof User && $request->user()->isCustomer();

        return view('customer.checkout.failed', [
            'reference' => $reference !== '' ? $reference : null,
            'order_reference' => $orderReference,
            'payment_reference' => $paymentReference !== '' ? $paymentReference : null,
            'error_message' => $error !== '' ? $error : 'We could not complete the payment for this order.',
            'order' => $order,
            'recovery' => [
                'primary_label' => $authenticatedCustomer ? 'Open my account' : 'Customer login',
                'primary_url' => $authenticatedCustomer ? route('customer.account.home') : route('customer.login'),
                'secondary_label' => ($orderReference ?? $reference) !== '' ? 'Check order status' : 'Return to customer login',
                'secondary_url' => ($orderReference ?? $reference) !== ''
                    ? route('checkout.success', ['ref' => $orderReference ?? $reference])
                    : route('customer.login'),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildAccountSummary(?User $user, Order $order): array
    {
        $customer = $order->customer;
        $isAuthenticatedCustomer = $user instanceof User
            && $user->isCustomer()
            && (int) $user->id === (int) $order->customer_id;

        return [
            'email' => $customer?->email,
            'created_via_guest_checkout' => ! $isAuthenticatedCustomer,
            'account_home_url' => $isAuthenticatedCustomer ? route('customer.account.home') : null,
            'orders_url' => $isAuthenticatedCustomer ? route('customer.account.orders.index') : null,
            'order_detail_url' => $isAuthenticatedCustomer ? route('customer.account.orders.show', $order) : null,
        ];
    }

    /**
     * @return array{order: ?Order, order_reference: ?string, payment_reference: ?string}
     */
    protected function resolveOrderReference(string $reference): array
    {
        $order = Order::query()
            ->where('reference_number', $reference)
            ->with(['customer', 'lineItems', 'shippingAddress', 'billingAddress', 'shipment', 'paymentMethodRecord'])
            ->first();

        if ($order) {
            return [
                'order' => $order,
                'order_reference' => $order->reference_number,
                'payment_reference' => null,
            ];
        }

        $transaction = PaymentTransaction::query()
            ->with(['order.customer', 'order.lineItems', 'order.shippingAddress', 'order.billingAddress', 'order.shipment', 'order.paymentMethodRecord'])
            ->where(function ($query) use ($reference) {
                if (Schema::hasColumn('payment_transactions', 'reference')) {
                    $query->orWhere('reference', $reference);
                }

                if (Schema::hasColumn('payment_transactions', 'gateway_reference')) {
                    $query->orWhere('gateway_reference', $reference);
                }

                if (Schema::hasColumn('payment_transactions', 'gateway_transaction_id')) {
                    $query->orWhere('gateway_transaction_id', $reference);
                }

                if (Schema::hasColumn('payment_transactions', 'payload')) {
                    $query->orWhereJsonContains('payload->stripe_session_id', $reference)
                        ->orWhereJsonContains('payload->stripe_payment_intent', $reference)
                        ->orWhereJsonContains('payload->payzone_ref', $reference)
                        ->orWhereJsonContains('payload->payzone_payment_id', $reference)
                        ->orWhereJsonContains('payload->cmi_oid', $reference);
                }

                if (Schema::hasColumn('payment_transactions', 'metadata')) {
                    $query->orWhereJsonContains('metadata->stripe_session_id', $reference);
                }
            })
            ->latest('id')
            ->first();

        return [
            'order' => $transaction?->order,
            'order_reference' => $transaction?->order?->reference_number,
            'payment_reference' => $transaction ? $reference : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function buildOfflinePaymentSummary(Order $order): ?array
    {
        if (! $order->paymentMethodRecord?->isOfflineManual()
            && ! in_array($order->resolvedPaymentMethodCode(), ['bank_transfer'], true)) {
            return null;
        }

        $transaction = $order->paymentTransactions()
            ->where('gateway', 'offline_transfer')
            ->latest('id')
            ->first();

        if ($transaction && is_array($transaction->payload) && isset($transaction->payload['offline_method']) && is_array($transaction->payload['offline_method'])) {
            return $transaction->payload['offline_method'];
        }

        return app(OfflinePaymentGateway::class)->checkoutDetails(
            reference: $transaction?->gateway_reference,
            paymentMethod: $order->paymentMethodRecord,
        );
    }
}
