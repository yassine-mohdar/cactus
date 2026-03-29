<?php

namespace App\Modules\Organizations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Http\Request;

class AdminBranchController extends Controller
{
    public function index()
    {
        $branches = Organization::branch()->latest()->paginate(20);
        return view('admin.branches.index', compact('branches'));
    }

    public function create()
    {
        $branch = new Organization(['type' => 'branch', 'status' => 'active']);
        return view('admin.branches.create', compact('branch'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        $branch = Organization::create(array_merge($validated, ['type' => 'branch']));

        return redirect()->route('admin.inventory.branches.index')
            ->with('success', 'Branch created successfully.');
    }

    public function show(Organization $branch)
    {
        if ($branch->type !== 'branch') {
            abort(404);
        }

        $branch->loadCount('users')
            ->load([
                'users' => fn ($query) => $query->orderBy('name')->limit(6),
            ]);

        $availabilityExpression = 'GREATEST(quantity - reserved_quantity, 0)';
        $stockBaseQuery = StockItem::query()->where('branch_id', $branch->id);

        $stockSummary = [
            'total_skus' => (clone $stockBaseQuery)->count(),
            'available_units' => (int) ((clone $stockBaseQuery)
                ->selectRaw("COALESCE(SUM({$availabilityExpression}), 0) as aggregate")
                ->value('aggregate') ?? 0),
            'reserved_units' => (int) ((clone $stockBaseQuery)->sum('reserved_quantity') ?? 0),
            'low_stock_items' => (clone $stockBaseQuery)
                ->whereRaw("{$availabilityExpression} <= COALESCE(NULLIF(low_stock_threshold, 0), 5)")
                ->count(),
            'recent_movements' => StockMovement::query()
                ->whereHas('stockItem', fn ($query) => $query->where('branch_id', $branch->id))
                ->where('created_at', '>=', now()->subDays(14))
                ->count(),
        ];

        $inventoryPreview = (clone $stockBaseQuery)
            ->with(['product', 'variant'])
            ->orderByRaw("{$availabilityExpression} asc")
            ->limit(6)
            ->get();

        return view('admin.branches.show', compact('branch', 'stockSummary', 'inventoryPreview'));
    }

    public function edit(Organization $branch)
    {
        if ($branch->type !== 'branch') {
            abort(404);
        }

        return view('admin.branches.edit', compact('branch'));
    }

    public function update(Request $request, Organization $branch)
    {
        if ($branch->type !== 'branch') {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        $branch->update($validated);

        return redirect()->route('admin.inventory.branches.index')
            ->with('success', 'Branch updated successfully.');
    }

    public function destroy(Organization $branch)
    {
        if ($branch->type !== 'branch') {
            abort(404);
        }

        $branch->delete();

        return redirect()->route('admin.inventory.branches.index')
            ->with('success', 'Branch deleted successfully.');
    }
}
