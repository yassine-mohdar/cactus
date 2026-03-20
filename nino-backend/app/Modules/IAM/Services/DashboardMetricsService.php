<?php

namespace App\Modules\IAM\Services;

use App\Models\User;
use App\Modules\Cms\Enums\PostStatus;
use App\Modules\Cms\Models\BlogPost;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Community\Enums\CommunityReportState;
use App\Modules\Community\Enums\ModerationQueueStatus;
use App\Modules\Community\Models\CommunityReport;
use App\Modules\Community\Models\ModerationQueueItem;
use App\Modules\Finance\Enums\CodStatus;
use App\Modules\Finance\Enums\PaymentMethod;
use App\Modules\Finance\Enums\RefundStatus;
use App\Modules\Finance\Enums\TransactionStatus;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\PaymentTransaction;
use App\Modules\Finance\Models\RefundRequest;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Services\OperationalScopeResolver;
use App\Modules\Promotions\Models\AbandonedCart;
use App\Modules\Promotions\Models\Coupon;
use App\Modules\Promotions\Models\CouponUsage;
use App\Modules\Reports\Models\ObservabilityEvent;
use App\Modules\Settings\Services\SettingsService;
use App\Modules\Shipping\Enums\ShipmentStatus;
use App\Modules\Shipping\Models\Shipment;
use App\Modules\Support\Models\ActivityTimeline;
use App\Modules\Support\Enums\IssuePriority;
use App\Modules\Support\Enums\IssueStatus;
use App\Modules\Support\Models\SupportIssue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardMetricsService
{
    protected const PRESETS = [
        '7d' => 7,
        '30d' => 30,
        '90d' => 90,
    ];

    public function build(User $user, ?string $preset = null): array
    {
        $activePreset = $this->normalizePreset($preset);
        $windowDays = self::PRESETS[$activePreset];

        $to = now()->endOfDay();
        $from = now()->copy()->subDays($windowDays - 1)->startOfDay();
        $previousTo = $from->copy()->subSecond();
        $previousFrom = $from->copy()->subDays($windowDays)->startOfDay();

        $roleSignature = implode('|', $user->getRoleNames()->sort()->values()->all());
        $cacheKey = sprintf(
            'dashboard:v4:%s:%s:%s:%s:%s',
            $user->id,
            md5($roleSignature),
            $activePreset,
            $from->toDateString(),
            $to->toDateString()
        );

        $ttlSeconds = max(0, (int) config('performance.dashboard_cache_ttl_seconds', 600));

        if (app()->runningUnitTests() || $ttlSeconds === 0) {
            return $this->buildPayload($user, $from, $to, $previousFrom, $previousTo, $activePreset, $windowDays);
        }

        return Cache::remember($cacheKey, now()->addSeconds($ttlSeconds), function () use ($user, $from, $to, $previousFrom, $previousTo, $activePreset, $windowDays): array {
            return $this->buildPayload($user, $from, $to, $previousFrom, $previousTo, $activePreset, $windowDays);
        });
    }

    protected function buildPayload(
        User $user,
        Carbon $from,
        Carbon $to,
        Carbon $previousFrom,
        Carbon $previousTo,
        string $activePreset,
        int $windowDays
    ): array
    {
        $isSuperAdmin = $user->hasRole('Super Admin');
        $isPlatform = $user->hasRole(['Super Admin', 'Platform Admin']);
        $isFranchiseDashboard = $isSuperAdmin || $user->hasRole('Franchise Manager') || $user->hasOrganizationScope('franchise');
        $isBranchDashboard = $user->hasRole('Branch Manager')
            || (($user->hasOrganizationScope('branch') || $user->hasOrganizationScope('own')) && $user->organization?->isBranch());
        $isSupportDashboard = ! $isPlatform
            && ($user->hasRole('Customer Support Agent') || $user->canAny(['support.viewAny', 'support.manage_tickets']));
        $isShippingDashboard = ! $isPlatform
            && ($user->hasRole('Shipping Agent') || $user->canAny(['shipping.viewAny', 'shipping.update', 'shipping.manage_carriers']));
        $isFinanceDashboard = ! $isPlatform
            && ($user->hasRole('Finance Manager') || $user->canAny(['finance.viewAny', 'payments.viewAny']));
        $isContentDashboard = ! $isPlatform
            && ($user->hasRole('SEO / Content Manager') || $user->canAny(['cms.viewAny', 'cms.manage_blog', 'cms.manage_pages']));
        $isMarketingDashboard = ! $isPlatform
            && ($user->hasRole('Media Buying / Marketing Manager') || $user->canAny(['coupons.viewAny', 'coupons.create', 'coupons.update']));
        $isSalesDashboard = ! $isPlatform
            && ($user->hasRole('Sales Manager') || $user->canAny(['orders.viewAny']));
        $isStockDashboard = ! $isPlatform
            && ($user->hasRole('Stock Manager') || $user->canAny(['inventory.viewAny', 'inventory.adjust', 'inventory.transfer']));
        $isModeratorDashboard = ! $isPlatform
            && ($user->hasRole('Community Moderator') || $user->canAny(['community.reports.viewAny', 'community.moderation.viewAny']));
        $canShip = $user->hasRole(['Super Admin', 'Shipping Agent']);
        $canSupport = $user->hasRole(['Super Admin', 'Customer Support Agent']);
        $canContent = $user->hasRole(['Super Admin', 'SEO / Content Manager']);
        $comparisonLabel = "previous {$windowDays} days";

        $orderWindow = [$from->toDateTimeString(), $to->toDateTimeString()];
        $previousWindow = [$previousFrom->toDateTimeString(), $previousTo->toDateTimeString()];
        $todayWindow = [now()->startOfDay()->toDateTimeString(), now()->endOfDay()->toDateTimeString()];
        $yesterdayStart = now()->copy()->subDay()->startOfDay();
        $yesterdayEnd = now()->copy()->subDay()->endOfDay();
        $yesterdayWindow = [$yesterdayStart->toDateTimeString(), $yesterdayEnd->toDateTimeString()];

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

        foreach (range(0, $windowDays - 1) as $offset) {
            $day = $from->copy()->addDays($offset);
            $row = $seriesRows->get($day->toDateString());

            $labels[] = $day->format('M j');
            $revenueSeries[] = round((float) ($row->revenue ?? 0), 2);
            $orderSeries[] = (int) ($row->orders ?? 0);
        }

        $revenueSnapshot = PaymentTransaction::completed()
            ->payments()
            ->toBase()
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN created_at BETWEEN ? AND ? THEN amount ELSE 0 END), 0) as current_revenue, '
                .'COALESCE(SUM(CASE WHEN created_at BETWEEN ? AND ? THEN amount ELSE 0 END), 0) as previous_revenue',
                [...$orderWindow, ...$previousWindow]
            )
            ->first();

        $currentRevenue = (float) ($revenueSnapshot->current_revenue ?? 0);
        $previousRevenue = (float) ($revenueSnapshot->previous_revenue ?? 0);

        $orderSnapshot = Order::query()
            ->toBase()
            ->selectRaw(
                'SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as current_orders, '
                .'SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as previous_orders, '
                .'COALESCE(SUM(CASE WHEN created_at BETWEEN ? AND ? THEN grand_total ELSE 0 END), 0) as current_gmv, '
                .'COALESCE(SUM(CASE WHEN created_at BETWEEN ? AND ? THEN grand_total ELSE 0 END), 0) as previous_gmv, '
                .'SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as orders_today, '
                .'SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as orders_yesterday',
                [...$orderWindow, ...$previousWindow, ...$orderWindow, ...$previousWindow, ...$todayWindow, ...$yesterdayWindow]
            )
            ->first();

        $currentOrders = (int) ($orderSnapshot->current_orders ?? 0);
        $previousOrders = (int) ($orderSnapshot->previous_orders ?? 0);
        $currentGmv = (float) ($orderSnapshot->current_gmv ?? 0);
        $previousGmv = (float) ($orderSnapshot->previous_gmv ?? 0);
        $ordersToday = (int) ($orderSnapshot->orders_today ?? 0);
        $ordersYesterday = (int) ($orderSnapshot->orders_yesterday ?? 0);

        $failedPaymentSnapshot = PaymentTransaction::query()
            ->payments()
            ->toBase()
            ->selectRaw(
                'SUM(CASE WHEN created_at BETWEEN ? AND ? AND status = ? THEN 1 ELSE 0 END) as current_failed, '
                .'SUM(CASE WHEN created_at BETWEEN ? AND ? AND status = ? THEN 1 ELSE 0 END) as previous_failed',
                [...$orderWindow, TransactionStatus::FAILED->value, ...$previousWindow, TransactionStatus::FAILED->value]
            )
            ->first();

        $currentFailedPayments = (int) ($failedPaymentSnapshot->current_failed ?? 0);
        $previousFailedPayments = (int) ($failedPaymentSnapshot->previous_failed ?? 0);

        $customerSnapshot = User::query()
            ->where('type', 'customer')
            ->toBase()
            ->selectRaw(
                'SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as current_customers, '
                .'SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as previous_customers',
                [...$orderWindow, ...$previousWindow]
            )
            ->first();

        $currentCustomers = (int) ($customerSnapshot->current_customers ?? 0);
        $previousCustomers = (int) ($customerSnapshot->previous_customers ?? 0);

        $stockAlerts = (int) StockItem::query()->whereIn('status', ['low_stock', 'out_of_stock'])->count();

        $primaryMetrics = $isPlatform ? [
            $this->metricCard('Revenue', $this->formatMoney($currentRevenue), 'payments', $this->changeLabel($currentRevenue, $previousRevenue, $comparisonLabel), "Last {$windowDays} days", 'positive'),
            $this->metricCard('Orders', number_format($currentOrders), 'shopping_bag', $this->changeLabel($currentOrders, $previousOrders, $comparisonLabel), "Placed in the last {$windowDays} days", 'positive'),
            $this->metricCard('New Customers', number_format($currentCustomers), 'group', $this->changeLabel($currentCustomers, $previousCustomers, $comparisonLabel), "New signups in the last {$windowDays} days", 'positive'),
            $this->metricCard('Stock Alerts', number_format($stockAlerts), 'inventory_2', 'Live snapshot', 'Low or out-of-stock items', $stockAlerts > 0 ? 'negative' : 'neutral', $stockAlerts > 0 ? 'error' : 'warning'),
        ] : [];

        $superAdminWidgets = $isSuperAdmin ? [
            $this->metricCard(
                'GMV Summary',
                $this->formatMoney($currentGmv),
                'monitoring',
                $this->changeLabel($currentGmv, $previousGmv, $comparisonLabel),
                "Gross merchandise value across {$windowDays} days",
                'positive'
            ),
            $this->metricCard(
                'Orders Today',
                number_format($ordersToday),
                'today',
                $this->changeLabel($ordersToday, $ordersYesterday, 'yesterday'),
                'Orders created since midnight',
                $ordersToday >= $ordersYesterday ? 'positive' : 'negative'
            ),
            $this->metricCard(
                'Failed Payments',
                number_format($currentFailedPayments),
                'credit_card_off',
                $this->changeLabel($currentFailedPayments, $previousFailedPayments, $comparisonLabel),
                "Failures recorded during {$windowDays}-day window",
                $currentFailedPayments > 0 ? 'negative' : 'neutral',
                $currentFailedPayments > 0 ? 'error' : 'warning'
            ),
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

        $statusCounts = Order::query()
            ->toBase()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->whereBetween('created_at', $orderWindow)
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $statusBoard = collect([
            OrderStatus::PENDING,
            OrderStatus::PREPARING,
            OrderStatus::SHIPPED,
            OrderStatus::DELIVERED,
            OrderStatus::FAILED,
            OrderStatus::CANCELLED,
        ])->map(function (OrderStatus $status) use ($statusCounts) {
            return [
                'label' => $status->label(),
                'count' => (int) ($statusCounts[$status->value] ?? 0),
                'tone' => match ($status) {
                    OrderStatus::DELIVERED => 'success',
                    OrderStatus::FAILED, OrderStatus::CANCELLED => 'danger',
                    OrderStatus::SHIPPED => 'info',
                    default => 'neutral',
                },
            ];
        });

        $shippingExceptionItems = $isSuperAdmin
            ? Shipment::query()
                ->with('order:id,reference_number')
                ->where(function ($builder) {
                    $builder
                        ->where('has_delivery_issue', true)
                        ->orWhereIn('status', [ShipmentStatus::FAILED_DELIVERY, ShipmentStatus::RETURNED]);
                })
                ->latest()
                ->limit(5)
                ->get()
                ->map(function (Shipment $shipment) {
                    return [
                        'reference' => $shipment->order?->reference_number ?? 'No order',
                        'status' => $shipment->status->label(),
                        'tone' => $shipment->status === ShipmentStatus::RETURNED ? 'danger' : 'warning',
                        'tracking' => $shipment->tracking_number ?: 'Pending tracking',
                    ];
                })
            : collect();

        $franchiseInventorySummary = $isSuperAdmin
            ? DB::table('organizations as branches')
                ->leftJoin('inventory_stock_items as stock_items', 'stock_items.branch_id', '=', 'branches.id')
                ->where('branches.type', Organization::TYPE_BRANCH)
                ->selectRaw(
                    "branches.parent_id as franchise_id,
                    SUM(CASE WHEN stock_items.status IN ('low_stock', 'out_of_stock') THEN 1 ELSE 0 END) as stock_alerts,
                    COALESCE(SUM(CASE WHEN stock_items.quantity - stock_items.reserved_quantity > 0 THEN stock_items.quantity - stock_items.reserved_quantity ELSE 0 END), 0) as available_units"
                )
                ->groupBy('branches.parent_id')
                ->get()
                ->keyBy('franchise_id')
            : collect();

        $franchisePerformance = $isSuperAdmin
            ? Organization::query()
                ->franchise()
                ->active()
                ->with([
                    'users:id',
                    'children' => fn ($query) => $query->branch()->active()->with('users:id'),
                ])
                ->get()
                ->map(function (Organization $franchise) use ($franchiseInventorySummary) {
                    $branches = $franchise->children;
                    $users = $branches->flatMap->users
                        ->merge($franchise->users)
                        ->unique('id');
                    $inventory = $franchiseInventorySummary->get($franchise->id);

                    return [
                        'name' => $franchise->name,
                        'branches' => $branches->count(),
                        'staff' => $users->count(),
                        'stock_alerts' => (int) ($inventory->stock_alerts ?? 0),
                        'available_units' => (int) ($inventory->available_units ?? 0),
                        'tone' => ((int) ($inventory->stock_alerts ?? 0)) > 0 ? 'warning' : 'success',
                    ];
                })
                ->sortByDesc('stock_alerts')
                ->take(5)
                ->values()
            : collect();

        $branchInventorySummary = $isSuperAdmin
            ? DB::table('inventory_stock_items')
                ->whereNotNull('branch_id')
                ->selectRaw(
                    "branch_id,
                    SUM(CASE WHEN status IN ('low_stock', 'out_of_stock') THEN 1 ELSE 0 END) as stock_alerts,
                    COALESCE(SUM(CASE WHEN quantity - reserved_quantity > 0 THEN quantity - reserved_quantity ELSE 0 END), 0) as available_units,
                    COUNT(*) as tracked_skus"
                )
                ->groupBy('branch_id')
                ->get()
                ->keyBy('branch_id')
            : collect();

        $branchPerformance = $isSuperAdmin
            ? Organization::query()
                ->branch()
                ->active()
                ->with(['users:id', 'parent:id,name'])
                ->get()
                ->map(function (Organization $branch) use ($branchInventorySummary) {
                    $inventory = $branchInventorySummary->get($branch->id);

                    return [
                        'name' => $branch->name,
                        'franchise' => $branch->parent?->name ?? 'Independent',
                        'staff' => $branch->users->unique('id')->count(),
                        'stock_alerts' => (int) ($inventory->stock_alerts ?? 0),
                        'available_units' => (int) ($inventory->available_units ?? 0),
                        'tracked_skus' => (int) ($inventory->tracked_skus ?? 0),
                        'tone' => ((int) ($inventory->stock_alerts ?? 0)) > 0 ? 'warning' : 'success',
                    ];
                })
                ->sortByDesc('stock_alerts')
                ->take(5)
                ->values()
            : collect();

        $franchiseBranchIds = $isFranchiseDashboard
            ? app(OperationalScopeResolver::class)->branchIdsFor($user)
            : [];

        $franchiseScopedStock = $franchiseBranchIds !== []
            ? DB::table('inventory_stock_items')
                ->whereIn('branch_id', $franchiseBranchIds)
                ->selectRaw(
                    "branch_id,
                    SUM(CASE WHEN status IN ('low_stock', 'out_of_stock') THEN 1 ELSE 0 END) as stock_alerts,
                    COALESCE(SUM(CASE WHEN quantity - reserved_quantity > 0 THEN quantity - reserved_quantity ELSE 0 END), 0) as available_units,
                    COALESCE(SUM(reserved_quantity), 0) as reserved_units,
                    COUNT(*) as tracked_skus"
                )
                ->groupBy('branch_id')
                ->get()
                ->keyBy('branch_id')
            : collect();

        $franchiseStaffSummary = $franchiseBranchIds !== []
            ? User::query()
                ->staff()
                ->whereIn('organization_id', $franchiseBranchIds)
                ->selectRaw(
                    "organization_id,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active_staff,
                    SUM(CASE WHEN last_login_at >= ? THEN 1 ELSE 0 END) as recently_active_staff",
                    [User::STATUS_ACTIVE, now()->subDays(7)->toDateTimeString()]
                )
                ->groupBy('organization_id')
                ->get()
                ->keyBy('organization_id')
            : collect();

        $franchiseMovementSummary = $franchiseBranchIds !== []
            ? DB::table('inventory_movements')
                ->join('inventory_stock_items', 'inventory_stock_items.id', '=', 'inventory_movements.stock_item_id')
                ->whereIn('inventory_stock_items.branch_id', $franchiseBranchIds)
                ->whereBetween('inventory_movements.created_at', $orderWindow)
                ->selectRaw(
                    "inventory_stock_items.branch_id,
                    COUNT(*) as movement_count,
                    SUM(CASE WHEN inventory_movements.type = 'deduction' THEN ABS(inventory_movements.quantity) ELSE 0 END) as outbound_units"
                )
                ->groupBy('inventory_stock_items.branch_id')
                ->get()
                ->keyBy('branch_id')
            : collect();

        $franchiseBranches = $franchiseBranchIds !== []
            ? Organization::query()
                ->branch()
                ->active()
                ->whereIn('id', $franchiseBranchIds)
                ->with('parent:id,name')
                ->orderBy('name')
                ->get()
            : collect();

        $salesByBranchItems = $isFranchiseDashboard
            ? $franchiseBranches
                ->map(function (Organization $branch) use ($franchiseScopedStock, $franchiseStaffSummary, $franchiseMovementSummary) {
                    $stock = $franchiseScopedStock->get($branch->id);
                    $staff = $franchiseStaffSummary->get($branch->id);
                    $movements = $franchiseMovementSummary->get($branch->id);

                    return [
                        'name' => $branch->name,
                        'franchise' => $branch->parent?->name ?? 'Franchise network',
                        'activity_count' => (int) ($movements->movement_count ?? 0),
                        'outbound_units' => (int) ($movements->outbound_units ?? 0),
                        'active_staff' => (int) ($staff->active_staff ?? 0),
                        'available_units' => (int) ($stock->available_units ?? 0),
                    ];
                })
                ->sortByDesc('activity_count')
                ->take(5)
                ->values()
            : collect();

        $franchiseStockOverviewItems = $isFranchiseDashboard
            ? $franchiseBranches
                ->map(function (Organization $branch) use ($franchiseScopedStock) {
                    $stock = $franchiseScopedStock->get($branch->id);

                    return [
                        'name' => $branch->name,
                        'stock_alerts' => (int) ($stock->stock_alerts ?? 0),
                        'tracked_skus' => (int) ($stock->tracked_skus ?? 0),
                        'available_units' => (int) ($stock->available_units ?? 0),
                        'tone' => ((int) ($stock->stock_alerts ?? 0)) > 0 ? 'warning' : 'success',
                    ];
                })
                ->sortByDesc('stock_alerts')
                ->take(5)
                ->values()
            : collect();

        $franchiseStaffPerformanceItems = $isFranchiseDashboard
            ? $franchiseBranches
                ->map(function (Organization $branch) use ($franchiseStaffSummary) {
                    $staff = $franchiseStaffSummary->get($branch->id);

                    return [
                        'name' => $branch->name,
                        'active_staff' => (int) ($staff->active_staff ?? 0),
                        'recently_active_staff' => (int) ($staff->recently_active_staff ?? 0),
                        'tone' => ((int) ($staff->recently_active_staff ?? 0)) > 0 ? 'success' : 'warning',
                    ];
                })
                ->sortByDesc('active_staff')
                ->take(5)
                ->values()
            : collect();

        $franchiseOrderMetricsItems = $isFranchiseDashboard
            ? $franchiseBranches
                ->map(function (Organization $branch) use ($franchiseScopedStock, $franchiseMovementSummary) {
                    $stock = $franchiseScopedStock->get($branch->id);
                    $movements = $franchiseMovementSummary->get($branch->id);

                    return [
                        'name' => $branch->name,
                        'reserved_units' => (int) ($stock->reserved_units ?? 0),
                        'stock_alerts' => (int) ($stock->stock_alerts ?? 0),
                        'recent_movements' => (int) ($movements->movement_count ?? 0),
                        'tone' => ((int) ($stock->reserved_units ?? 0)) > 0 ? 'info' : (((int) ($stock->stock_alerts ?? 0)) > 0 ? 'warning' : 'neutral'),
                    ];
                })
                ->sortByDesc('reserved_units')
                ->sortByDesc('recent_movements')
                ->take(5)
                ->values()
            : collect();

        $paymentExceptionItems = $isPlatform
            ? PaymentTransaction::query()
                ->with('order:id,reference_number')
                ->payments()
                ->whereIn('status', [TransactionStatus::PENDING, TransactionStatus::FAILED])
                ->latest()
                ->limit(5)
                ->get()
                ->map(function (PaymentTransaction $transaction) {
                    return [
                        'reference' => $transaction->reference,
                        'order_reference' => $transaction->order?->reference_number ?? 'No order',
                        'status' => $transaction->status->label(),
                        'tone' => $transaction->status === TransactionStatus::FAILED ? 'danger' : 'warning',
                        'amount' => $this->formatMoney((float) $transaction->amount, $transaction->currency),
                    ];
                })
            : collect();

        $draftPostCount = 0;
        $scheduledPostCount = 0;
        $activeCouponCount = 0;
        $recoverableCartCount = 0;

        if ($isPlatform) {
            try {
                $draftPostCount = BlogPost::query()->where('status', PostStatus::DRAFT)->count();
                $scheduledPostCount = BlogPost::query()->where('status', PostStatus::SCHEDULED)->count();
            } catch (\Throwable) {
                $draftPostCount = 0;
                $scheduledPostCount = 0;
            }

            try {
                $activeCouponCount = Coupon::query()->active()->count();
                $recoverableCartCount = AbandonedCart::query()->recoverable()->count();
            } catch (\Throwable) {
                $activeCouponCount = 0;
                $recoverableCartCount = 0;
            }
        }

        $supportActiveCount = SupportIssue::query()
            ->whereIn('status', [
                IssueStatus::OPEN,
                IssueStatus::IN_PROGRESS,
                IssueStatus::WAITING_CUSTOMER,
            ])
            ->count();

        $supportUrgentCount = SupportIssue::query()
            ->whereIn('status', [
                IssueStatus::OPEN,
                IssueStatus::IN_PROGRESS,
                IssueStatus::WAITING_CUSTOMER,
            ])
            ->whereIn('priority', [IssuePriority::URGENT, IssuePriority::HIGH])
            ->count();

        $supportUnassignedCount = SupportIssue::query()
            ->whereIn('status', [
                IssueStatus::OPEN,
                IssueStatus::IN_PROGRESS,
                IssueStatus::WAITING_CUSTOMER,
            ])
            ->whereNull('assigned_to')
            ->count();

        $supportPriorityItems = $isSuperAdmin
            ? SupportIssue::query()
                ->whereIn('status', [
                    IssueStatus::OPEN,
                    IssueStatus::IN_PROGRESS,
                    IssueStatus::WAITING_CUSTOMER,
                ])
                ->whereIn('priority', [IssuePriority::URGENT, IssuePriority::HIGH])
                ->latest()
                ->limit(4)
                ->get()
                ->map(function (SupportIssue $issue) {
                    return [
                        'reference' => $issue->reference,
                        'subject' => $issue->subject,
                        'priority' => $issue->priority->label(),
                        'tone' => $issue->priority === IssuePriority::URGENT ? 'danger' : 'warning',
                    ];
                })
            : collect();

        $refundStatuses = [
            RefundStatus::REQUESTED,
            RefundStatus::APPROVED,
            RefundStatus::PROCESSING,
        ];

        $refundQueue = RefundRequest::query()
            ->toBase()
            ->selectRaw(
                'COUNT(*) as aggregate, COALESCE(SUM(amount), 0) as total_amount'
            )
            ->whereIn('status', array_map(fn (RefundStatus $status) => $status->value, $refundStatuses))
            ->first();

        $pendingPaymentReview = PaymentTransaction::query()
            ->payments()
            ->toBase()
            ->selectRaw(
                'COUNT(*) as aggregate, COALESCE(SUM(amount), 0) as total_amount'
            )
            ->whereIn('status', [TransactionStatus::PENDING->value, TransactionStatus::FAILED->value])
            ->first();

        $systemAlertCounts = ObservabilityEvent::query()
            ->toBase()
            ->selectRaw(
                "SUM(CASE WHEN severity IN ('critical', 'error') THEN 1 ELSE 0 END) as critical_count,
                SUM(CASE WHEN severity = 'warning' THEN 1 ELSE 0 END) as warning_count"
            )
            ->first();

        $systemAlertItems = $isSuperAdmin
            ? ObservabilityEvent::query()
                ->whereIn('severity', ['critical', 'error', 'warning'])
                ->latest('occurred_at')
                ->limit(4)
                ->get()
                ->map(function (ObservabilityEvent $event) {
                    $tone = in_array($event->severity, ['critical', 'error'], true) ? 'danger' : 'warning';

                    return [
                        'name' => $event->name,
                        'source' => $event->source,
                        'severity' => Str::headline($event->severity),
                        'tone' => $tone,
                        'occurred_at' => optional($event->occurred_at)->diffForHumans() ?? 'Recent',
                    ];
                })
            : collect();

        $platformActionQueue = $isPlatform
            ? collect([
                [
                    'title' => 'Orders to Prepare',
                    'value' => (int) Order::query()->where('status', OrderStatus::PREPARING)->count(),
                    'meta' => 'Orders waiting for warehouse or branch handoff',
                    'href' => route('admin.orders.index', ['status' => OrderStatus::PREPARING->value]),
                    'cta' => 'Open orders',
                    'tone' => 'warning',
                ],
                [
                    'title' => 'Low Stock Hotspots',
                    'value' => $stockAlerts,
                    'meta' => 'Low-stock and out-of-stock items across active inventory',
                    'href' => route('admin.inventory.index', ['filter' => 'low_stock']),
                    'cta' => 'Open inventory',
                    'tone' => $stockAlerts > 0 ? 'warning' : 'success',
                ],
                [
                    'title' => 'Delivery Issue Queue',
                    'value' => (int) Shipment::query()
                        ->where(function ($builder) {
                            $builder
                                ->where('has_delivery_issue', true)
                                ->orWhereIn('status', [ShipmentStatus::FAILED_DELIVERY, ShipmentStatus::RETURNED]);
                        })
                        ->count(),
                    'meta' => 'Returns, failed drops, and flagged delivery issues',
                    'href' => route('admin.shipping.shipments.index', ['issues' => 1]),
                    'cta' => 'Review shipments',
                    'tone' => 'danger',
                ],
                [
                    'title' => 'Open Support Issues',
                    'value' => $supportActiveCount,
                    'meta' => 'Active customer issues requiring operator follow-up',
                    'href' => route('admin.support.issues.index', ['status' => IssueStatus::OPEN->value]),
                    'cta' => 'Open support',
                    'tone' => $supportActiveCount > 0 ? 'info' : 'success',
                ],
            ])->values()
            : collect();

        $platformWorkspace = $isPlatform
            ? [
                'recent_orders' => [
                    'items' => $recentOrders->take(5)->values()->all(),
                    'href' => route('admin.orders.index'),
                ],
                'stock_alerts' => [
                    'count' => $stockAlerts,
                    'items' => $lowStock->take(5)->values()->all(),
                    'href' => route('admin.inventory.index', ['filter' => 'low_stock']),
                ],
                'payment_exceptions' => [
                    'count' => (int) $paymentExceptionItems->count(),
                    'items' => $paymentExceptionItems->values()->all(),
                    'href' => route('admin.finance.transactions.index', ['status' => TransactionStatus::FAILED->value]),
                ],
                'content_promo_highlights' => [
                    'draft_posts' => $draftPostCount,
                    'scheduled_posts' => $scheduledPostCount,
                    'active_coupons' => $activeCouponCount,
                    'recoverable_carts' => $recoverableCartCount,
                    'content_href' => route('admin.cms.posts.index'),
                    'promo_href' => route('admin.promotions.coupons.index'),
                ],
            ]
            : [];

        $franchiseWorkspace = $isFranchiseDashboard
            ? [
                'sales_by_branch' => [
                    'items' => $salesByBranchItems->all(),
                    'href' => route('admin.inventory.branches.index'),
                ],
                'branch_stock_overview' => [
                    'items' => $franchiseStockOverviewItems->all(),
                    'href' => route('admin.inventory.branches.index'),
                ],
                'staff_performance_summary' => [
                    'items' => $franchiseStaffPerformanceItems->all(),
                    'href' => route('admin.staff.index'),
                ],
                'branch_order_metrics' => [
                    'items' => $franchiseOrderMetricsItems->all(),
                    'href' => route('admin.inventory.branches.index'),
                ],
            ]
            : [];

        $branchDashboardBranchId = $isBranchDashboard
            ? collect(app(OperationalScopeResolver::class)->branchIdsFor($user))->first()
            : null;

        $branchDashboardBranch = $branchDashboardBranchId
            ? Organization::query()->with('parent:id,name')->find($branchDashboardBranchId)
            : null;

        $branchStockSummary = $branchDashboardBranchId
            ? DB::table('inventory_stock_items')
                ->where('branch_id', $branchDashboardBranchId)
                ->selectRaw(
                    "SUM(CASE WHEN status IN ('low_stock', 'out_of_stock') THEN 1 ELSE 0 END) as stock_alerts,
                    COALESCE(SUM(CASE WHEN quantity - reserved_quantity > 0 THEN quantity - reserved_quantity ELSE 0 END), 0) as available_units,
                    COALESCE(SUM(reserved_quantity), 0) as reserved_units,
                    COUNT(*) as tracked_skus"
                )
                ->first()
            : null;

        $branchReservationSummary = $branchDashboardBranchId
            ? DB::table('inventory_movements')
                ->join('inventory_stock_items', 'inventory_stock_items.id', '=', 'inventory_movements.stock_item_id')
                ->where('inventory_stock_items.branch_id', $branchDashboardBranchId)
                ->whereBetween('inventory_movements.created_at', $orderWindow)
                ->selectRaw(
                    "SUM(CASE WHEN inventory_movements.type = 'reservation' THEN 1 ELSE 0 END) as reservation_events,
                    SUM(CASE WHEN inventory_movements.type = 'deduction' THEN 1 ELSE 0 END) as outbound_events,
                    SUM(CASE WHEN inventory_movements.type = 'deduction' THEN ABS(inventory_movements.quantity) ELSE 0 END) as outbound_units,
                    SUM(CASE WHEN inventory_movements.reason IN ('damage', 'manual_adjustment') AND inventory_movements.type = 'deduction' THEN 1 ELSE 0 END) as issue_events"
                )
                ->first()
            : null;

        $branchRecentReservations = $branchDashboardBranchId
            ? DB::table('inventory_movements')
                ->join('inventory_stock_items', 'inventory_stock_items.id', '=', 'inventory_movements.stock_item_id')
                ->leftJoin('products', 'products.id', '=', 'inventory_stock_items.product_id')
                ->leftJoin('product_variants', 'product_variants.id', '=', 'inventory_stock_items.product_variant_id')
                ->where('inventory_stock_items.branch_id', $branchDashboardBranchId)
                ->where('inventory_movements.type', 'reservation')
                ->latest('inventory_movements.created_at')
                ->limit(5)
                ->get([
                    'products.name as product_name',
                    'products.sku as product_sku',
                    'product_variants.sku as variant_sku',
                    'inventory_movements.notes',
                    'inventory_movements.created_at',
                ])
                ->map(function ($row) {
                    return [
                        'name' => $row->product_name ?? 'Reserved item',
                        'sku' => $row->variant_sku ?: ($row->product_sku ?? 'NO-SKU'),
                        'notes' => $row->notes ?: 'Reserved for order preparation',
                        'date' => Carbon::parse($row->created_at)->diffForHumans(),
                    ];
                })
            : collect();

        $branchLowStockItems = $branchDashboardBranchId
            ? StockItem::query()
                ->with(['product:id,name,sku', 'variant:id,product_id,sku'])
                ->where('branch_id', $branchDashboardBranchId)
                ->whereIn('status', ['low_stock', 'out_of_stock'])
                ->orderByRaw('CASE WHEN quantity - reserved_quantity < 0 THEN 0 ELSE quantity - reserved_quantity END asc')
                ->limit(5)
                ->get()
                ->map(function (StockItem $item) {
                    return [
                        'name' => $item->product?->name ?? 'Deleted product',
                        'sku' => $item->variant?->sku ?: ($item->product?->sku ?? 'NO-SKU'),
                        'available' => max(0, $item->quantity - $item->reserved_quantity),
                        'threshold' => (int) ($item->low_stock_threshold ?? 0),
                        'tone' => max(0, $item->quantity - $item->reserved_quantity) === 0 ? 'danger' : 'warning',
                    ];
                })
            : collect();

        $branchRecentOutbound = $branchDashboardBranchId
            ? DB::table('inventory_movements')
                ->join('inventory_stock_items', 'inventory_stock_items.id', '=', 'inventory_movements.stock_item_id')
                ->leftJoin('products', 'products.id', '=', 'inventory_stock_items.product_id')
                ->leftJoin('product_variants', 'product_variants.id', '=', 'inventory_stock_items.product_variant_id')
                ->where('inventory_stock_items.branch_id', $branchDashboardBranchId)
                ->where('inventory_movements.type', 'deduction')
                ->latest('inventory_movements.created_at')
                ->limit(5)
                ->get([
                    'products.name as product_name',
                    'products.sku as product_sku',
                    'product_variants.sku as variant_sku',
                    'inventory_movements.reason',
                    'inventory_movements.quantity',
                    'inventory_movements.created_at',
                ])
                ->map(function ($row) {
                    return [
                        'name' => $row->product_name ?? 'Outbound item',
                        'sku' => $row->variant_sku ?: ($row->product_sku ?? 'NO-SKU'),
                        'reason' => Str::headline((string) $row->reason),
                        'units' => abs((int) $row->quantity),
                        'date' => Carbon::parse($row->created_at)->diffForHumans(),
                    ];
                })
            : collect();

        $branchRecentIssues = $branchDashboardBranchId
            ? DB::table('inventory_movements')
                ->join('inventory_stock_items', 'inventory_stock_items.id', '=', 'inventory_movements.stock_item_id')
                ->leftJoin('products', 'products.id', '=', 'inventory_stock_items.product_id')
                ->leftJoin('product_variants', 'product_variants.id', '=', 'inventory_stock_items.product_variant_id')
                ->where('inventory_stock_items.branch_id', $branchDashboardBranchId)
                ->whereIn('inventory_movements.reason', ['damage', 'manual_adjustment'])
                ->latest('inventory_movements.created_at')
                ->limit(5)
                ->get([
                    'products.name as product_name',
                    'products.sku as product_sku',
                    'product_variants.sku as variant_sku',
                    'inventory_movements.reason',
                    'inventory_movements.quantity',
                    'inventory_movements.created_at',
                ])
                ->map(function ($row) {
                    return [
                        'name' => $row->product_name ?? 'Issue item',
                        'sku' => $row->variant_sku ?: ($row->product_sku ?? 'NO-SKU'),
                        'reason' => Str::headline((string) $row->reason),
                        'units' => abs((int) $row->quantity),
                        'date' => Carbon::parse($row->created_at)->diffForHumans(),
                    ];
                })
            : collect();

        $branchWorkspace = $branchDashboardBranch
            ? [
                'branch_name' => $branchDashboardBranch->name,
                'franchise_name' => $branchDashboardBranch->parent?->name ?? 'Branch network',
                'orders_to_prepare' => [
                    'reserved_units' => (int) ($branchStockSummary->reserved_units ?? 0),
                    'reservation_events' => (int) ($branchReservationSummary->reservation_events ?? 0),
                    'items' => $branchRecentReservations->values()->all(),
                    'href' => route('admin.inventory.branches.show', $branchDashboardBranch),
                ],
                'stock_widgets' => [
                    'tracked_skus' => (int) ($branchStockSummary->tracked_skus ?? 0),
                    'available_units' => (int) ($branchStockSummary->available_units ?? 0),
                    'stock_alerts' => (int) ($branchStockSummary->stock_alerts ?? 0),
                    'items' => $branchLowStockItems->values()->all(),
                    'href' => route('admin.inventory.branches.show', $branchDashboardBranch),
                ],
                'dispatch_queue' => [
                    'outbound_events' => (int) ($branchReservationSummary->outbound_events ?? 0),
                    'outbound_units' => (int) ($branchReservationSummary->outbound_units ?? 0),
                    'items' => $branchRecentOutbound->values()->all(),
                    'href' => route('admin.inventory.reports.adjustments'),
                ],
                'issue_queue' => [
                    'issue_events' => (int) ($branchReservationSummary->issue_events ?? 0),
                    'stock_alerts' => (int) ($branchStockSummary->stock_alerts ?? 0),
                    'items' => $branchRecentIssues->values()->all(),
                    'href' => route('admin.inventory.branches.show', $branchDashboardBranch),
                ],
            ]
            : [];

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

        $supportLookupOrders = $isSupportDashboard
            ? $recentOrders->take(5)->map(function (array $order) {
                return [
                    'reference' => $order['reference'],
                    'customer' => $order['customer'],
                    'status' => $order['status'],
                    'status_tone' => $order['status_tone'],
                    'meta' => $order['date'],
                    'href' => route('admin.support.lookup', ['q' => $order['reference']]),
                ];
            })
            : collect();

        $supportLookupCustomers = $isSupportDashboard
            ? SupportIssue::query()
                ->with('order:id,reference_number')
                ->where(function ($query) {
                    $query
                        ->whereNotNull('customer_email')
                        ->orWhereNotNull('customer_name');
                })
                ->latest()
                ->limit(10)
                ->get()
                ->map(function (SupportIssue $issue) {
                    $query = $issue->customer_email ?: $issue->customer_name ?: $issue->reference;

                    return [
                        'name' => $issue->customer_name ?: 'Customer contact',
                        'contact' => $issue->customer_email ?: 'Lookup by ticket reference',
                        'context' => $issue->order?->reference_number ? 'Order '.$issue->order->reference_number : $issue->reference,
                        'href' => route('admin.support.lookup', ['q' => $query]),
                    ];
                })
                ->filter(fn (array $item) => filled($item['href']))
                ->unique('href')
                ->take(5)
                ->values()
            : collect();

        $supportQueueIssues = $isSupportDashboard
            ? SupportIssue::query()
                ->with('order:id,reference_number')
                ->active()
                ->orderByRaw(
                    "CASE priority
                        WHEN 'urgent' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'medium' THEN 3
                        WHEN 'low' THEN 4
                        ELSE 5
                    END"
                )
                ->latest()
                ->limit(4)
                ->get()
                ->map(function (SupportIssue $issue) {
                    return [
                        'reference' => $issue->reference,
                        'subject' => $issue->subject,
                        'status' => $issue->status->label(),
                        'status_tone' => $this->mapIssueStatusTone($issue->status),
                        'priority' => $issue->priority->label(),
                        'priority_tone' => $this->mapIssuePriorityTone($issue->priority),
                        'href' => route('admin.support.issues.show', $issue),
                    ];
                })
            : collect();

        $supportQueueRefunds = $isSupportDashboard
            ? RefundRequest::query()
                ->with('order:id,reference_number')
                ->whereIn('status', array_map(fn (RefundStatus $status) => $status->value, $refundStatuses))
                ->latest()
                ->limit(4)
                ->get()
                ->map(function (RefundRequest $refund) {
                    return [
                        'reference' => $refund->reference,
                        'order_reference' => $refund->order?->reference_number ?? 'No order',
                        'status' => $refund->status->label(),
                        'tone' => $this->mapRefundTone($refund->status),
                        'amount' => $this->formatMoney((float) $refund->amount, $refund->currency),
                    ];
                })
            : collect();

        $recentIssueTimeline = $isSupportDashboard
            ? ActivityTimeline::query()
                ->with('subject')
                ->where('subject_type', SupportIssue::class)
                ->recent()
                ->limit(5)
                ->get()
                ->map(function (ActivityTimeline $entry) {
                    $issue = $entry->subject instanceof SupportIssue ? $entry->subject : null;

                    return [
                        'reference' => $issue?->reference ?? 'Support issue',
                        'description' => $entry->description,
                        'action' => Str::headline(str_replace('_', ' ', $entry->action)),
                        'occurred_at' => $entry->created_at?->diffForHumans() ?? 'Recent',
                        'href' => $issue ? route('admin.support.issues.show', $issue) : route('admin.support.issues.index'),
                    ];
                })
            : collect();

        $supportWorkspace = $isSupportDashboard
            ? [
                'order_lookup' => [
                    'searchable_orders' => (int) Order::query()->count(),
                    'preparing_orders' => (int) Order::query()->where('status', OrderStatus::PREPARING)->count(),
                    'items' => $supportLookupOrders->all(),
                    'href' => route('admin.support.lookup'),
                ],
                'customer_lookup' => [
                    'active_customers' => (int) User::query()->where('type', User::TYPE_CUSTOMER)->count(),
                    'open_customer_cases' => (int) SupportIssue::query()->active()->whereNotNull('customer_email')->count(),
                    'items' => $supportLookupCustomers->all(),
                    'href' => route('admin.support.lookup'),
                ],
                'open_cases_refund_queue' => [
                    'open_cases' => $supportActiveCount,
                    'urgent_cases' => $supportUrgentCount,
                    'refund_queue_count' => (int) ($refundQueue->aggregate ?? 0),
                    'refund_queue_amount' => $this->formatMoney((float) ($refundQueue->total_amount ?? 0)),
                    'issues' => $supportQueueIssues->all(),
                    'refunds' => $supportQueueRefunds->all(),
                    'issues_href' => route('admin.support.issues.index'),
                    'refunds_href' => route('admin.finance.refunds.index'),
                ],
                'recent_issue_timeline' => [
                    'items' => $recentIssueTimeline->all(),
                    'href' => route('admin.support.issues.index'),
                ],
            ]
            : [];

        $readyToShipItems = $isShippingDashboard
            ? Shipment::query()
                ->with('order:id,reference_number')
                ->whereIn('status', [ShipmentStatus::READY_TO_SHIP, ShipmentStatus::PACKED])
                ->latest()
                ->limit(5)
                ->get()
                ->map(function (Shipment $shipment) {
                    return [
                        'reference' => $shipment->order?->reference_number ?? 'No order',
                        'tracking' => $shipment->tracking_number ?: 'Pending tracking',
                        'status' => $shipment->status->label(),
                        'tone' => $this->mapShipmentTone($shipment->status),
                        'date' => $shipment->updated_at?->diffForHumans() ?? 'Recent',
                    ];
                })
            : collect();

        $shippedItems = $isShippingDashboard
            ? Shipment::query()
                ->with('order:id,reference_number')
                ->whereIn('status', [ShipmentStatus::DISPATCHED, ShipmentStatus::IN_TRANSIT, ShipmentStatus::DELIVERED])
                ->latest('dispatched_at')
                ->limit(5)
                ->get()
                ->map(function (Shipment $shipment) {
                    return [
                        'reference' => $shipment->order?->reference_number ?? 'No order',
                        'tracking' => $shipment->tracking_number ?: 'Pending tracking',
                        'status' => $shipment->status->label(),
                        'tone' => $this->mapShipmentTone($shipment->status),
                        'date' => optional($shipment->dispatched_at ?? $shipment->updated_at)?->diffForHumans() ?? 'Recent',
                    ];
                })
            : collect();

        $failedDeliveryItems = $isShippingDashboard
            ? Shipment::query()
                ->with('order:id,reference_number')
                ->where('status', ShipmentStatus::FAILED_DELIVERY)
                ->latest('failed_at')
                ->limit(5)
                ->get()
                ->map(function (Shipment $shipment) {
                    return [
                        'reference' => $shipment->order?->reference_number ?? 'No order',
                        'tracking' => $shipment->tracking_number ?: 'Pending tracking',
                        'reason' => $shipment->failure_reason ?: ($shipment->delivery_issue_notes ?: 'Delivery follow-up needed'),
                        'date' => optional($shipment->failed_at ?? $shipment->updated_at)?->diffForHumans() ?? 'Recent',
                    ];
                })
            : collect();

        $returnedItems = $isShippingDashboard
            ? Shipment::query()
                ->with('order:id,reference_number')
                ->where('status', ShipmentStatus::RETURNED)
                ->latest('returned_at')
                ->limit(5)
                ->get()
                ->map(function (Shipment $shipment) {
                    return [
                        'reference' => $shipment->order?->reference_number ?? 'No order',
                        'tracking' => $shipment->tracking_number ?: 'Pending tracking',
                        'carrier' => $shipment->carrier_name ?: 'Unassigned carrier',
                        'date' => optional($shipment->returned_at ?? $shipment->updated_at)?->diffForHumans() ?? 'Recent',
                    ];
                })
            : collect();

        $shippingWorkspace = $isShippingDashboard
            ? [
                'ready_to_ship' => [
                    'ready_count' => (int) Shipment::query()->where('status', ShipmentStatus::READY_TO_SHIP)->count(),
                    'packed_count' => (int) Shipment::query()->where('status', ShipmentStatus::PACKED)->count(),
                    'items' => $readyToShipItems->all(),
                    'href' => route('admin.shipping.shipments.index', ['status' => ShipmentStatus::READY_TO_SHIP->value]),
                ],
                'shipped' => [
                    'dispatched_count' => (int) Shipment::query()->where('status', ShipmentStatus::DISPATCHED)->count(),
                    'in_transit_count' => (int) Shipment::query()->where('status', ShipmentStatus::IN_TRANSIT)->count(),
                    'items' => $shippedItems->all(),
                    'href' => route('admin.shipping.shipments.index', ['status' => ShipmentStatus::IN_TRANSIT->value]),
                ],
                'failed_delivery' => [
                    'failed_count' => (int) Shipment::query()->where('status', ShipmentStatus::FAILED_DELIVERY)->count(),
                    'issue_count' => (int) Shipment::query()->where('has_delivery_issue', true)->count(),
                    'items' => $failedDeliveryItems->all(),
                    'href' => route('admin.shipping.shipments.index', ['status' => ShipmentStatus::FAILED_DELIVERY->value]),
                ],
                'returned_parcel_queue' => [
                    'returned_count' => (int) Shipment::query()->where('status', ShipmentStatus::RETURNED)->count(),
                    'returned_today' => (int) Shipment::query()->where('status', ShipmentStatus::RETURNED)->whereDate('returned_at', today())->count(),
                    'items' => $returnedItems->all(),
                    'href' => route('admin.shipping.shipments.index', ['status' => ShipmentStatus::RETURNED->value]),
                ],
            ]
            : [];

        $trackingQueueItems = $isShippingDashboard
            ? Shipment::query()
                ->with('order:id,reference_number')
                ->whereIn('status', [
                    ShipmentStatus::PACKED,
                    ShipmentStatus::DISPATCHED,
                    ShipmentStatus::IN_TRANSIT,
                    ShipmentStatus::FAILED_DELIVERY,
                    ShipmentStatus::RETURNED,
                ])
                ->where(function ($builder) {
                    $builder->whereNull('tracking_number')->orWhere('tracking_number', '');
                })
                ->latest()
                ->limit(5)
                ->get()
                ->map(function (Shipment $shipment) {
                    return [
                        'reference' => $shipment->order?->reference_number ?? 'No order',
                        'status' => $shipment->status->label(),
                        'tone' => $this->mapShipmentTone($shipment->status),
                        'carrier' => $shipment->carrier_name ?: 'Unassigned carrier',
                        'date' => $shipment->updated_at?->diffForHumans() ?? 'Recent',
                    ];
                })
            : collect();

        if ($shippingWorkspace !== []) {
            $shippingWorkspace['tracking_queue'] = [
                'missing_tracking_count' => (int) Shipment::query()
                    ->whereIn('status', [
                        ShipmentStatus::PACKED,
                        ShipmentStatus::DISPATCHED,
                        ShipmentStatus::IN_TRANSIT,
                        ShipmentStatus::FAILED_DELIVERY,
                        ShipmentStatus::RETURNED,
                    ])
                    ->where(function ($builder) {
                        $builder->whereNull('tracking_number')->orWhere('tracking_number', '');
                    })
                    ->count(),
                'tracked_active_count' => (int) Shipment::query()
                    ->whereIn('status', [ShipmentStatus::DISPATCHED, ShipmentStatus::IN_TRANSIT])
                    ->whereNotNull('tracking_number')
                    ->where('tracking_number', '!=', '')
                    ->count(),
                'items' => $trackingQueueItems->all(),
                'href' => route('admin.shipping.shipments.index', ['tracking' => 'missing']),
            ];
        }

        $financePaymentSummary = $isFinanceDashboard
            ? PaymentTransaction::query()
                ->payments()
                ->toBase()
                ->selectRaw(
                    'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as paid_count,
                    COALESCE(SUM(CASE WHEN status = ? THEN amount ELSE 0 END), 0) as paid_volume,
                    SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as unpaid_count,
                    COALESCE(SUM(CASE WHEN status IN (?, ?) THEN amount ELSE 0 END), 0) as unpaid_volume',
                    [
                        TransactionStatus::COMPLETED->value,
                        TransactionStatus::COMPLETED->value,
                        TransactionStatus::PENDING->value,
                        TransactionStatus::FAILED->value,
                        TransactionStatus::PENDING->value,
                        TransactionStatus::FAILED->value,
                    ]
                )
                ->first()
            : null;

        $paymentMethodItems = $isFinanceDashboard
            ? PaymentTransaction::query()
                ->payments()
                ->toBase()
                ->selectRaw('payment_method, COUNT(*) as aggregate, COALESCE(SUM(amount), 0) as total_amount')
                ->groupBy('payment_method')
                ->orderByDesc('aggregate')
                ->limit(5)
                ->get()
                ->map(function ($row) {
                    $method = PaymentMethod::tryFrom((string) $row->payment_method);

                    return [
                        'method' => $method?->label() ?? Str::headline((string) $row->payment_method),
                        'key' => (string) $row->payment_method,
                        'count' => (int) $row->aggregate,
                        'amount' => $this->formatMoney((float) $row->total_amount),
                    ];
                })
            : collect();

        $gatewayItems = $isFinanceDashboard
            ? DB::table('payment_transactions')
                ->selectRaw(
                    "COALESCE(NULLIF(gateway, ''), 'manual') as gateway_label,
                    COUNT(*) as aggregate,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed_count,
                    COALESCE(SUM(amount), 0) as total_amount",
                    [TransactionStatus::FAILED->value]
                )
                ->where('type', TransactionType::PAYMENT->value)
                ->groupBy('gateway_label')
                ->orderByDesc('aggregate')
                ->limit(5)
                ->get()
                ->map(function ($row) {
                    return [
                        'gateway' => Str::headline((string) $row->gateway_label),
                        'key' => (string) $row->gateway_label,
                        'count' => (int) $row->aggregate,
                        'failed_count' => (int) $row->failed_count,
                        'amount' => $this->formatMoney((float) $row->total_amount),
                    ];
                })
            : collect();

        $financeWorkspace = $isFinanceDashboard
            ? [
                'paid_unpaid_summary' => [
                    'paid_count' => (int) ($financePaymentSummary->paid_count ?? 0),
                    'paid_volume' => $this->formatMoney((float) ($financePaymentSummary->paid_volume ?? 0)),
                    'unpaid_count' => (int) ($financePaymentSummary->unpaid_count ?? 0),
                    'unpaid_volume' => $this->formatMoney((float) ($financePaymentSummary->unpaid_volume ?? 0)),
                    'href' => route('admin.finance.transactions.index'),
                ],
                'payment_method_summary' => [
                    'items' => $paymentMethodItems->all(),
                    'href' => route('admin.finance.transactions.index'),
                ],
                'gateway_transactions_summary' => [
                    'items' => $gatewayItems->all(),
                    'href' => route('admin.finance.transactions.index'),
                ],
            ]
            : [];

        if ($financeWorkspace !== []) {
            $codSummary = PaymentTransaction::query()
                ->payments()
                ->cod()
                ->toBase()
                ->selectRaw(
                    'SUM(CASE WHEN cod_status = ? THEN 1 ELSE 0 END) as collected_count,
                    SUM(CASE WHEN cod_status = ? THEN 1 ELSE 0 END) as reconciled_count,
                    SUM(CASE WHEN cod_status = ? THEN 1 ELSE 0 END) as discrepancy_count,
                    COALESCE(SUM(CASE WHEN cod_status IN (?, ?, ?) THEN amount ELSE 0 END), 0) as outstanding_amount',
                    [
                        CodStatus::COLLECTED->value,
                        CodStatus::RECONCILED->value,
                        CodStatus::DISCREPANCY->value,
                        CodStatus::PENDING->value,
                        CodStatus::COLLECTED->value,
                        CodStatus::DEPOSITED->value,
                    ]
                )
                ->first();

            $refundSummary = RefundRequest::query()
                ->toBase()
                ->selectRaw(
                    'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as requested_count,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved_count,
                    COALESCE(SUM(CASE WHEN status = ? THEN amount ELSE 0 END), 0) as queued_amount',
                    [
                        RefundStatus::REQUESTED->value,
                        RefundStatus::APPROVED->value,
                        RefundStatus::REQUESTED->value,
                    ]
                )
                ->first();

            $discountImpact = PaymentTransaction::query()
                ->payments()
                ->toBase()
                ->selectRaw(
                    'SUM(CASE WHEN COALESCE(order_discount, 0) > 0 THEN 1 ELSE 0 END) as discounted_orders,
                    COALESCE(SUM(order_discount), 0) as total_discounts,
                    COALESCE(SUM(fee_amount), 0) as total_fees'
                )
                ->first();

            $financeWorkspace['cod_reconciliation_summary'] = [
                'collected_count' => (int) ($codSummary->collected_count ?? 0),
                'reconciled_count' => (int) ($codSummary->reconciled_count ?? 0),
                'discrepancy_count' => (int) ($codSummary->discrepancy_count ?? 0),
                'outstanding_amount' => $this->formatMoney((float) ($codSummary->outstanding_amount ?? 0)),
                'href' => route('admin.finance.transactions.index', ['method' => PaymentMethod::CASH_ON_DELIVERY->value]),
            ];

            $financeWorkspace['refund_summary'] = [
                'requested_count' => (int) ($refundSummary->requested_count ?? 0),
                'approved_count' => (int) ($refundSummary->approved_count ?? 0),
                'queued_amount' => $this->formatMoney((float) ($refundSummary->queued_amount ?? 0)),
                'href' => route('admin.finance.refunds.index'),
            ];

            $financeWorkspace['discount_fee_impact'] = [
                'discounted_orders' => (int) ($discountImpact->discounted_orders ?? 0),
                'total_discounts' => $this->formatMoney((float) ($discountImpact->total_discounts ?? 0)),
                'total_fees' => $this->formatMoney((float) ($discountImpact->total_fees ?? 0)),
                'href' => route('admin.finance.transactions.index'),
            ];
        }

        $contentWorkspace = [];

        if ($isContentDashboard) {
            try {
                $draftItems = BlogPost::query()
                    ->with('author:id,name')
                    ->draft()
                    ->latest()
                    ->limit(5)
                    ->get()
                    ->map(function (BlogPost $post) {
                        return [
                            'title' => $post->title,
                            'author' => $post->author?->name ?? 'Unknown author',
                            'updated_at' => $post->updated_at?->diffForHumans() ?? 'Recent',
                        ];
                    });

                $missingMetadataCollections = collect()
                    ->concat(
                        BlogPost::query()
                            ->where(function ($query) {
                                $query->whereNull('meta_title')
                                    ->orWhere('meta_title', '')
                                    ->orWhereNull('meta_description')
                                    ->orWhere('meta_description', '');
                            })
                            ->latest('updated_at')
                            ->limit(3)
                            ->get()
                            ->map(function (BlogPost $post) {
                                $missing = [];

                                if (blank($post->meta_title)) {
                                    $missing[] = 'Meta title';
                                }

                                if (blank($post->meta_description)) {
                                    $missing[] = 'Meta description';
                                }

                                return [
                                    'title' => $post->title,
                                    'type' => 'Blog Post',
                                    'missing' => implode(' / ', $missing),
                                    'updated_at' => $post->updated_at?->diffForHumans() ?? 'Recent',
                                    'updated_sort' => $post->updated_at?->timestamp ?? 0,
                                ];
                            })
                    )
                    ->concat(
                        Product::query()
                            ->where('status', 'published')
                            ->where(function ($query) {
                                $query->whereNull('meta_title')
                                    ->orWhere('meta_title', '')
                                    ->orWhereNull('meta_description')
                                    ->orWhere('meta_description', '');
                            })
                            ->latest('updated_at')
                            ->limit(3)
                            ->get()
                            ->map(function (Product $product) {
                                $missing = [];

                                if (blank($product->meta_title)) {
                                    $missing[] = 'Meta title';
                                }

                                if (blank($product->meta_description)) {
                                    $missing[] = 'Meta description';
                                }

                                return [
                                    'title' => $product->name,
                                    'type' => 'Product',
                                    'missing' => implode(' / ', $missing),
                                    'updated_at' => $product->updated_at?->diffForHumans() ?? 'Recent',
                                    'updated_sort' => $product->updated_at?->timestamp ?? 0,
                                ];
                            })
                    )
                    ->concat(
                        Category::query()
                            ->active()
                            ->where(function ($query) {
                                $query->whereNull('meta_title')
                                    ->orWhere('meta_title', '')
                                    ->orWhereNull('meta_description')
                                    ->orWhere('meta_description', '');
                            })
                            ->latest('updated_at')
                            ->limit(3)
                            ->get()
                            ->map(function (Category $category) {
                                $missing = [];

                                if (blank($category->meta_title)) {
                                    $missing[] = 'Meta title';
                                }

                                if (blank($category->meta_description)) {
                                    $missing[] = 'Meta description';
                                }

                                return [
                                    'title' => $category->name,
                                    'type' => 'Category',
                                    'missing' => implode(' / ', $missing),
                                    'updated_at' => $category->updated_at?->diffForHumans() ?? 'Recent',
                                    'updated_sort' => $category->updated_at?->timestamp ?? 0,
                                ];
                            })
                    )
                    ->sortByDesc('updated_sort')
                    ->take(6)
                    ->values()
                    ->map(function (array $item) {
                        unset($item['updated_sort']);

                        return $item;
                    });

                $missingMetaTitleCount = (int) BlogPost::query()
                    ->whereNull('meta_title')
                    ->orWhere('meta_title', '')
                    ->count()
                    + (int) Product::query()
                        ->where('status', 'published')
                        ->where(function ($query) {
                            $query->whereNull('meta_title')
                                ->orWhere('meta_title', '');
                        })
                        ->count()
                    + (int) Category::query()
                        ->active()
                        ->where(function ($query) {
                            $query->whereNull('meta_title')
                                ->orWhere('meta_title', '');
                        })
                        ->count();

                $missingMetaDescriptionCount = (int) BlogPost::query()
                    ->whereNull('meta_description')
                    ->orWhere('meta_description', '')
                    ->count()
                    + (int) Product::query()
                        ->where('status', 'published')
                        ->where(function ($query) {
                            $query->whereNull('meta_description')
                                ->orWhere('meta_description', '');
                        })
                        ->count()
                    + (int) Category::query()
                        ->active()
                        ->where(function ($query) {
                            $query->whereNull('meta_description')
                                ->orWhere('meta_description', '');
                        })
                        ->count();

                $canonicalMissingCount = (int) BlogPost::query()
                    ->whereIn('status', [PostStatus::PUBLISHED->value, PostStatus::SCHEDULED->value])
                    ->where(function ($query) {
                        $query->whereNull('canonical_url')
                            ->orWhere('canonical_url', '');
                    })
                    ->count();

                $publishedNoindexCount = (int) BlogPost::query()
                    ->where('status', PostStatus::PUBLISHED)
                    ->where('noindex', true)
                    ->count();

                $scheduledItems = BlogPost::query()
                    ->with('author:id,name')
                    ->scheduled()
                    ->orderBy('scheduled_at')
                    ->limit(5)
                    ->get()
                    ->map(function (BlogPost $post) {
                        return [
                            'title' => $post->title,
                            'author' => $post->author?->name ?? 'Unknown author',
                            'scheduled_for' => $post->scheduled_at?->format('M j, Y H:i') ?? 'Unscheduled',
                        ];
                    });

                $nextScheduledPost = BlogPost::query()
                    ->scheduled()
                    ->orderBy('scheduled_at')
                    ->first();

                $contentWorkspace['drafts'] = [
                    'draft_count' => (int) BlogPost::query()->draft()->count(),
                    'scheduled_count' => (int) BlogPost::query()->scheduled()->count(),
                    'items' => $draftItems->all(),
                    'href' => route('admin.cms.posts.index'),
                ];

                $contentWorkspace['missing_metadata'] = [
                    'total_items' => $missingMetadataCollections->count(),
                    'items' => $missingMetadataCollections->all(),
                    'href' => route('admin.cms.posts.index'),
                ];

                $contentWorkspace['seo_issue_summary'] = [
                    'missing_meta_title' => $missingMetaTitleCount,
                    'missing_meta_description' => $missingMetaDescriptionCount,
                    'missing_canonical' => $canonicalMissingCount,
                    'published_noindex' => $publishedNoindexCount,
                    'href' => route('admin.settings.index', ['tab' => 'seo']),
                ];

                $contentWorkspace['scheduled_content'] = [
                    'scheduled_count' => (int) BlogPost::query()->scheduled()->count(),
                    'publishes_today' => (int) BlogPost::query()
                        ->scheduled()
                        ->whereBetween('scheduled_at', [now()->startOfDay(), now()->endOfDay()])
                        ->count(),
                    'next_publish_at' => $nextScheduledPost?->scheduled_at?->format('M j, Y H:i') ?? 'No scheduled publish',
                    'items' => $scheduledItems->all(),
                    'href' => route('admin.cms.posts.index', ['status' => PostStatus::SCHEDULED->value]),
                ];
            } catch (\Throwable) {
                $contentWorkspace['drafts'] = [
                    'draft_count' => 0,
                    'scheduled_count' => 0,
                    'items' => [],
                    'href' => route('admin.cms.posts.index'),
                ];

                $contentWorkspace['missing_metadata'] = [
                    'total_items' => 0,
                    'items' => [],
                    'href' => route('admin.cms.posts.index'),
                ];

                $contentWorkspace['seo_issue_summary'] = [
                    'missing_meta_title' => 0,
                    'missing_meta_description' => 0,
                    'missing_canonical' => 0,
                    'published_noindex' => 0,
                    'href' => route('admin.settings.index', ['tab' => 'seo']),
                ];

                $contentWorkspace['scheduled_content'] = [
                    'scheduled_count' => 0,
                    'publishes_today' => 0,
                    'next_publish_at' => 'No scheduled publish',
                    'items' => [],
                    'href' => route('admin.cms.posts.index', ['status' => PostStatus::SCHEDULED->value]),
                ];
            }
        }

        $mediaWorkspace = [];

        if ($isMarketingDashboard) {
            try {
                $seoSettings = app(SettingsService::class)->group('seo');

                $integrationItems = collect([
                    [
                        'name' => 'Google Analytics',
                        'value' => $seoSettings['google_analytics'] ?? $seoSettings['google_analytics_id'] ?? null,
                    ],
                    [
                        'name' => 'Facebook Pixel',
                        'value' => $seoSettings['facebook_pixel'] ?? $seoSettings['facebook_pixel_id'] ?? null,
                    ],
                    [
                        'name' => 'TikTok Pixel',
                        'value' => $seoSettings['tiktok_pixel'] ?? null,
                    ],
                ])->map(function (array $integration) {
                    $configured = filled($integration['value']);

                    return [
                        'name' => $integration['name'],
                        'status' => $configured ? 'Configured' : 'Missing',
                        'tone' => $configured ? 'success' : 'warning',
                    ];
                })->all();

                $configuredCount = collect($integrationItems)->where('status', 'Configured')->count();

                $mediaWorkspace['integration_health'] = [
                    'configured_count' => $configuredCount,
                    'missing_count' => count($integrationItems) - $configuredCount,
                    'items' => $integrationItems,
                    'href' => route('admin.settings.index', ['tab' => 'seo']),
                ];

                $campaignHooks = CouponUsage::query()
                    ->toBase()
                    ->selectRaw(
                        'COUNT(*) as coupon_redemptions,
                        COALESCE(SUM(discount_amount), 0) as total_discount,
                        COALESCE(AVG(discount_amount), 0) as average_discount'
                    )
                    ->whereBetween('created_at', $orderWindow)
                    ->first();

                $recoverableCarts = AbandonedCart::query()
                    ->recoverable()
                    ->toBase()
                    ->selectRaw('COUNT(*) as recoverable_count, COALESCE(SUM(cart_total), 0) as recoverable_value')
                    ->first();

                $mediaWorkspace['campaign_hooks'] = [
                    'active_coupons' => (int) Coupon::query()->active()->count(),
                    'coupon_redemptions' => (int) ($campaignHooks->coupon_redemptions ?? 0),
                    'discount_value' => $this->formatMoney((float) ($campaignHooks->total_discount ?? 0)),
                    'recoverable_carts' => (int) ($recoverableCarts->recoverable_count ?? 0),
                    'recoverable_value' => $this->formatMoney((float) ($recoverableCarts->recoverable_value ?? 0)),
                    'href' => route('admin.reports.coupons'),
                ];

                $mediaWorkspace['traffic_placeholder'] = [
                    'configured_sources' => $configuredCount,
                    'awaiting_sources' => max(0, count($integrationItems) - $configuredCount),
                    'status' => $configuredCount > 0 ? 'Tracking IDs configured; analytics ingestion pending.' : 'Analytics connectors are not configured yet.',
                    'href' => route('admin.settings.index', ['tab' => 'seo']),
                ];
            } catch (\Throwable) {
                $mediaWorkspace['integration_health'] = [
                    'configured_count' => 0,
                    'missing_count' => 0,
                    'items' => [],
                    'href' => route('admin.settings.index', ['tab' => 'seo']),
                ];

                $mediaWorkspace['campaign_hooks'] = [
                    'active_coupons' => 0,
                    'coupon_redemptions' => 0,
                    'discount_value' => $this->formatMoney(0),
                    'recoverable_carts' => 0,
                    'recoverable_value' => $this->formatMoney(0),
                    'href' => route('admin.reports.coupons'),
                ];

                $mediaWorkspace['traffic_placeholder'] = [
                    'configured_sources' => 0,
                    'awaiting_sources' => 0,
                    'status' => 'Analytics connectors are not configured yet.',
                    'href' => route('admin.settings.index', ['tab' => 'seo']),
                ];
            }
        }

        $salesWorkspace = [];

        if ($isSalesDashboard) {
            $currentAov = $currentOrders > 0 ? round($currentRevenue / $currentOrders, 2) : 0.0;
            $previousAov = $previousOrders > 0 ? round($previousRevenue / $previousOrders, 2) : 0.0;

            $salesWorkspace['revenue_trend'] = [
                'total_revenue' => $this->formatMoney($currentRevenue),
                'total_orders' => $currentOrders,
                'chart' => [
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
                    ],
                ],
                'href' => route('admin.reports.sales'),
            ];

            $salesWorkspace['aov'] = [
                'value' => $this->formatMoney($currentAov),
                'change' => $this->changeLabel($currentAov, $previousAov, $comparisonLabel),
                'orders' => $currentOrders,
                'href' => route('admin.reports.sales'),
            ];

            $salesWorkspace['best_sellers'] = [
                'items' => $topProducts->take(5)->values()->all(),
                'href' => route('admin.reports.sales'),
            ];

            $couponPerformance = CouponUsage::query()
                ->toBase()
                ->selectRaw(
                    'COUNT(*) as redemptions,
                    COALESCE(SUM(discount_amount), 0) as total_discount,
                    COALESCE(AVG(discount_amount), 0) as average_discount'
                )
                ->whereBetween('created_at', $orderWindow)
                ->first();

            $recoverableSalesCarts = AbandonedCart::query()
                ->recoverable()
                ->toBase()
                ->selectRaw('COUNT(*) as recoverable_count, COALESCE(SUM(cart_total), 0) as recoverable_value')
                ->first();

            $salesWorkspace['promo_performance'] = [
                'active_coupons' => (int) Coupon::query()->active()->count(),
                'redemptions' => (int) ($couponPerformance->redemptions ?? 0),
                'discount_value' => $this->formatMoney((float) ($couponPerformance->total_discount ?? 0)),
                'recoverable_carts' => (int) ($recoverableSalesCarts->recoverable_count ?? 0),
                'recoverable_value' => $this->formatMoney((float) ($recoverableSalesCarts->recoverable_value ?? 0)),
                'href' => route('admin.reports.coupons'),
            ];
        }

        $stockWorkspace = [];

        if ($isStockDashboard) {
            $damagedStockSummary = DB::table('inventory_movements')
                ->join('inventory_stock_items', 'inventory_stock_items.id', '=', 'inventory_movements.stock_item_id')
                ->leftJoin('products', 'products.id', '=', 'inventory_stock_items.product_id')
                ->leftJoin('product_variants', 'product_variants.id', '=', 'inventory_stock_items.product_variant_id')
                ->whereBetween('inventory_movements.created_at', $orderWindow)
                ->where('inventory_movements.reason', 'damage')
                ->where('inventory_movements.type', 'deduction')
                ->selectRaw(
                    'COUNT(*) as damaged_events,
                    COALESCE(SUM(ABS(inventory_movements.quantity)), 0) as damaged_units'
                )
                ->first();

            $damagedItems = DB::table('inventory_movements')
                ->join('inventory_stock_items', 'inventory_stock_items.id', '=', 'inventory_movements.stock_item_id')
                ->leftJoin('products', 'products.id', '=', 'inventory_stock_items.product_id')
                ->leftJoin('product_variants', 'product_variants.id', '=', 'inventory_stock_items.product_variant_id')
                ->whereBetween('inventory_movements.created_at', $orderWindow)
                ->where('inventory_movements.reason', 'damage')
                ->where('inventory_movements.type', 'deduction')
                ->latest('inventory_movements.created_at')
                ->limit(5)
                ->get([
                    'products.name as product_name',
                    'products.sku as product_sku',
                    'product_variants.sku as variant_sku',
                    'inventory_movements.quantity',
                    'inventory_movements.created_at',
                ])
                ->map(function ($row) {
                    return [
                        'name' => $row->product_name ?? 'Damaged item',
                        'sku' => $row->variant_sku ?: ($row->product_sku ?? 'NO-SKU'),
                        'units' => abs((int) $row->quantity),
                        'date' => Carbon::parse($row->created_at)->diffForHumans(),
                    ];
                });

            $stockWorkspace['low_stock'] = [
                'count' => $stockAlerts,
                'items' => $lowStock->take(5)->values()->all(),
                'href' => route('admin.inventory.index', ['filter' => 'low_stock']),
            ];

            $stockWorkspace['damaged_stock'] = [
                'damaged_events' => (int) ($damagedStockSummary->damaged_events ?? 0),
                'damaged_units' => (int) ($damagedStockSummary->damaged_units ?? 0),
                'items' => $damagedItems->values()->all(),
                'href' => route('admin.inventory.reports.adjustments'),
            ];

            $adjustmentSummary = DB::table('inventory_movements')
                ->whereBetween('created_at', $orderWindow)
                ->where('reason', 'manual_adjustment')
                ->selectRaw(
                    'COUNT(*) as adjustment_events,
                    COALESCE(SUM(ABS(quantity)), 0) as adjusted_units'
                )
                ->first();

            $adjustmentItems = DB::table('inventory_movements')
                ->join('inventory_stock_items', 'inventory_stock_items.id', '=', 'inventory_movements.stock_item_id')
                ->leftJoin('products', 'products.id', '=', 'inventory_stock_items.product_id')
                ->leftJoin('product_variants', 'product_variants.id', '=', 'inventory_stock_items.product_variant_id')
                ->whereBetween('inventory_movements.created_at', $orderWindow)
                ->where('inventory_movements.reason', 'manual_adjustment')
                ->latest('inventory_movements.created_at')
                ->limit(5)
                ->get([
                    'products.name as product_name',
                    'products.sku as product_sku',
                    'product_variants.sku as variant_sku',
                    'inventory_movements.quantity',
                    'inventory_movements.type',
                    'inventory_movements.created_at',
                ])
                ->map(function ($row) {
                    return [
                        'name' => $row->product_name ?? 'Adjusted item',
                        'sku' => $row->variant_sku ?: ($row->product_sku ?? 'NO-SKU'),
                        'units' => abs((int) $row->quantity),
                        'direction' => $row->type === 'addition' ? 'Increase' : 'Decrease',
                        'date' => Carbon::parse($row->created_at)->diffForHumans(),
                    ];
                });

            $transferSummary = DB::table('stock_transfers')
                ->whereBetween('created_at', $orderWindow)
                ->selectRaw(
                    'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as shipped_count,
                    COALESCE(SUM(CASE WHEN status IN (?, ?) THEN quantity ELSE 0 END), 0) as in_flight_units',
                    ['pending', 'shipped', 'pending', 'shipped']
                )
                ->first();

            $transferItems = DB::table('stock_transfers')
                ->join('inventory_stock_items as source_items', 'source_items.id', '=', 'stock_transfers.source_stock_item_id')
                ->leftJoin('products', 'products.id', '=', 'source_items.product_id')
                ->leftJoin('product_variants', 'product_variants.id', '=', 'source_items.product_variant_id')
                ->whereIn('stock_transfers.status', ['pending', 'shipped'])
                ->latest('stock_transfers.created_at')
                ->limit(5)
                ->get([
                    'products.name as product_name',
                    'products.sku as product_sku',
                    'product_variants.sku as variant_sku',
                    'stock_transfers.quantity',
                    'stock_transfers.status',
                    'stock_transfers.created_at',
                ])
                ->map(function ($row) {
                    return [
                        'name' => $row->product_name ?? 'Transfer item',
                        'sku' => $row->variant_sku ?: ($row->product_sku ?? 'NO-SKU'),
                        'units' => (int) $row->quantity,
                        'status' => Str::headline((string) $row->status),
                        'date' => Carbon::parse($row->created_at)->diffForHumans(),
                    ];
                });

            $stockWorkspace['adjustment_summary'] = [
                'adjustment_events' => (int) ($adjustmentSummary->adjustment_events ?? 0),
                'adjusted_units' => (int) ($adjustmentSummary->adjusted_units ?? 0),
                'items' => $adjustmentItems->values()->all(),
                'href' => route('admin.inventory.reports.adjustments'),
            ];

            $stockWorkspace['transfer_ready_metrics'] = [
                'pending_transfers' => (int) ($transferSummary->pending_count ?? 0),
                'shipped_transfers' => (int) ($transferSummary->shipped_count ?? 0),
                'in_flight_units' => (int) ($transferSummary->in_flight_units ?? 0),
                'items' => $transferItems->values()->all(),
                'href' => route('admin.inventory.index'),
            ];
        }

        $moderatorWorkspace = [];

        if ($isModeratorDashboard) {
            $queueCounts = ModerationQueueItem::query()
                ->toBase()
                ->selectRaw(
                    'SUM(CASE WHEN status IN (?, ?, ?) THEN 1 ELSE 0 END) as open_count,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as assigned_count,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as review_count',
                    [
                        ModerationQueueStatus::OPEN->value,
                        ModerationQueueStatus::ASSIGNED->value,
                        ModerationQueueStatus::IN_REVIEW->value,
                        ModerationQueueStatus::ASSIGNED->value,
                        ModerationQueueStatus::IN_REVIEW->value,
                    ]
                )
                ->first();

            $reportCounts = CommunityReport::query()
                ->toBase()
                ->selectRaw(
                    'SUM(CASE WHEN state IN (?, ?, ?) THEN 1 ELSE 0 END) as open_reports,
                    SUM(CASE WHEN state = ? THEN 1 ELSE 0 END) as action_taken,
                    SUM(CASE WHEN state = ? THEN 1 ELSE 0 END) as dismissed_reports',
                    [
                        CommunityReportState::SUBMITTED->value,
                        CommunityReportState::QUEUED->value,
                        CommunityReportState::IN_REVIEW->value,
                        CommunityReportState::ACTION_TAKEN->value,
                        CommunityReportState::DISMISSED->value,
                    ]
                )
                ->first();

            $queueItems = ModerationQueueItem::query()
                ->open()
                ->latest('queued_at')
                ->limit(5)
                ->get()
                ->map(function (ModerationQueueItem $item) {
                    return [
                        'status' => $item->status->label(),
                        'priority' => (int) $item->priority,
                        'queued_at' => $item->queued_at?->diffForHumans() ?? 'Queued',
                    ];
                });

            $moderatorWorkspace['report_queue'] = [
                'open_queue' => (int) ($queueCounts->open_count ?? 0),
                'assigned_queue' => (int) ($queueCounts->assigned_count ?? 0),
                'in_review_queue' => (int) ($queueCounts->review_count ?? 0),
                'open_reports' => (int) ($reportCounts->open_reports ?? 0),
                'action_taken' => (int) ($reportCounts->action_taken ?? 0),
                'dismissed_reports' => (int) ($reportCounts->dismissed_reports ?? 0),
                'items' => $queueItems->values()->all(),
                'href' => route('admin.dashboard'),
            ];
        }

        return [
            'is_super_admin' => $isSuperAdmin,
            'active_preset' => $activePreset,
            'date_presets' => collect(self::PRESETS)->map(fn (int $days, string $key) => [
                'key' => $key,
                'label' => Str::upper($key),
                'days' => $days,
            ])->values()->all(),
            'range_label' => "Last {$windowDays} days",
            'window_days' => $windowDays,
            'primary_metrics' => $primaryMetrics,
            'super_admin_widgets' => $superAdminWidgets,
            'super_admin_health' => [
                'low_stock_alerts' => [
                    'count' => $stockAlerts,
                    'items' => $lowStock->take(5)->values()->all(),
                    'href' => route('admin.inventory.index', ['filter' => 'low_stock']),
                ],
                'shipping_exceptions' => [
                    'count' => (int) Shipment::query()
                        ->where(function ($builder) {
                            $builder
                                ->where('has_delivery_issue', true)
                                ->orWhereIn('status', [ShipmentStatus::FAILED_DELIVERY, ShipmentStatus::RETURNED]);
                        })
                        ->count(),
                    'items' => $shippingExceptionItems->values()->all(),
                    'href' => route('admin.shipping.shipments.index', ['issues' => 1]),
                ],
                'franchise_performance' => [
                    'items' => $franchisePerformance->all(),
                    'href' => route('admin.inventory.branches.index'),
                ],
                'branch_performance' => [
                    'items' => $branchPerformance->all(),
                    'href' => route('admin.inventory.branches.index'),
                ],
            ],
            'super_admin_command' => [
                'support_kpi' => [
                    'open' => $supportActiveCount,
                    'urgent' => $supportUrgentCount,
                    'unassigned' => $supportUnassignedCount,
                    'items' => $supportPriorityItems->values()->all(),
                    'href' => route('admin.support.issues.index'),
                ],
                'finance_summary' => [
                    'captured' => $this->formatMoney($currentRevenue),
                    'payment_review_count' => (int) ($pendingPaymentReview->aggregate ?? 0),
                    'payment_review_amount' => $this->formatMoney((float) ($pendingPaymentReview->total_amount ?? 0)),
                    'refund_queue_count' => (int) ($refundQueue->aggregate ?? 0),
                    'refund_queue_amount' => $this->formatMoney((float) ($refundQueue->total_amount ?? 0)),
                    'href' => route('admin.finance.reports.index'),
                ],
                'system_alerts' => [
                    'critical' => (int) ($systemAlertCounts->critical_count ?? 0),
                    'warning' => (int) ($systemAlertCounts->warning_count ?? 0),
                    'items' => $systemAlertItems->values()->all(),
                    'href' => route('admin.reports.observability'),
                ],
            ],
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
            'branch_workspace' => $branchWorkspace,
            'franchise_workspace' => $franchiseWorkspace,
            'platform_workspace' => $platformWorkspace,
            'support_workspace' => $supportWorkspace,
            'shipping_workspace' => $shippingWorkspace,
            'finance_workspace' => $financeWorkspace,
            'content_workspace' => $contentWorkspace,
            'media_workspace' => $mediaWorkspace,
            'sales_workspace' => $salesWorkspace,
            'stock_workspace' => $stockWorkspace,
            'moderator_workspace' => $moderatorWorkspace,
            'platform_action_queue' => $platformActionQueue->values()->all(),
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

    protected function changeLabel(float|int $current, float|int $previous, string $comparisonLabel): string
    {
        if ((float) $previous === 0.0) {
            return $current > 0 ? 'New activity' : 'No change';
        }

        $delta = (($current - $previous) / abs($previous)) * 100;

        if (abs($delta) < 0.1) {
            return "Flat vs {$comparisonLabel}";
        }

        return sprintf('%s%.1f%% vs %s', $delta > 0 ? '+' : '', $delta, $comparisonLabel);
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

    protected function mapIssueStatusTone(IssueStatus $status): string
    {
        return match ($status) {
            IssueStatus::OPEN => 'danger',
            IssueStatus::IN_PROGRESS => 'warning',
            IssueStatus::WAITING_CUSTOMER => 'info',
            IssueStatus::RESOLVED => 'success',
            IssueStatus::CLOSED => 'neutral',
        };
    }

    protected function mapIssuePriorityTone(IssuePriority $priority): string
    {
        return match ($priority) {
            IssuePriority::URGENT => 'danger',
            IssuePriority::HIGH => 'warning',
            IssuePriority::MEDIUM => 'info',
            IssuePriority::LOW => 'neutral',
        };
    }

    protected function mapRefundTone(RefundStatus $status): string
    {
        return match ($status) {
            RefundStatus::REQUESTED => 'warning',
            RefundStatus::APPROVED, RefundStatus::PROCESSING => 'info',
            RefundStatus::COMPLETED => 'success',
            RefundStatus::REJECTED => 'danger',
        };
    }

    protected function mapShipmentTone(ShipmentStatus $status): string
    {
        return match ($status) {
            ShipmentStatus::READY_TO_SHIP, ShipmentStatus::PACKED => 'warning',
            ShipmentStatus::DISPATCHED, ShipmentStatus::IN_TRANSIT => 'info',
            ShipmentStatus::DELIVERED => 'success',
            ShipmentStatus::FAILED_DELIVERY, ShipmentStatus::RETURNED => 'danger',
            ShipmentStatus::CANCELLED => 'neutral',
            default => 'neutral',
        };
    }

    protected function normalizePreset(?string $preset): string
    {
        return array_key_exists($preset, self::PRESETS) ? $preset : '30d';
    }
}
