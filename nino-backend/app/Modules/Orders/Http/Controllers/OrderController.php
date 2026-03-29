<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\Finance\Models\PaymentTransaction;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Support\Models\ActivityTimeline;
use App\Modules\Support\Models\InternalNote;
use App\Modules\Shipping\Models\ShipmentStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::with('customer')->orderByDesc('created_at');
        $summaryBaseQuery = Order::query();

        if ($search = $request->input('search')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('reference_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('email', 'LIKE', "%{$search}%")
                            ->orWhere('name', 'LIKE', "%{$search}%")
                            ->orWhere('first_name', 'LIKE', "%{$search}%")
                            ->orWhere('last_name', 'LIKE', "%{$search}%");
                    });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', Carbon::parse($dateFrom)->toDateString());
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', Carbon::parse($dateTo)->toDateString());
        }

        if ($customerId = $request->input('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        if ($paymentMethod = $request->input('payment_method')) {
            $query->where('payment_method', $paymentMethod);
        }

        if ($shippingMethod = $request->input('shipping_method')) {
            $query->where('shipping_method', $shippingMethod);
        }

        $orders = $query->paginate(25)->withQueryString();

        $summary = [
            'total_orders' => (clone $summaryBaseQuery)->count(),
            'awaiting_payment' => (clone $summaryBaseQuery)->where('status', OrderStatus::AWAITING_PAYMENT)->count(),
            'preparing' => (clone $summaryBaseQuery)->where('status', OrderStatus::PREPARING)->count(),
            'gross_30_days' => (float) ((clone $summaryBaseQuery)
                ->where('created_at', '>=', now()->subDays(30))
                ->sum('grand_total') ?? 0),
        ];

        $statuses = OrderStatus::cases();
        $customers = User::query()
            ->whereIn('id', Order::query()->whereNotNull('customer_id')->select('customer_id'))
            ->orderByRaw("COALESCE(NULLIF(name, ''), CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')))")
            ->get(['id', 'name', 'first_name', 'last_name', 'email']);
        $paymentMethods = $this->distinctOrderFieldValues('payment_method');
        $shippingMethods = $this->distinctOrderFieldValues('shipping_method');

        return view('admin.orders.index', compact(
            'orders',
            'statuses',
            'summary',
            'customers',
            'paymentMethods',
            'shippingMethods',
        ));
    }

    public function show(Order $order)
    {
        $this->authorize('view', $order);

        $order->load([
            'customer',
            'lineItems',
            'addresses',
            'shipments.statusHistory.changedByUser',
            'transactions',
        ]);

        $shippingAddress = $order->addresses->where('type', 'shipping')->first();
        $billingAddress = $order->addresses->where('type', 'billing')->first();
        $notes = InternalNote::query()
            ->where('notable_type', Order::class)
            ->where('notable_id', $order->id)
            ->with('author')
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->get();
        $auditTrail = AuditLog::query()
            ->where('auditable_type', Order::class)
            ->where('auditable_id', $order->id)
            ->latest('id')
            ->limit(10)
            ->get();
        $timeline = $this->buildTimeline($order, $notes);

        return view('admin.orders.show', compact(
            'order',
            'shippingAddress',
            'billingAddress',
            'notes',
            'auditTrail',
            'timeline',
        ));
    }

    /**
     * @return list<array{value:string,label:string}>
     */
    private function distinctOrderFieldValues(string $field): array
    {
        return Order::query()
            ->whereNotNull($field)
            ->where($field, '!=', '')
            ->distinct()
            ->orderBy($field)
            ->pluck($field)
            ->map(fn (string $value): array => [
                'value' => $value,
                'label' => Str::of($value)->replace(['_', '-'], ' ')->title()->value(),
            ])
            ->values()
            ->all();
    }

    private function buildTimeline(Order $order, Collection $notes): Collection
    {
        $timeline = collect([
            [
                'title' => 'Order created',
                'copy' => 'Checkout completed and the operational order record was created.',
                'meta' => $order->created_at->format('M d, Y H:i'),
                'timestamp' => $order->created_at,
                'tone' => 'success',
            ],
        ]);

        $timeline = $timeline
            ->merge($order->transactions->map(function (PaymentTransaction $transaction): array {
                $status = $transaction->status?->label() ?? Str::of((string) $transaction->status)->replace('_', ' ')->title()->value();
                $method = $transaction->payment_method?->label() ?? Str::of((string) $transaction->payment_method)->replace('_', ' ')->title()->value();

                return [
                    'title' => 'Payment transaction '.$status,
                    'copy' => trim(sprintf(
                        '%s%s%s',
                        $method ?: 'Payment method pending',
                        $transaction->reference ? ' · Ref '.$transaction->reference : '',
                        $transaction->failure_reason ? ' · '.$transaction->failure_reason : '',
                    )),
                    'meta' => $transaction->created_at?->format('M d, Y H:i') ?? 'Unknown time',
                    'timestamp' => $transaction->created_at ?? now()->subCentury(),
                    'tone' => match ($transaction->status?->value) {
                        'completed' => 'success',
                        'failed', 'cancelled', 'refunded' => 'danger',
                        default => 'info',
                    },
                ];
            }))
            ->merge($order->shipments->flatMap(function ($shipment): Collection {
                return $shipment->statusHistory->map(function (ShipmentStatusHistory $entry): array {
                    $statusTo = Str::of($entry->status_to)->replace('_', ' ')->title()->value();
                    $statusFrom = $entry->status_from
                        ? Str::of($entry->status_from)->replace('_', ' ')->title()->value().' -> '
                        : '';

                    return [
                        'title' => 'Shipment transition',
                        'copy' => trim($statusFrom.$statusTo.($entry->notes ? ' · '.$entry->notes : '')),
                        'meta' => ($entry->changed_by_name ?: 'System').' · '.$entry->created_at->format('M d, Y H:i'),
                        'timestamp' => $entry->created_at,
                        'tone' => match ($entry->status_to) {
                            'delivered' => 'success',
                            'failed_delivery', 'cancelled', 'returned' => 'danger',
                            default => 'info',
                        },
                    ];
                });
            }))
            ->merge($notes->map(function (InternalNote $note): array {
                return [
                    'title' => $note->is_pinned ? 'Pinned internal note' : 'Internal note added',
                    'copy' => $note->content,
                    'meta' => ($note->author?->first_name ?: $note->author?->name ?: 'Unknown').' · '.$note->created_at->format('M d, Y H:i'),
                    'timestamp' => $note->created_at,
                    'tone' => 'neutral',
                ];
            }));

        return $timeline
            ->sortByDesc(fn (array $entry) => $entry['timestamp'])
            ->values();
    }
}
