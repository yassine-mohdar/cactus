<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customers\Models\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    /**
     * List all addresses for the authenticated customer.
     */
    public function index(Request $request)
    {
        $addresses = $request->user()->addresses()->latest()->get();
        return response()->json(['addresses' => $addresses]);
    }

    /**
     * Store a new address.
     */
    public function store(Request $request)
    {
        $validated = $this->validateAddress($request);

        $address = $request->user()->addresses()->create($validated);

        if ($address->is_default) {
            $this->unsetOtherDefaults($request->user(), $address);
        }

        return response()->json([
            'message' => 'Address created successfully.',
            'address' => $address
        ], 201);
    }

    /**
     * Update an address.
     */
    public function update(Request $request, Address $address)
    {
        $this->authorizeAddress($request->user(), $address);

        $validated = $this->validateAddress($request);
        $address->update($validated);

        if ($address->is_default) {
            $this->unsetOtherDefaults($request->user(), $address);
        }

        return response()->json([
            'message' => 'Address updated successfully.',
            'address' => $address
        ]);
    }

    /**
     * Delete an address.
     */
    public function destroy(Request $request, Address $address)
    {
        $this->authorizeAddress($request->user(), $address);
        
        $address->delete();

        return response()->json([
            'message' => 'Address deleted successfully.'
        ]);
    }

    /**
     * Validation rules for address.
     */
    protected function validateAddress(Request $request): array
    {
        return $request->validate([
            'type' => 'required|in:billing,shipping',
            'is_default' => 'boolean',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
        ]);
    }

    /**
     * Ensure the user owns the address.
     */
    protected function authorizeAddress($user, Address $address): void
    {
        if ($address->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }
    }

    /**
     * Ensure only one default address exists per user per type.
     */
    protected function unsetOtherDefaults($user, Address $currentAddress): void
    {
        $user->addresses()
            ->where('id', '!=', $currentAddress->id)
            ->where('type', $currentAddress->type)
            ->update(['is_default' => false]);
    }
}
