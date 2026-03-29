<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Services\OperationalScopeResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly OperationalScopeResolver $scopeResolver,
    ) {}

    /**
     * @return array<string, string>
     */
    public static function adjustmentReasons(): array
    {
        return [
            'manual_adjustment' => 'Manual inventory count',
            'restock' => 'Restock / received',
            'damage' => 'Damage / breakage',
            'shrinkage' => 'Shrinkage / loss',
            'return' => 'Customer return',
            'transfer_in' => 'Transfer in',
            'transfer_out' => 'Transfer out',
        ];
    }

    public function index(): View
    {
        $this->authorize('viewAny', StockItem::class);

        $actor = request()->user();
        $query = StockItem::with(['product', 'variant', 'branch'])
            ->orderBy('id', 'desc');

        $this->applyInventoryVisibilityScope($query, $actor);

        if (request()->filled('search')) {
            $search = trim((string) request('search'));

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

        if (request('filter') === 'low_stock') {
            $query->whereIn('status', ['low_stock', 'out_of_stock']);
        }

        if (request()->filled('branch_id')) {
            $query->where('branch_id', request('branch_id'));
        }

        $items = $query->paginate(50)->withQueryString();
        $branches = $this->visibleBranchesFor($actor);
        $summary = $this->inventorySummaryFor($actor);

        return view('admin.inventory.index', compact('items', 'branches', 'summary'));
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
            ->orderByDesc('updated_at');

        $this->applyInventoryVisibilityScope($query, $request->user());

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
            $query->where('branch_id', $request->integer('branch_id'));
        }

        $items = $query->paginate(50)->withQueryString();
        $branches = $this->visibleBranchesFor($request->user());

        return view('admin.inventory.low-stock', compact('items', 'branches'));
    }

    public function createAdjustment(Request $request): View
    {
        abort_unless($request->user()?->can('inventory.adjust'), 403);

        $stockItems = StockItem::query()
            ->with(['product', 'variant', 'branch'])
            ->orderByDesc('id');

        $this->applyInventoryVisibilityScope($stockItems, $request->user());

        $stockItems = $stockItems->get();

        $selectedStockItem = null;

        if ($request->filled('stock_item_id')) {
            $selectedStockItem = $stockItems->firstWhere('id', (int) $request->integer('stock_item_id'));
        }

        $adjustmentReasons = self::adjustmentReasons();

        return view('admin.inventory.adjust', compact(
            'stockItems',
            'selectedStockItem',
            'adjustmentReasons',
        ));
    }

    public function createDamageReport(Request $request): View
    {
        abort_unless($request->user()?->can('inventory.adjust'), 403);

        $stockItems = StockItem::query()
            ->with(['product', 'variant', 'branch'])
            ->orderByDesc('id');

        $this->applyInventoryVisibilityScope($stockItems, $request->user());

        $stockItems = $stockItems->get();

        $selectedStockItem = null;

        if ($request->filled('stock_item_id')) {
            $selectedStockItem = $stockItems->firstWhere('id', (int) $request->integer('stock_item_id'));
        }

        return view('admin.inventory.damage', compact('stockItems', 'selectedStockItem'));
    }

    public function storeAdjustment(Request $request, InventoryService $inventoryService): RedirectResponse
    {
        abort_unless($request->user()?->can('inventory.adjust'), 403);

        $adjustmentReasons = self::adjustmentReasons();

        $validated = $request->validate([
            'stock_item_id' => ['required', 'integer', 'exists:inventory_stock_items,id'],
            'adjustment_type' => ['required', 'in:add,subtract'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'in:'.implode(',', array_keys($adjustmentReasons))],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $stockItem = StockItem::query()
            ->with(['product', 'variant', 'branch'])
            ->findOrFail($validated['stock_item_id']);

        $this->authorize('update', $stockItem);

        $quantityChange = (int) $validated['quantity'];

        if ($validated['adjustment_type'] === 'subtract') {
            $quantityChange *= -1;
        }

        $inventoryService->adjustStock(
            $stockItem,
            $quantityChange,
            $validated['reason'],
            $request->user()?->id,
            $validated['notes'] ?: null,
        );

        return redirect()
            ->route('admin.inventory.adjustments.create', ['stock_item_id' => $stockItem->id])
            ->with('success', 'Inventory adjustment recorded.');
    }

    public function storeDamageReport(Request $request, InventoryService $inventoryService): RedirectResponse
    {
        abort_unless($request->user()?->can('inventory.adjust'), 403);

        $validated = $request->validate([
            'stock_item_id' => ['required', 'integer', 'exists:inventory_stock_items,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $stockItem = StockItem::query()
            ->with(['product', 'variant', 'branch'])
            ->findOrFail($validated['stock_item_id']);

        $this->authorize('update', $stockItem);

        $inventoryService->adjustStock(
            $stockItem,
            -1 * (int) $validated['quantity'],
            'damage',
            $request->user()?->id,
            $validated['notes'] ?: null,
        );

        $stockItem->refresh()->loadMissing(['product', 'variant', 'branch']);

        $this->audit->log(
            action: 'inventory.damage_reported',
            target: $stockItem,
            oldValues: [],
            newValues: [
                'reported_damage_units' => (int) $validated['quantity'],
                'reason' => 'damage',
            ],
            notes: $validated['notes'] ?: null,
            context: [
                'module' => 'inventory',
                'source' => 'inventory_controller',
                'inventory_workflow' => 'damage_report',
            ],
            targetLabel: $stockItem->branch?->name
                ? sprintf(
                    'Damaged stock: %s @ %s',
                    $stockItem->variant?->sku ?? $stockItem->product?->name ?? ('Stock Item #'.$stockItem->id),
                    $stockItem->branch->name,
                )
                : sprintf(
                    'Damaged stock: %s',
                    $stockItem->variant?->sku ?? $stockItem->product?->name ?? ('Stock Item #'.$stockItem->id),
                ),
        );

        return redirect()
            ->route('admin.inventory.damage.create', ['stock_item_id' => $stockItem->id])
            ->with('success', 'Damaged stock recorded.');
    }

    public function damagedStock(Request $request): View
    {
        $this->authorize('viewAny', StockItem::class);

        $query = StockMovement::query()
            ->with(['stockItem.product', 'stockItem.variant', 'stockItem.branch', 'user'])
            ->where('reason', 'damage')
            ->latest('created_at');

        if (! $request->user()?->isSuperAdmin() && ! $request->user()?->hasOrganizationScope('platform')) {
            $branchIds = $this->scopeResolver->branchIdsFor($request->user());

            $query->whereHas('stockItem', function ($stockQuery) use ($branchIds) {
                $stockQuery->whereIn('branch_id', $branchIds === [] ? [-1] : $branchIds);
            });
        }

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

        $movements = $query->paginate(50)->withQueryString();

        return view('admin.inventory.damaged-stock', compact('movements'));
    }

    public function updateThreshold(Request $request, StockItem $stockItem): RedirectResponse
    {
        $this->authorize('update', $stockItem);

        $validated = $request->validate([
            'low_stock_threshold' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        $previousThreshold = (int) $stockItem->low_stock_threshold;
        $stockItem->low_stock_threshold = (int) $validated['low_stock_threshold'];
        $stockItem->save();

        $stockItem->loadMissing(['product', 'variant', 'branch']);

        $this->audit->log(
            action: 'inventory.low_stock_threshold_updated',
            target: $stockItem,
            oldValues: ['low_stock_threshold' => $previousThreshold],
            newValues: ['low_stock_threshold' => (int) $stockItem->low_stock_threshold],
            context: [
                'module' => 'inventory',
                'source' => 'inventory_controller',
                'inventory_workflow' => 'low_stock_threshold_update',
            ],
            targetLabel: $stockItem->branch?->name
                ? sprintf(
                    'Stock threshold: %s @ %s',
                    $stockItem->variant?->sku ?? $stockItem->product?->name ?? ('Stock Item #'.$stockItem->id),
                    $stockItem->branch->name,
                )
                : sprintf(
                    'Stock threshold: %s',
                    $stockItem->variant?->sku ?? $stockItem->product?->name ?? ('Stock Item #'.$stockItem->id),
                ),
        );

        return redirect()
            ->back()
            ->with('success', 'Low-stock threshold updated.');
    }

    private function applyInventoryVisibilityScope(Builder $query, $actor): void
    {
        if (! $actor || $actor->isSuperAdmin() || $actor->hasOrganizationScope('platform')) {
            return;
        }

        $branchIds = $this->scopeResolver->branchIdsFor($actor);
        $query->whereIn('branch_id', $branchIds === [] ? [-1] : $branchIds);
    }

    private function visibleBranchesFor($actor)
    {
        $query = Organization::branch()->orderBy('name');

        if ($actor && ! $actor->isSuperAdmin() && ! $actor->hasOrganizationScope('platform')) {
            $branchIds = $this->scopeResolver->branchIdsFor($actor);
            $query->whereIn('id', $branchIds === [] ? [-1] : $branchIds);
        }

        return $query->get();
    }

    private function inventorySummaryFor($actor): array
    {
        $baseQuery = StockItem::query();
        $this->applyInventoryVisibilityScope($baseQuery, $actor);

        return [
            'visible_items' => (clone $baseQuery)->count(),
            'visible_branches' => $this->visibleBranchesFor($actor)->count(),
            'reserved_units' => (int) ((clone $baseQuery)->sum('reserved_quantity') ?? 0),
            'global_items' => ($actor && ! $actor->isSuperAdmin() && ! $actor->hasOrganizationScope('platform'))
                ? 0
                : (clone $baseQuery)->whereNull('branch_id')->count(),
        ];
    }
}
