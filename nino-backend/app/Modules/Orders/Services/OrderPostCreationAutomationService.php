<?php

namespace App\Modules\Orders\Services;

use App\Modules\Finance\Enums\CodStatus;
use App\Modules\Finance\Enums\TransactionStatus;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\PaymentTransaction;
use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\Payments\Services\PaymentMethodAvailabilityService;
use App\Modules\Shipping\Enums\ShipmentStatus;
use App\Modules\Shipping\Models\Shipment;
use App\Modules\Shipping\Models\ShippingCarrier;
use App\Modules\Shipping\Services\SenditService;
use App\Modules\Shipping\Services\ShipmentService;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderPostCreationAutomationService
{
    public function __construct(
        private readonly PaymentMethodAvailabilityService $paymentMethodAvailability,
        private readonly ShipmentService $shipmentService,
        private readonly SenditService $senditService,
    ) {}

    public function bootstrap(Order $order): void
    {
        $order->loadMissing([
            'paymentMethodRecord',
            'shippingMethodRecord.shippingCarrier',
            'shipments.shippingMethod.shippingCarrier',
            'lineItems',
            'addresses',
        ]);

        $this->ensureCodTransaction($order);
        $this->bootstrapCodSenditShipment($order);
    }

    public function ensureCodTransaction(Order $order): ?PaymentTransaction
    {
        if (! $order->isCodPaymentMethod()) {
            return null;
        }

        $existingTransaction = $order->transactions()
            ->where('type', TransactionType::PAYMENT)
            ->where(function ($query): void {
                $query
                    ->where('payment_method_behavior', PaymentMethod::BEHAVIOR_COD)
                    ->orWhereIn('payment_method', ['cod', 'cash_on_delivery']);
            })
            ->latest('id')
            ->first();

        if ($existingTransaction) {
            return $existingTransaction;
        }

        $snapshot = $this->paymentMethodAvailability->snapshot($order->payment_method);

        return PaymentTransaction::create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'type' => TransactionType::PAYMENT,
            'status' => TransactionStatus::PENDING,
            'payment_method' => $snapshot['code'] ?? $order->payment_method,
            'payment_method_id' => $snapshot['id'],
            'payment_method_label' => $snapshot['label'] ?? $order->resolvedPaymentMethodLabel(),
            'payment_method_behavior' => $snapshot['behavior'] ?? PaymentMethod::BEHAVIOR_COD,
            'gateway' => 'cod',
            'amount' => $order->grand_total,
            'fee_amount' => 0,
            'net_amount' => $order->grand_total,
            'currency' => $order->currency,
            'cod_status' => CodStatus::PENDING,
            'order_subtotal' => $order->subtotal,
            'order_discount' => $order->discount_total,
            'order_shipping' => $order->shipping_total,
            'order_tax' => $order->tax_total,
            'order_total' => $order->grand_total,
            'metadata' => [
                'source' => 'order_creation',
                'automation' => 'cod_bootstrap',
                'shipping_method_id' => $order->shipping_method_id,
            ],
        ]);
    }

    public function bootstrapCodSenditShipment(Order $order): ?Shipment
    {
        if (! $order->isCodPaymentMethod()) {
            return null;
        }

        $shippingMethod = $order->shippingMethodRecord;
        $carrier = $shippingMethod?->shippingCarrier;

        if (! $shippingMethod || ! $carrier || ! $carrier->isSendit()) {
            return null;
        }

        $shipment = $order->shipments()
            ->with(['shippingMethod.shippingCarrier', 'order.customer', 'order.addresses', 'order.lineItems'])
            ->latest('id')
            ->first();

        if (! $shipment) {
            $shipment = $this->shipmentService->createShipment($order, $shippingMethod->id, [
                'package_count' => 1,
                'internal_notes' => 'Auto-created for COD + Sendit fulfillment.',
            ]);

            $shipment->load(['shippingMethod.shippingCarrier', 'order.customer', 'order.addresses', 'order.lineItems']);
        }

        try {
            if (! filled($shipment->external_reference)) {
                $this->senditService->createDelivery($shipment);
                $shipment = $shipment->fresh(['shippingMethod.shippingCarrier', 'order.customer', 'order.addresses', 'order.lineItems']);
            }

            if ($shipment->status === ShipmentStatus::PENDING) {
                $shipment = $this->shipmentService->transitionStatus(
                    $shipment,
                    ShipmentStatus::READY_TO_SHIP,
                    'Auto-prepared after successful COD + Sendit parcel creation.',
                );
            }

            return $shipment;
        } catch (Throwable $exception) {
            $shipment->forceFill([
                'provider_error' => $exception->getMessage(),
                'last_provider_sync_at' => now(),
            ])->save();

            Log::warning('[Orders] COD + Sendit automation failed', [
                'order_id' => $order->id,
                'shipment_id' => $shipment->id,
                'carrier_id' => $carrier->id,
                'carrier_code' => $carrier->code,
                'error' => $exception->getMessage(),
            ]);

            return $shipment->fresh(['shippingMethod.shippingCarrier', 'order.customer', 'order.addresses', 'order.lineItems']);
        }
    }
}
