<?php

namespace App\Modules\Reports\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\StockItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryReportController extends Controller
{
    public function index(Request $request)
    {
        $threshold = max(1, (int) $request->input('threshold', 10));
        $availableQuantitySql = 'CASE WHEN inventory_stock_items.quantity - inventory_stock_items.reserved_quantity > 0 THEN inventory_stock_items.quantity - inventory_stock_items.reserved_quantity ELSE 0 END';

        $inventorySummary = DB::table('inventory_stock_items')
            ->leftJoin('product_variants', 'inventory_stock_items.product_variant_id', '=', 'product_variants.id')
            ->leftJoin('products', 'inventory_stock_items.product_id', '=', 'products.id')
            ->selectRaw(
                'SUM(CASE WHEN '.$availableQuantitySql.' > 0 THEN 1 ELSE 0 END) as in_stock,
                SUM(CASE WHEN '.$availableQuantitySql.' <= 0 THEN 1 ELSE 0 END) as out_of_stock,
                SUM(CASE WHEN '.$availableQuantitySql.' > 0 AND '.$availableQuantitySql.' <= ? THEN 1 ELSE 0 END) as low_stock,
                COALESCE(SUM('.$availableQuantitySql.'), 0) as total_units,
                COALESCE(SUM('.$availableQuantitySql.' * COALESCE(product_variants.price, products.price, 0)), 0) as total_value',
                [$threshold]
            )
            ->first();

        $stockSummary = [
            'total_products' => Product::count(),
            'total_variants' => ProductVariant::count(),
            'in_stock' => (int) ($inventorySummary->in_stock ?? 0),
            'out_of_stock' => (int) ($inventorySummary->out_of_stock ?? 0),
            'low_stock' => (int) ($inventorySummary->low_stock ?? 0),
            'total_units' => (int) ($inventorySummary->total_units ?? 0),
            'total_value' => (float) ($inventorySummary->total_value ?? 0),
        ];

        $lowStock = StockItem::with(['product', 'variant', 'branch'])
            ->select('inventory_stock_items.*')
            ->selectRaw($availableQuantitySql.' as available_quantity')
            ->whereRaw($availableQuantitySql.' > 0')
            ->whereRaw($availableQuantitySql.' <= ?', [$threshold])
            ->orderByRaw($availableQuantitySql.' asc')
            ->limit(50)
            ->get();

        $outOfStock = StockItem::with(['product', 'variant', 'branch'])
            ->select('inventory_stock_items.*')
            ->selectRaw($availableQuantitySql.' as available_quantity')
            ->whereRaw($availableQuantitySql.' <= 0')
            ->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get();

        $stockByCategory = DB::table('inventory_stock_items')
            ->join('products', 'inventory_stock_items.product_id', '=', 'products.id')
            ->leftJoin('category_product', 'products.id', '=', 'category_product.product_id')
            ->leftJoin('categories', 'category_product.category_id', '=', 'categories.id')
            ->selectRaw('COALESCE(categories.name, "Uncategorized") as category, COALESCE(SUM('.$availableQuantitySql.'), 0) as total_stock, COUNT(DISTINCT products.id) as product_count')
            ->groupBy('categories.name')
            ->orderByDesc('total_stock')
            ->get();

        return view('admin.reports.inventory', compact('stockSummary', 'lowStock', 'outOfStock', 'stockByCategory', 'threshold'));
    }
}
