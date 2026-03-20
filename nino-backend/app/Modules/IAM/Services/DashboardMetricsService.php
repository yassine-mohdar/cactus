<?php

namespace App\Modules\IAM\Services;

use App\Models\User;
use App\Modules\Finance\Enums\TransactionStatus;
use App\Modules\Finance\Models\PaymentTransaction;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Support\Models\SupportIssue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardMetricsService
{
    public function build(User $user): array
    {
        $to = now()->endOfDay();
        $from = now()->copy()->subDays(29)->startOfDay();
        $previousTo = $from->copy()->subSecond();
        $previousFrom = $from->copy()->subDays(30);

        $roleSignature = implode('|', $user->getRoleNames()->sort()->values()->all());
        $cacheKey = sprintf(
            'dashboard:v3:%s:%s:%s:%s',
            $user->id,
            md5($roleSignature),
            $from->toDateString(),
            $to->toDateString()
        );

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user, $from, $to, $previousFrom, $previousTo): array {
            return $this->buildPayload($user, $from, $to, $previousFrom, $previousTo);
        });
    }

    protected function buildPayload(User $user, Carbon $from, Carbon $to, Carbon $previousFrom, Carbon $previousTo): array
    {
        $isPlatform = $user->hasRole(['Super Admin', 'Platform Admin']);
        $canShip = $user->hasRole(['Super Admin', 'Shipping Agent']);
        $canSupport = $user->hasRole(['Super Admin', 'Customer Support Agent']);
        $canContent = $user->hasRole(['Super Admin', 'SEO / Content Manager']);

        $orderWindow = [$from->toDateTimeString(), $to->toDateTimeString()];
        $previousWindow = [$previousFrom->toDateTimeString(), $previousTo->toDateTimeString()];

        $seriesRows = Order::query()
            ->toBase()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as orders, COALESCE(SUM(grand_total), 0) as revenue')
            ->whereBetween('created_at', $orderWindow)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $revenueSeries = [];
        $orderSeries = [];

        foreach (range(0, 29) as $offset) {
            $day = $from->copy()->addDays($offset);
            $row = $seriesRows->get($day->toDateString());

            $labels[] = $day->format('M j');
            $revenueSeries[] = round((float) ($row->revenue ?? 0), 2);
            $orderSeries[] = (int) ($row->orders ?? 0);
        }

        $currentRevenue = (float) PaymentTransaction::completed()
            ->payments()
            ->whereBetween('created_at', $orderWindow)
            ->sum('amount');

        $previousRevenue = (float) PaymentTransaction::completed()
            ->payments()
            ->whereBetween('created_at', $previousWindow)
            ->sum('amount');

        $currentOrders = (int) Order::query()->whereBetween('created_at', $orderWindow)->count();
        $previousOrders = (int) Order::query()->whereBetween('created_at', $previousWindow)->count();

        $currentCustomers = (int) User::customers()->whereBetween('created_at', $orderWindow)->count();
        $previousCustomers = (int) User::customers()->whereBetween('created_at', $previousWindow)->count();

        $stockAlerts = (int) StockItem::query()->whereIn('status', ['low_stock', 'out_of_stock'])->count();

        $primaryMetrics = $isPlatform ? [
            $this->metricCard('Revenue', $this->formatMoney($currentRevenue), 'payments', $this->changeLabel($currentRevenue, $previousRevenue), 'Last 30 days', 'positive'),
            $this->metricCard('Orders', number_format($currentOrders), 'shopping_bag', $this->changeLabel($currentOrders, $previousOrders), 'Placed in the last 30 days', 'positive'),
            $this->metricCard('New Customers', number_format($currentCustomers), 'group', $this->changeLabel($currentCustomers, $previousCustomers), 'New signups in the last 30 days', 'positive'),
            $this->metricCard('Stock Alerts', number_format($stockAlerts), 'inventory_2', 'Live snapshot', 'Low or out-of-stock items', $stockAlerts > 0 ? 'negative' : 'neutral', $stockAlerts > 0 ? 'error' : 'warning'),
        ] : [];

        $topProducts = DB::table('order_line_items')
            ->join('orders', 'orders.id', '=', 'order_line_items.order_id')
            ->selectRaw('order_line_items.product_name, SUM(order_line_items.quantity) as total_qty, COALESCE(SUM(order_line_items.line_total), 0) as total_revenue')
            ->whereBetween('orders.created_at', $orderWindow)
            ->groupBy('order_line_items.product_name')
            ->orderByDesc('total_revenue')
            ->limit(6)
            ->get()
            ->map(function ($row) {
                return [
                    'label' => $row->product_name,
                    'units' => (int) $row->total_qty,
                    'revenue' => (float) $row->total_revenue,
                ];
            });

        $recentOrders = Order::query()
            ->with(['customer:id,name,first_name,last_name', 'billingAddress:id,order_id,first_name,last_name'])
            ->latest()
            ->limit(6)
            ->get()
            ->map(function (Order $order) {
                $billingName = trim(($order->billingAddress?->first_name ?? '').' '.($order->billingAddress?->last_name ?? ''));

                return [
                    'reference' => $order->reference_number,
                    'customer' => $billingName !== '' ? $billingName : ($order->customer?->full_name ?: ($order->customer?->name ?? 'Guest')),
                    'status' => $order->status->label(),
                    'status_tone' => $this->mapOrderTone($order->status),
                    'total' => $this->formatMoney((float) $order->grand_total, $order->currency),
                    'date' => $order->created_at->diffForHumans(),
                    'url' => route('admin.orders.show', $order),
                ];
            });

        $lowStock = StockItem::query()
            ->with(['product:id,name,sku', 'variant:id,product_id,sku', 'branch:id,name'])
            ->whereIn('status', ['low_stock', 'out_of_stock'])
            ->orderByRaw('CASE WHEN quantity - reserved_quantity < 0 THEN 0 ELSE quantity - reserved_quantity END asc')
            ->limit(6)
            ->get()
            ->map(function (StockItem $item) {
                $available = max(0, $item->quantity - $item->reserved_quantity);

                return [
                    'name' => $item->product?->name ?? 'Deleted product',
                    'sku' => $item->sku ?? 'NO-SKU',
                    'branch' => $item->branch?->name ?? 'Default',
                    'available' => $available,
                    'threshold' => (int) ($item->low_stock_threshold ?? 0),
                    'tone' => $available === 0 ? 'danger' : 'warning',
                ];
            });

        $statusBoard = collect([
            OrderStatus::PENDING,
            OrderStatus::PREPARING,
            OrderStatus::SHIPPED,
            OrderStatus::DELIVERED,
            OrderStatus::FAILED,
            OrderStatus::CANCELLED,
        ])->map(function (OrderStatus $status) use ($orderWindow) {
            $count = Order::query()
                ->whereBetween('created_at', $orderWindow)
                ->where('status', $status)
                ->count();

            return [
                'label' => $status->label(),
                'count' => $count,
                'tone' => match ($status) {
                    OrderStatus::DELIVERED => 'success',
                    OrderStatus::FAILED, OrderStatus::CANCELLED => 'danger',
                    OrderStatus::SHIPPED => 'info',
                    default => 'neutral',
                },
            ];
        });

        $queueCards = collect();

        if ($canShip) {
            $queueCards->push([
                'title' => 'Pending Shipments',
                'value' => number_format(Order::query()->where('status', OrderStatus::PREPARING)->count()),
                'subtitle' => 'Orders waiting for fulfillment handoff',
                'tone' => 'warning',
                'icon' => 'local_shipping',
                'href' => route('admin.orders.index', ['status' => OrderStatus::PREPARING->value]),
                'cta' => 'Open queue',
            ]);

            $queueCards->push([
                'title' => 'Delivery Exceptions',
                'value' => number_format(DB::table('shipments')->whereIn('status', ['failed_delivery', 'returned'])->count()),
                'subtitle' => 'Failed or returned shipments needing action',
                'tone' => 'danger',
                'icon' => 'warning',
                'href' => route('admin.shipping.reports', ['tab' => 'failed']),
                'cta' => 'Review issues',
            ]);
        }

        if ($canSupport) {
            $queueCards->push([
                'title' => 'Open Tickets',
                'value' => number_format(SupportIssue::query()->active()->count()),
                'subtitle' => 'Issues in progress or waiting on staff',
                'tone' => 'info',
                'icon' => 'support_agent',
                'href' => route('admin.support.lookup'),
                'cta' => 'Open support desk',
            ]);
        }

        if ($canContent) {
            try {
                $draftCount = (int) \App\Modules\Cms\Models\BlogPost::query()->where('is_published', false)->count();

                $queueCards->push([
                    'title' => 'Draft Content',
                    'value' => number_format($draftCount),
                    'subtitle' => 'Posts waiting on final review',
                    'tone' => 'neutral',
                    'icon' => 'edit_note',
                    'href' => route('admin.cms.posts.index'),
                    'cta' => 'Review drafts',
                ]);
            } catch (\Throwable) {
                // CMS module is optional in some local setups.
            }
        }

        if ($isPlatform) {
            $queueCards->push([
                'title' => 'Payment Exceptions',
                'value' => number_format(PaymentTransaction::query()->whereIn('status', [TransactionStatus::PENDING, TransactionStatus::FAILED])->count()),
                'subtitle' => 'Pending or failed transactions requiring review',
                'tone' => 'danger',
                'icon' => 'credit_card_off',
                'href' => route('admin.finance.transactions.index', ['status' => TransactionStatus::FAILED->value]),
                'cta' => 'Review finance',
            ]);
        }

        $recentTickets = $canSupport
            ? SupportIssue::query()->active()->latest()->limit(5)->get()->map(function (SupportIssue $issue) {
                return [
                    'reference' => $issue->reference,
                    'subject' => $issue->subject,
                    'status' => $issue->status->label(),
                    'status_tone' => $issue->status->isActive() ? 'warning' : 'neutral',
                    'url' => route('admin.support.issues.show', $issue),
                ];
            })
            : collect();

        return [
            'range_label' => 'Last 30 days',
            'primary_metrics' => $primaryMetrics,
            'revenue_chart' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Revenue',
                        'data' => $revenueSeries,
                        'borderColor' => '#245848',
                        'backgroundColor' => 'rgba(36, 88, 72, 0.12)',
                        'fill' => true,
                        'tension' => 0.35,
                        'borderWidth' => 2,
                        'pointRadius' => 0,
                        'pointHoverRadius' => 4,
                    ],
                    [
                        'label' => 'Orders',
                        'data' => $orderSeries,
                        'borderColor' => '#9F7B3A',
                        'backgroundColor' => 'rgba(184, 128, 47, 0.14)',
                        'fill' => false,
                        'tension' => 0.3,
                        'borderWidth' => 2,
                        'pointRadius' => 0,
                        'pointHoverRadius' => 4,
                        'yAxisID' => 'y1',
                    ],
                ],
            ],
            'top_products' => $topProducts->values()->all(),
            'recent_orders' => $recentOrders->values()->all(),
            'low_stock' => $lowStock->values()->all(),
            'status_board' => $statusBoard->values()->all(),
            'queue_cards' => $queueCards->values()->all(),
            'recent_tickets' => $recentTickets->values()->all(),
        ];
    }

    protected function metricCard(
        string $title,
        string $value,
        string $icon,
        string $change,
        string $subtitle,
        string $changeType = 'neutral',
        string $tone = 'sage'
    ): array {
        return compact('title', 'value', 'icon', 'change', 'subtitle', 'changeType', 'tone');
    }

    protected function changeLabel(float|int $current, float|int $previous): string
    {
        if ((float) $previous === 0.0) {
            return $current > 0 ? 'New activity' : 'No change';
        }

        $delta = (($current - $previous) / abs($previous)) * 100;

        if (abs($delta) < 0.1) {
            return 'Flat vs previous 30 days';
        }

        return sprintf('%s%.1f%% vs previous 30 days', $delta > 0 ? '+' : '', $delta);
    }

    protected function formatMoney(float $amount, string $currency = 'MAD'): string
    {
        return number_format($amount, 2).' '.$currency;
    }

    protected function mapOrderTone(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::DELIVERED => 'success',
            OrderStatus::SHIPPED => 'info',
            OrderStatus::FAILED, OrderStatus::CANCELLED, OrderStatus::REFUNDED => 'danger',
            OrderStatus::PREPARING, OrderStatus::PENDING, OrderStatus::AWAITING_PAYMENT => 'warning',
            default => 'neutral',
        };
    }
}
