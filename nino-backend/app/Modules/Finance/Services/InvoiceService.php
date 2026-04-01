<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Enums\InvoiceStatus;
use App\Modules\Finance\Enums\TransactionStatus;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Models\PaymentTransaction;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;

class InvoiceService
{
    public function ensureInvoiceForOrder(Order $order, ?PaymentTransaction $transaction = null): ?Invoice
    {
        $order->loadMissing(['customer', 'transactions', 'shipments', 'invoice']);

        $invoiceable = $this->isInvoiceable($order);
        $invoice = $order->invoice;

        if (! $invoiceable && ! $invoice) {
            return null;
        }

        $transaction ??= $this->resolvePrimaryTransaction($order);

        $attributes = [
            'customer_id' => $order->customer_id,
            'transaction_id' => $transaction?->id,
            'status' => $this->resolveStatus($order),
            'issued_at' => $invoice?->issued_at ?? $order->created_at ?? now(),
            'paid_at' => $this->resolvePaidAt($order, $transaction, $invoice),
            'currency' => $order->currency,
            'subtotal' => $order->subtotal,
            'tax_total' => $order->tax_total,
            'shipping_total' => $order->shipping_total,
            'discount_total' => $order->discount_total,
            'grand_total' => $order->grand_total,
            'metadata' => [
                'order_reference' => $order->reference_number,
                'payment_method' => $order->resolvedPaymentMethodCode(),
                'payment_method_label' => $order->resolvedPaymentMethodLabel(),
                'shipping_method' => $order->shipping_method,
                'order_status' => $order->status?->value ?? (string) $order->status,
            ],
        ];

        if ($invoice) {
            $invoice->fill($attributes);
            $invoice->save();

            return $invoice->fresh(['order', 'customer', 'transaction']);
        }

        return Invoice::create(array_merge($attributes, [
            'order_id' => $order->id,
        ]))->fresh(['order', 'customer', 'transaction']);
    }

    private function isInvoiceable(Order $order): bool
    {
        $status = $order->status instanceof OrderStatus
            ? $order->status
            : OrderStatus::from((string) $order->status);

        if ($order->isCodPaymentMethod()) {
            return in_array($status, [OrderStatus::DELIVERED, OrderStatus::REFUNDED], true);
        }

        return in_array($status, [
            OrderStatus::PAID,
            OrderStatus::PREPARING,
            OrderStatus::SHIPPED,
            OrderStatus::DELIVERED,
            OrderStatus::REFUNDED,
        ], true);
    }

    private function resolvePrimaryTransaction(Order $order): ?PaymentTransaction
    {
        return $order->transactions
            ->sortByDesc(fn (PaymentTransaction $transaction) => $transaction->updated_at ?? $transaction->created_at)
            ->first(function (PaymentTransaction $transaction): bool {
                return $transaction->status === TransactionStatus::COMPLETED
                    || $transaction->isCod();
            });
    }

    private function resolveStatus(Order $order): InvoiceStatus
    {
        $status = $order->status instanceof OrderStatus
            ? $order->status
            : OrderStatus::from((string) $order->status);

        return $status === OrderStatus::CANCELLED
            ? InvoiceStatus::VOID
            : InvoiceStatus::PAID;
    }

    private function resolvePaidAt(Order $order, ?PaymentTransaction $transaction, ?Invoice $invoice): ?\Illuminate\Support\Carbon
    {
        if ($invoice?->paid_at) {
            return $invoice->paid_at;
        }

        if ($transaction && $transaction->status === TransactionStatus::COMPLETED) {
            return $transaction->updated_at ?? $transaction->created_at;
        }

        $deliveredShipment = $order->shipments
            ->first(fn ($shipment) => $shipment->status?->value === 'delivered');

        if ($deliveredShipment?->delivered_at) {
            return $deliveredShipment->delivered_at;
        }

        return $order->updated_at ?? $order->created_at;
    }
}
