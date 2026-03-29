<?php

namespace App\Modules\Customers\Services;

use App\Models\User;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CustomerAccountPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function overview(User $customer): array
    {
        $customer->loadCount(['addresses', 'orders']);

        $recentOrders = $customer->orders()
            ->with(['lineItems', 'shipment'])
            ->latest('created_at')
            ->limit(5)
            ->get();

        $activeOrders = $customer->orders()
            ->with(['lineItems', 'shipment.statusHistory'])
            ->whereIn('status', $this->activeOrderStatuses())
            ->latest('created_at')
            ->limit(5)
            ->get();

        return [
            'profile' => [
                'id' => $customer->id,
                'name' => $customer->full_name,
                'email' => $customer->email,
                'username' => $customer->username,
                'avatar' => $customer->avatar,
                'marketing_opt_in' => (bool) $customer->marketing_opt_in,
            ],
            'summary' => [
                'addresses_count' => (int) $customer->addresses_count,
                'orders_count' => (int) $customer->orders_count,
                'active_orders_count' => $activeOrders->count(),
            ],
            'addresses' => $customer->addresses()
                ->orderByDesc('is_default')
                ->orderBy('type')
                ->latest('id')
                ->get(),
            'orders' => [
                'total' => (int) $customer->orders_count,
                'recent' => $this->mapOrderSummaries($recentOrders),
                'active' => $this->mapOrderSummaries($activeOrders),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function history(User $customer): array
    {
        $orders = $customer->orders()
            ->with(['lineItems', 'shipment'])
            ->latest('created_at')
            ->paginate(12);

        return [
            'orders' => $orders,
            'summary' => [
                'total_orders' => (int) $customer->orders()->count(),
                'active_orders' => (int) $customer->orders()->whereIn('status', $this->activeOrderStatuses())->count(),
                'delivered_orders' => (int) $customer->orders()->where('status', OrderStatus::DELIVERED->value)->count(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(User $customer, Order $order): array
    {
        abort_unless($order->customer_id === $customer->id, 403, 'Unauthorized action.');

        $order->load([
            'lineItems',
            'billingAddress',
            'addresses',
            'shipment.shippingMethod',
            'shipment.statusHistory',
        ]);

        return [
            'order' => $order,
            'summary' => $this->mapOrderSummary($order),
            'shipping_address' => $order->addresses->firstWhere('type', 'shipping'),
            'billing_address' => $order->billingAddress,
            'shipment' => $order->shipment,
            'tracking' => $this->mapTracking($order),
        ];
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return array<int, array<string, mixed>>
     */
    protected function mapOrderSummaries(Collection $orders): array
    {
        return $orders->map(fn (Order $order) => $this->mapOrderSummary($order))->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapOrderSummary(Order $order): array
    {
        $shipment = $order->shipment;

        return [
            'id' => $order->id,
            'reference_number' => $order->reference_number,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'currency' => $order->currency,
            'grand_total' => number_format((float) $order->grand_total, 2, '.', ''),
            'created_at' => $order->created_at?->toDateTimeString(),
            'created_at_human' => $order->created_at?->format('M j, Y H:i'),
            'items_count' => (int) $order->lineItems->sum('quantity'),
            'tracking_available' => $shipment !== null,
            'tracking_number' => $shipment?->tracking_number,
            'tracking_url' => $shipment?->getTrackingLink(),
            'shipment_status' => $shipment?->status?->value,
            'shipment_status_label' => $shipment?->status?->label(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function mapTracking(Order $order): ?array
    {
        $shipment = $order->shipment;

        if ($shipment === null) {
            return null;
        }

        return [
            'tracking_number' => $shipment->tracking_number,
            'tracking_url' => $shipment->getTrackingLink(),
            'carrier' => $shipment->carrier_name,
            'status' => $shipment->status->value,
            'status_label' => $shipment->status->label(),
            'timeline' => $shipment->statusHistory->map(fn ($history) => [
                'from' => $history->status_from,
                'to' => $history->status_to,
                'notes' => $history->notes,
                'at' => $history->created_at?->format('M j, Y H:i'),
            ])->all(),
        ];
    }

    /**
     * @return list<string>
     */
    protected function activeOrderStatuses(): array
    {
        return array_map(
            static fn (OrderStatus $status): string => $status->value,
            [
                OrderStatus::PENDING,
                OrderStatus::AWAITING_PAYMENT,
                OrderStatus::PAID,
                OrderStatus::PREPARING,
                OrderStatus::SHIPPED,
            ],
        );
    }
}
