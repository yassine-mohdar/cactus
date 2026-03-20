<?php

namespace App\Modules\Shipping\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shipping\Enums\ShipmentStatus;
use App\Modules\Shipping\Models\Shipment;
use App\Modules\Shipping\Models\ShippingMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShippingMethodController extends Controller
{
    public function index()
    {
        $methods = ShippingMethod::withCount('shipments')->orderBy('sort_order')->get();

        $stats = [
            'total' => $methods->count(),
            'enabled' => $methods->where('is_enabled', true)->count(),
            'free_shipping' => $methods->whereNotNull('free_shipping_threshold')->count(),
            'active_shipments' => Shipment::whereIn('status', [
                ShipmentStatus::READY_TO_SHIP,
                ShipmentStatus::PACKED,
                ShipmentStatus::DISPATCHED,
                ShipmentStatus::IN_TRANSIT,
            ])->count(),
        ];

        $carrierBreakdown = $methods
            ->groupBy(fn (ShippingMethod $method) => $method->carrier ?: 'Unassigned')
            ->map(fn ($group, $label) => ['label' => $label, 'count' => $group->count()])
            ->sortByDesc('count')
            ->values();

        return view('admin.shipping.methods.index', compact('methods', 'stats', 'carrierBreakdown'));
    }

    public function create()
    {
        return view('admin.shipping.methods.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'carrier' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'base_cost' => 'required|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'estimated_days' => 'nullable|string|max:30',
            'is_enabled' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_enabled'] = $request->has('is_enabled');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        // Ensure slug uniqueness
        $baseSlug = $validated['slug'];
        $counter = 1;
        while (ShippingMethod::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $baseSlug . '-' . $counter++;
        }

        ShippingMethod::create($validated);

        return redirect()->route('admin.shipping.methods.index')
            ->with('success', 'Shipping method created successfully.');
    }

    public function edit(ShippingMethod $method)
    {
        $method->loadCount('shipments');
        return view('admin.shipping.methods.edit', compact('method'));
    }

    public function update(Request $request, ShippingMethod $method)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'carrier' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'base_cost' => 'required|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'estimated_days' => 'nullable|string|max:30',
            'is_enabled' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['is_enabled'] = $request->has('is_enabled');
        $method->update($validated);

        return redirect()->route('admin.shipping.methods.index')
            ->with('success', 'Shipping method updated successfully.');
    }

    public function destroy(ShippingMethod $method)
    {
        if ($method->shipments()->exists()) {
            return back()->with('error', 'Cannot delete a shipping method that has shipments.');
        }

        $method->delete();
        return redirect()->route('admin.shipping.methods.index')
            ->with('success', 'Shipping method deleted.');
    }
}
