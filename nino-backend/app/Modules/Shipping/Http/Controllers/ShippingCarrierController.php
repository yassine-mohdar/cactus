<?php

namespace App\Modules\Shipping\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shipping\Models\ShippingCarrier;
use App\Modules\Shipping\Services\SenditService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShippingCarrierController extends Controller
{
    public function __construct(
        private readonly SenditService $senditService,
    ) {
        $this->middleware('permission.any:shipping.viewAny,shipping.manage_carriers')->only(['index', 'edit']);
        $this->middleware('permission.any:shipping.manage_carriers')->except(['index', 'edit']);
    }

    public function index()
    {
        $carriers = ShippingCarrier::query()
            ->withCount(['shippingMethods', 'districts'])
            ->orderBy('name')
            ->get();

        return view('admin.shipping.carriers.index', compact('carriers'));
    }

    public function create()
    {
        return view('admin.shipping.carriers.create', [
            'pickupDistricts' => collect(),
        ]);
    }

    public function store(Request $request)
    {
        $carrier = ShippingCarrier::create($this->validatePayload($request));

        return redirect()
            ->route('admin.shipping.carriers.edit', $carrier)
            ->with('success', 'Carrier created successfully.');
    }

    public function edit(ShippingCarrier $carrier)
    {
        $carrier->loadCount(['shippingMethods', 'districts']);
        $pickupDistricts = $carrier->districts()->get();

        return view('admin.shipping.carriers.edit', [
            'carrier' => $carrier,
            'pickupDistricts' => $pickupDistricts,
        ]);
    }

    public function update(Request $request, ShippingCarrier $carrier)
    {
        $carrier->update($this->validatePayload($request, $carrier));

        return redirect()
            ->route('admin.shipping.carriers.edit', $carrier)
            ->with('success', 'Carrier updated successfully.');
    }

    public function destroy(ShippingCarrier $carrier)
    {
        if ($carrier->shippingMethods()->exists()) {
            return back()->with('error', 'Cannot delete a carrier that is linked to shipping methods.');
        }

        $carrier->delete();

        return redirect()
            ->route('admin.shipping.carriers.index')
            ->with('success', 'Carrier deleted.');
    }

    public function syncDistricts(ShippingCarrier $carrier)
    {
        abort_unless($carrier->isSendit(), 404);

        $count = $this->senditService->syncDistricts($carrier);

        return redirect()
            ->route('admin.shipping.carriers.edit', $carrier)
            ->with('success', "Sendit districts synced: {$count} records updated.");
    }

    private function validatePayload(Request $request, ?ShippingCarrier $carrier = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:60',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('shipping_carriers', 'code')->ignore($carrier?->id),
            ],
            'provider' => ['required', Rule::in([ShippingCarrier::PROVIDER_MANUAL, ShippingCarrier::PROVIDER_SENDIT])],
            'tracking_url_template' => ['nullable', 'string', 'max:500'],
            'is_enabled' => ['nullable', 'boolean'],
            'credentials.public_key' => ['nullable', 'string', 'max:255'],
            'credentials.secret_key' => ['nullable', 'string', 'max:255'],
            'settings.pickup_district_id' => ['nullable', 'integer', 'min:1'],
            'settings.default_label_format' => ['nullable', 'in:0,1'],
            'settings.webhook_secret' => ['nullable', 'string', 'max:255'],
            'settings.webhook_api_key' => ['nullable', 'string', 'max:255'],
            'metadata.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['code'] = $validated['code'] ?? Str::slug($validated['name']);
        $validated['is_enabled'] = $request->boolean('is_enabled');
        $validated['tracking_url_template'] = trim((string) ($validated['tracking_url_template'] ?? '')) ?: null;

        $existingCredentials = $carrier?->credentials ?? [];
        $credentials = [
            'public_key' => trim((string) data_get($validated, 'credentials.public_key', '')),
            'secret_key' => trim((string) data_get($validated, 'credentials.secret_key', '')),
        ];

        if (($validated['provider'] ?? null) === ShippingCarrier::PROVIDER_SENDIT) {
            if ($credentials['public_key'] === '' && $carrier) {
                $credentials['public_key'] = (string) data_get($existingCredentials, 'public_key', '');
            }

            if ($credentials['secret_key'] === '' && $carrier) {
                $credentials['secret_key'] = (string) data_get($existingCredentials, 'secret_key', '');
            }

            if ($credentials['public_key'] === '' || $credentials['secret_key'] === '') {
                throw ValidationException::withMessages([
                    'credentials.public_key' => 'Sendit carriers require both public and secret keys.',
                ]);
            }

        } else {
            $credentials = [];
        }

        $pickupDistrictId = data_get($validated, 'settings.pickup_district_id');
        $webhookSecret = trim((string) data_get($validated, 'settings.webhook_secret', ''));
        $webhookApiKey = trim((string) data_get($validated, 'settings.webhook_api_key', ''));

        if (! filled($pickupDistrictId) && $carrier?->isSendit()) {
            $pickupDistrictId = data_get($carrier->settings ?? [], 'pickup_district_id');
        }

        if ($validated['provider'] === ShippingCarrier::PROVIDER_SENDIT) {
            if ($webhookSecret === '' && $carrier) {
                $webhookSecret = (string) data_get($carrier->settings ?? [], 'webhook_secret', '');
            }

            if ($webhookSecret === '') {
                $webhookSecret = Str::random(40);
            }

            if ($webhookApiKey === '' && $carrier) {
                $webhookApiKey = (string) data_get($carrier->settings ?? [], 'webhook_api_key', '');
            }
        }

        $validated['credentials'] = $credentials ?: null;
        $validated['settings'] = [
            'pickup_district_id' => $pickupDistrictId,
            'default_label_format' => (int) data_get($validated, 'settings.default_label_format', 0),
            'webhook_secret' => $webhookSecret !== '' ? $webhookSecret : null,
            'webhook_api_key' => $webhookApiKey !== '' ? $webhookApiKey : null,
        ];
        $validated['metadata'] = [
            'notes' => data_get($validated, 'metadata.notes'),
            'districts_synced_at' => data_get($carrier?->metadata, 'districts_synced_at'),
        ];

        return $validated;
    }
}
