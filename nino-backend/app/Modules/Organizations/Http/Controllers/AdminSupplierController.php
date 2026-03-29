<?php

namespace App\Modules\Organizations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Http\Request;

class AdminSupplierController extends Controller
{
    public function index()
    {
        $suppliers = Organization::supplier()->latest()->paginate(20);
        return view('admin.suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        $supplier = new Organization(['type' => 'supplier', 'status' => 'active']);
        return view('admin.suppliers.create', compact('supplier'));
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

        $supplier = Organization::create(array_merge($validated, ['type' => 'supplier']));

        return redirect()->route('admin.inventory.suppliers.index')
            ->with('success', 'Supplier created successfully.');
    }

    public function show(Organization $supplier)
    {
        if ($supplier->type !== 'supplier') {
            abort(404);
        }

        $supplier->loadCount('users')
            ->load([
                'users' => fn ($query) => $query->orderBy('name')->limit(6),
            ]);

        return view('admin.suppliers.show', compact('supplier'));
    }

    public function edit(Organization $supplier)
    {
        if ($supplier->type !== 'supplier') {
            abort(404);
        }

        return view('admin.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Organization $supplier)
    {
        if ($supplier->type !== 'supplier') {
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

        $supplier->update($validated);

        return redirect()->route('admin.inventory.suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Organization $supplier)
    {
        if ($supplier->type !== 'supplier') {
            abort(404);
        }

        $supplier->delete();

        return redirect()->route('admin.inventory.suppliers.index')
            ->with('success', 'Supplier deleted successfully.');
    }
}
