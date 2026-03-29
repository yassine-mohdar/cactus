<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Services\OperationalScopeResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryReportsController extends Controller
{
    public function __construct(
        private readonly OperationalScopeResolver $scopeResolver,
    ) {}

    public function currentStock(Request $request): View
    {
        $this->authorize('viewAny', StockItem::class);

        $query = StockItem::query()
            ->with(['product', 'variant', 'branch'])
            ->orderByDesc('quantity')
            ->orderByDesc('id');

        $this->applyStockVisibilityScope($query, $request);
        $this->applyStockFilters($query, $request);

        $items = $query->paginate(50)->withQueryString();
        $branches = $this->visibleBranchesFor($request);
        $summary = $this->stockSummary(clone $query);

        return view('admin.inventory.reports.current_stock', compact('items', 'branches', 'summary'));
    }

    public function adjustments(Request $request): View
    {
        $this->authorize('viewAny', StockItem::class);

        $query = StockMovement::query()
            ->with(['stockItem.product', 'stockItem.variant', 'stockItem.branch', 'user'])
            ->latest();

        $this->applyMovementVisibilityScope($query, $request);
        $this->applyMovementFilters($query, $request);

        $movements = $query->paginate(50)->withQueryString();
        $branches = $this->visibleBranchesFor($request);
        $summary = $this->movementSummary(clone $query);

        return view('admin.inventory.reports.adjustments', compact('movements', 'branches', 'summary'));
    }

    public function lowStock(Request $request): View
    {
        $this->authorize('viewAny', StockItem::class);

        $availableQuantitySql = 'CASE WHEN inventory_stock_items.quantity - inventory_stock_items.reserved_quantity > 0 THEN inventory_stock_items.quantity - inventory_stock_items.reserved_quantity ELSE 0 END';

        $query = StockItem::query()
            ->with(['product', 'variant', 'branch'])
            ->select('inventory_stock_items.*')
            ->selectRaw($availableQuantitySql.' as available_quantity')
            ->whereRaw($availableQuantitySql.' <= COALESCE(NULLIF(low_stock_threshold, 0), 5)')
            ->orderByRaw($availableQuantitySql.' asc')
            ->orderByDesc('reserved_quantity');

        $this->applyStockVisibilityScope($query, $request);
        $this->applyStockFilters($query, $request);

        $items = $query->paginate(50)->withQueryString();
        $branches = $this->visibleBranchesFor($request);
        $summary = $this->lowStockSummary(clone $query);

        return view('admin.inventory.reports.low_stock', compact('items', 'branches', 'summary'));
    }

    public function damagedStock(Request $request): View
    {
        $this->authorize('viewAny', StockItem::class);

        $query = StockMovement::query()
            ->with(['stockItem.product', 'stockItem.variant', 'stockItem.branch', 'user'])
            ->where('reason', 'damage')
            ->latest();

        $this->applyMovementVisibilityScope($query, $request);
        $this->applyMovementFilters($query, $request);

        $movements = $query->paginate(50)->withQueryString();
        $branches = $this->visibleBranchesFor($request);
        $summary = [
            'rows' => $movements->total(),
            'units' => (int) $movements->getCollection()->sum(fn (StockMovement $movement) => abs((int) $movement->quantity)),
            'branch_rows' => (int) $movements->getCollection()->filter(fn (StockMovement $movement) => $movement->stockItem?->branch_id !== null)->count(),
            'operator_rows' => (int) $movements->getCollection()->filter(fn (StockMovement $movement) => $movement->user_id !== null)->count(),
        ];

        return view('admin.inventory.reports.damaged_stock', compact('movements', 'branches', 'summary'));
    }

    private function applyStockFilters(Builder $query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = trim((string) $request->string('search'));

            $query->where(function ($builder) use ($search) {
                $builder
                    ->whereHas('variant', function ($variantQuery) use ($search) {
                        $variantQuery->where('sku', 'like', "%{$search}%");
                    })
                    ->orWhereHas('product', function ($productQuery) use ($search) {
                        $productQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('branch_id')) {
            $branchId = (int) $request->integer('branch_id');

            if ($branchId > 0) {
                $query->where('branch_id', $branchId);
            } elseif ((string) $request->input('branch_id') === 'global') {
                $query->whereNull('branch_id');
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
    }

    private function applyMovementFilters(Builder $query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = trim((string) $request->string('search'));

            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('notes', 'like', "%{$search}%")
                    ->orWhereHas('stockItem.product', function ($productQuery) use ($search) {
                        $productQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    })
                    ->orWhereHas('stockItem.variant', function ($variantQuery) use ($search) {
                        $variantQuery->where('sku', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('reason')) {
            $query->where('reason', $request->string('reason'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('branch_id')) {
            $branchId = $request->input('branch_id');

            if ((string) $branchId === 'global') {
                $query->whereHas('stockItem', fn (Builder $stockQuery) => $stockQuery->whereNull('branch_id'));
            } elseif ((int) $branchId > 0) {
                $query->whereHas('stockItem', fn (Builder $stockQuery) => $stockQuery->where('branch_id', (int) $branchId));
            }
        }
    }

    private function applyStockVisibilityScope(Builder $query, Request $request): void
    {
        $actor = $request->user();

        if (! $actor || $actor->isSuperAdmin() || $actor->hasOrganizationScope('platform')) {
            return;
        }

        $branchIds = $this->scopeResolver->branchIdsFor($actor);

        $query->whereIn('branch_id', $branchIds === [] ? [-1] : $branchIds);
    }

    private function applyMovementVisibilityScope(Builder $query, Request $request): void
    {
        $actor = $request->user();

        if (! $actor || $actor->isSuperAdmin() || $actor->hasOrganizationScope('platform')) {
            return;
        }

        $branchIds = $this->scopeResolver->branchIdsFor($actor);

        $query->whereHas('stockItem', fn (Builder $stockQuery) => $stockQuery->whereIn('branch_id', $branchIds === [] ? [-1] : $branchIds));
    }

    private function visibleBranchesFor(Request $request)
    {
        $query = Organization::branch()->active()->orderBy('name');
        $actor = $request->user();

        if (! $actor || $actor->isSuperAdmin() || $actor->hasOrganizationScope('platform')) {
            return $query->get();
        }

        $branchIds = $this->scopeResolver->branchIdsFor($actor);

        return $query->whereIn('id', $branchIds === [] ? [-1] : $branchIds)->get();
    }

    private function stockSummary(Builder $query): array
    {
        $items = $query->get(['id', 'quantity', 'reserved_quantity', 'branch_id']);

        return [
            'rows' => $items->count(),
            'physical_units' => (int) $items->sum('quantity'),
            'reserved_units' => (int) $items->sum('reserved_quantity'),
            'branch_rows' => (int) $items->whereNotNull('branch_id')->count(),
        ];
    }

    private function movementSummary(Builder $query): array
    {
        $movements = $query->get(['id', 'reason', 'type']);

        return [
            'rows' => $movements->count(),
            'deductions' => (int) $movements->where('type', 'deduction')->count(),
            'additions' => (int) $movements->where('type', 'addition')->count(),
            'damage_events' => (int) $movements->where('reason', 'damage')->count(),
        ];
    }

    private function lowStockSummary(Builder $query): array
    {
        $items = $query->get(['id', 'reserved_quantity', 'status']);

        return [
            'rows' => $items->count(),
            'out_of_stock' => (int) $items->where('status', StockItem::STATUS_OUT_OF_STOCK)->count(),
            'low_stock' => (int) $items->where('status', StockItem::STATUS_LOW_STOCK)->count(),
            'reserved_units' => (int) $items->sum('reserved_quantity'),
        ];
    }
}
