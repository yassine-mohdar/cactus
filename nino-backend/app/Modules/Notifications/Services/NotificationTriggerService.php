<?php

namespace App\Modules\Notifications\Services;

use App\Modules\Notifications\Enums\NotificationEvent;
use App\Modules\Orders\Models\Order;
use App\Modules\Shipping\Models\Shipment;

/**
 * Trigger Service — provides convenient methods for dispatching
 * notifications from business events. Each method collects the
 * relevant variables and calls the dispatcher.
 *
 * Call these from controllers, event listeners, or observers:
 *   app(NotificationTriggerService::class)->orderPlaced($order);
 */
class NotificationTriggerService
{
    public function __construct(
        private NotificationDispatcher $dispatcher
    ) {}

    // ── Order Lifecycle ────────────────────────────────────

    public function orderPlaced(Order $order): array
    {
        $order->loadMissing('customer');

        return $this->dispatcher->dispatch(
            NotificationEvent::ORDER_PLACED,
            $order->customer?->email ?? $order->email,
            [
                'customer_name' => $order->customer?->first_name ?? 'Customer',
                'customer_email' => $order->customer?->email ?? $order->email,
                'order_reference' => $order->reference_number,
                'order_total' => number_format($order->grand_total, 2),
                'order_currency' => $order->currency ?? 'MAD',
                'order_items_count' => $order->lineItems()->count(),
                'order_date' => $order->created_at->format('M d, Y H:i'),
            ],
            $order->customer_id,
        );
    }

    public function orderCancelled(Order $order): array
    {
        $order->loadMissing('customer');

        return $this->dispatcher->dispatch(
            NotificationEvent::ORDER_CANCELLED,
            $order->customer?->email ?? $order->email,
            [
                'customer_name' => $order->customer?->first_name ?? 'Customer',
                'customer_email' => $order->customer?->email ?? $order->email,
                'order_reference' => $order->reference_number,
                'order_total' => number_format($order->grand_total, 2),
                'order_currency' => $order->currency ?? 'MAD',
                'order_items_count' => $order->lineItems()->count(),
                'order_date' => $order->created_at->format('M d, Y H:i'),
            ],
            $order->customer_id,
        );
    }

    // ── Payment Lifecycle ──────────────────────────────────

    public function paymentSuccess(Order $order, ?string $paymentMethod = null, ?string $transactionId = null): array
    {
        $order->loadMissing('customer');

        return $this->dispatcher->dispatch(
            NotificationEvent::PAYMENT_SUCCESS,
            $order->customer?->email ?? $order->email,
            [
                'customer_name' => $order->customer?->first_name ?? 'Customer',
                'customer_email' => $order->customer?->email ?? $order->email,
                'order_reference' => $order->reference_number,
                'order_total' => number_format($order->grand_total, 2),
                'order_currency' => $order->currency ?? 'MAD',
                'payment_method' => $paymentMethod ?? 'Unknown',
                'transaction_id' => $transactionId ?? '—',
            ],
            $order->customer_id,
        );
    }

    public function paymentFailed(Order $order, ?string $paymentMethod = null, ?string $transactionId = null): array
    {
        $order->loadMissing('customer');

        return $this->dispatcher->dispatch(
            NotificationEvent::PAYMENT_FAILED,
            $order->customer?->email ?? $order->email,
            [
                'customer_name' => $order->customer?->first_name ?? 'Customer',
                'customer_email' => $order->customer?->email ?? $order->email,
                'order_reference' => $order->reference_number,
                'order_total' => number_format($order->grand_total, 2),
                'order_currency' => $order->currency ?? 'MAD',
                'payment_method' => $paymentMethod ?? 'Unknown',
                'transaction_id' => $transactionId ?? '—',
            ],
            $order->customer_id,
        );
    }

    // ── Shipping Lifecycle ─────────────────────────────────

    public function orderShipped(Order $order, Shipment $shipment): array
    {
        $order->loadMissing('customer');

        return $this->dispatcher->dispatch(
            NotificationEvent::ORDER_SHIPPED,
            $order->customer?->email ?? $order->email,
            [
                'customer_name' => $order->customer?->first_name ?? 'Customer',
                'customer_email' => $order->customer?->email ?? $order->email,
                'order_reference' => $order->reference_number,
                'tracking_number' => $shipment->tracking_number ?? '—',
                'tracking_url' => $shipment->getTrackingLink() ?? '#',
                'carrier_name' => $shipment->carrier_name ?? '—',
                'estimated_delivery' => $shipment->shippingMethod?->estimated_days ?? 'TBD',
            ],
            $order->customer_id,
        );
    }

    public function orderDelivered(Order $order, Shipment $shipment): array
    {
        $order->loadMissing('customer');

        return $this->dispatcher->dispatch(
            NotificationEvent::ORDER_DELIVERED,
            $order->customer?->email ?? $order->email,
            [
                'customer_name' => $order->customer?->first_name ?? 'Customer',
                'customer_email' => $order->customer?->email ?? $order->email,
                'order_reference' => $order->reference_number,
                'tracking_number' => $shipment->tracking_number ?? '—',
                'delivery_date' => $shipment->delivered_at?->format('M d, Y H:i') ?? now()->format('M d, Y H:i'),
            ],
            $order->customer_id,
        );
    }

    // ── Account Lifecycle ──────────────────────────────────

    public function welcome(string $email, string $name, ?int $customerId = null): array
    {
        return $this->dispatcher->dispatch(
            NotificationEvent::WELCOME,
            $email,
            [
                'customer_name' => $name,
                'customer_email' => $email,
                'login_url' => config('app.url') . '/login',
            ],
            $customerId,
        );
    }

    public function passwordSetup(string $email, string $name, string $setupUrl, int $expiryHours = 24, ?int $customerId = null): array
    {
        return $this->dispatcher->dispatch(
            NotificationEvent::PASSWORD_SETUP,
            $email,
            [
                'customer_name' => $name,
                'customer_email' => $email,
                'setup_url' => $setupUrl,
                'expiry_hours' => (string) $expiryHours,
            ],
            $customerId,
        );
    }

    // ── Marketing / Recovery ───────────────────────────────

    public function abandonedCart(string $email, string $name, array $cartData, ?int $customerId = null): array
    {
        return $this->dispatcher->dispatch(
            NotificationEvent::ABANDONED_CART,
            $email,
            [
                'customer_name' => $name,
                'customer_email' => $email,
                'cart_items_count' => (string) ($cartData['items_count'] ?? 0),
                'cart_total' => $cartData['total'] ?? '0.00',
                'cart_url' => $cartData['url'] ?? config('app.url') . '/cart',
            ],
            $customerId,
        );
    }

    public function promotionalOffer(string $email, string $name, array $promoData, ?int $customerId = null): array
    {
        return $this->dispatcher->dispatch(
            NotificationEvent::PROMOTIONAL_OFFER,
            $email,
            [
                'customer_name' => $name,
                'customer_email' => $email,
                'promo_title' => $promoData['title'] ?? '',
                'promo_code' => $promoData['code'] ?? '',
                'promo_discount' => $promoData['discount'] ?? '',
                'promo_expiry' => $promoData['expiry'] ?? '',
                'promo_url' => $promoData['url'] ?? config('app.url'),
            ],
            $customerId,
        );
    }
}
