<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', StockItem::class);

        $query = StockItem::with(['product', 'variant', 'branch'])
            ->orderBy('id', 'desc');

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
        $branches = \App\Modules\Organizations\Models\Organization::where('type', 'branch')->get();

        return view('admin.inventory.index', compact('items', 'branches'));
    }

}
