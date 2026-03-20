<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Http\Request;

class InventoryReportsController extends Controller
{
    public function currentStock(Request $request)
    {
        $this->authorize('viewAny', StockItem::class);

        // Simple aggregation for now
        $items = StockItem::with(['product', 'variant', 'branch'])
            ->orderBy('quantity', 'desc')
            ->paginate(50);

        return view('admin.inventory.reports.current_stock', compact('items'));
    }

    public function adjustments(Request $request)
    {
        $this->authorize('viewAny', StockItem::class);

        $query = StockMovement::with(['stockItem.product', 'stockItem.variant', 'user'])
            ->latest();

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
            $query->where('reason', $request->reason);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $movements = $query->paginate(50)->withQueryString();

        return view('admin.inventory.reports.adjustments', compact('movements'));
    }
}
