<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customers\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AddressController extends Controller
{
    public function manage(Request $request)
    {
        return view('customer.account.addresses', [
            'customer' => $request->user(),
            'addresses' => $request->user()
                ->addresses()
                ->orderByDesc('is_default')
                ->orderBy('type')
                ->latest('id')
                ->get(),
        ]);
    }

    /**
     * List all addresses for the authenticated customer.
     */
    public function index(Request $request)
    {
        $addresses = $request->user()
            ->addresses()
            ->orderByDesc('is_default')
            ->orderBy('type')
            ->latest('id')
            ->get();

        return response()->json(['addresses' => $addresses]);
    }

    /**
     * Store a new address.
     */
    public function store(Request $request)
    {
        $this->normalizeAddressPayload($request);

        $validated = $this->validateAddress($request);
        $validated['is_default'] = $this->shouldMarkAsDefault($request, $validated);

        $address = $request->user()->addresses()->create($validated);

        if ($address->is_default) {
            $this->unsetOtherDefaults($request->user(), $address);
        }

        if (! ($request->expectsJson() || $request->is('api/*'))) {
            return redirect()
                ->route('customer.account.addresses.index')
                ->with('success', 'Address created successfully.');
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
        $this->normalizeAddressPayload($request);

        $validated = $this->validateAddress($request);
        $validated['is_default'] = $this->resolveUpdatedDefaultState($request, $address, $validated);
        $address->update($validated);

        if ($address->is_default) {
            $this->unsetOtherDefaults($request->user(), $address);
        }

        if (! ($request->expectsJson() || $request->is('api/*'))) {
            return redirect()
                ->route('customer.account.addresses.index')
                ->with('success', 'Address updated successfully.');
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

        $deletedWasDefault = $address->isDefault();
        $deletedType = $address->type;
        $address->delete();

        if ($deletedWasDefault) {
            $this->promoteReplacementDefault($request->user(), $deletedType);
        }

        if (! ($request->expectsJson() || $request->is('api/*'))) {
            return redirect()
                ->route('customer.account.addresses.index')
                ->with('success', 'Address deleted successfully.');
        }

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
            'type' => ['required', Rule::in(Address::TYPES)],
            'is_default' => 'boolean',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:120',
            'state' => 'required|string|max:120',
            'postal_code' => 'required|string|max:32',
            'country' => ['required', 'string', 'size:2', 'regex:'.Address::COUNTRY_CODE_PATTERN],
            'phone' => 'nullable|string|max:50',
        ]);
    }

    protected function normalizeAddressPayload(Request $request): void
    {
        $request->merge([
            'type' => $this->normalizeNullableString($request->input('type')),
            'first_name' => $this->normalizeNullableString($request->input('first_name')),
            'last_name' => $this->normalizeNullableString($request->input('last_name')),
            'company' => $this->normalizeNullableString($request->input('company')),
            'address_line_1' => $this->normalizeNullableString($request->input('address_line_1')),
            'address_line_2' => $this->normalizeNullableString($request->input('address_line_2')),
            'city' => $this->normalizeNullableString($request->input('city')),
            'state' => $this->normalizeNullableString($request->input('state')),
            'postal_code' => $this->normalizeNullableString($request->input('postal_code')),
            'country' => $this->normalizeCountryCode($request->input('country')),
            'phone' => $this->normalizeNullableString($request->input('phone')),
            'is_default' => $request->boolean('is_default'),
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

    protected function shouldMarkAsDefault(Request $request, array $validated): bool
    {
        if (($validated['is_default'] ?? false) === true) {
            return true;
        }

        return ! $request->user()
            ->addresses()
            ->where('type', $validated['type'])
            ->exists();
    }

    protected function resolveUpdatedDefaultState(Request $request, Address $address, array $validated): bool
    {
        if (($validated['is_default'] ?? false) === true) {
            return true;
        }

        $sameTypeDefaultExists = $request->user()
            ->addresses()
            ->where('id', '!=', $address->id)
            ->where('type', $validated['type'])
            ->default()
            ->exists();

        return ! $sameTypeDefaultExists;
    }

    protected function promoteReplacementDefault($user, string $type): void
    {
        $replacement = $user->addresses()
            ->where('type', $type)
            ->latest('id')
            ->first();

        if ($replacement !== null) {
            $replacement->update(['is_default' => true]);
        }
    }

    protected function normalizeNullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function normalizeCountryCode($value): ?string
    {
        $normalized = $this->normalizeNullableString($value);

        return $normalized === null ? null : strtoupper($normalized);
    }
}
