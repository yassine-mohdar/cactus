<?php

namespace App\Modules\Shipping\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shipping\Enums\ShipmentStatus;
use App\Modules\Shipping\Models\Shipment;
use App\Modules\Shipping\Models\ShippingCarrier;
use App\Modules\Shipping\Models\ShippingMethodDistrictOverride;
use App\Modules\Shipping\Models\ShippingMethod;
use App\Modules\Shipping\Services\ShippingSettingsService;
use App\Support\InternationalDirectory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ShippingMethodController extends Controller
{
    public function __construct(
        private readonly ShippingSettingsService $shippingSettings,
    ) {
        $this->middleware('permission.any:shipping.viewAny,shipping.manage_carriers')->only('index');
        $this->middleware('permission.any:shipping.manage_carriers')->except('index');
    }

    public function index()
    {
        $methods = ShippingMethod::with(['shippingCarrier'])->withCount('shipments')->orderBy('sort_order')->get();

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
            ->groupBy(fn (ShippingMethod $method) => $method->carrierLabel())
            ->map(fn ($group, $label) => ['label' => $label, 'count' => $group->count()])
            ->sortByDesc('count')
            ->values();

        return view('admin.shipping.methods.index', compact('methods', 'stats', 'carrierBreakdown'));
    }

    public function create()
    {
        $carriers = ShippingCarrier::enabled()->orderBy('name')->get();

        return view('admin.shipping.methods.create', [
            'carriers' => $carriers,
            'apiCarrierDirectory' => $this->apiCarrierDirectory($carriers),
            'districtOverrideMap' => [],
            'countryOptions' => InternationalDirectory::countries(),
            'selectedCountryCodes' => [],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'shipping_carrier_id' => 'nullable|exists:shipping_carriers,id',
            'carrier' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'base_cost' => 'nullable|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'estimated_days' => 'nullable|string|max:30',
            'is_enabled' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'metadata.allowed_countries' => 'nullable|array',
            'metadata.allowed_countries.*' => 'string|size:2',
            'district_overrides' => 'nullable|array',
            'district_overrides.*' => 'nullable|numeric|min:0',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_enabled'] = $request->has('is_enabled');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated = $this->normalizeCoverageMetadata($validated);
        $validated = $this->applyCarrierSelection($validated);
        $carrier = $this->selectedCarrier($validated);

        $this->assertPricingContract($carrier, $validated);
        $validated = $this->normalizePricingContract($carrier, $validated);

        // Ensure slug uniqueness
        $baseSlug = $validated['slug'];
        $counter = 1;
        while (ShippingMethod::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $baseSlug . '-' . $counter++;
        }

        $method = ShippingMethod::create($validated);
        $this->syncDistrictOverrides($method, $request, $carrier);

        return redirect()->route('admin.shipping.methods.index')
            ->with('success', 'Shipping method created successfully.');
    }

    public function edit(ShippingMethod $method)
    {
        $method->loadCount('shipments');
        $carriers = ShippingCarrier::enabled()->orderBy('name')->get();

        return view('admin.shipping.methods.edit', [
            'method' => $method,
            'carriers' => $carriers,
            'apiCarrierDirectory' => $this->apiCarrierDirectory($carriers),
            'districtOverrideMap' => $this->districtOverrideMap($method),
            'countryOptions' => InternationalDirectory::countries(),
            'selectedCountryCodes' => $method->allowedCountryCodes(),
        ]);
    }

    public function update(Request $request, ShippingMethod $method)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'shipping_carrier_id' => 'nullable|exists:shipping_carriers,id',
            'carrier' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'base_cost' => 'nullable|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'estimated_days' => 'nullable|string|max:30',
            'is_enabled' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'metadata.allowed_countries' => 'nullable|array',
            'metadata.allowed_countries.*' => 'string|size:2',
            'district_overrides' => 'nullable|array',
            'district_overrides.*' => 'nullable|numeric|min:0',
        ]);

        $validated['is_enabled'] = $request->has('is_enabled');
        $validated = $this->normalizeCoverageMetadata($validated);
        $validated = $this->applyCarrierSelection($validated);
        $carrier = $this->selectedCarrier($validated);

        $this->assertPricingContract($carrier, $validated);
        $validated = $this->normalizePricingContract($carrier, $validated);
        $method->update($validated);
        $this->syncDistrictOverrides($method, $request, $carrier);

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

    private function applyCarrierSelection(array $validated): array
    {
        $carrier = $this->resolveCarrier($validated);

        $validated['shipping_carrier_id'] = $carrier?->id;
        $validated['carrier'] = $carrier?->name ?: trim((string) ($validated['carrier'] ?? '')) ?: $this->shippingSettings->defaultCarrierName();

        return $validated;
    }

    private function resolveCarrier(array $validated): ?ShippingCarrier
    {
        if (! empty($validated['shipping_carrier_id'])) {
            $carrier = ShippingCarrier::find($validated['shipping_carrier_id']);

            if ($carrier) {
                return $carrier;
            }
        }

        if (filled($validated['carrier'] ?? null)) {
            $carrier = ShippingCarrier::query()
                ->whereRaw('LOWER(name) = ?', [Str::lower(trim((string) $validated['carrier']))])
                ->first();

            if ($carrier) {
                return $carrier;
            }
        }

        $defaultName = $this->shippingSettings->defaultCarrierName();

        return ShippingCarrier::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($defaultName)])
            ->first();
    }

    private function selectedCarrier(array $validated): ?ShippingCarrier
    {
        return ! empty($validated['shipping_carrier_id'])
            ? ShippingCarrier::find($validated['shipping_carrier_id'])
            : null;
    }

    private function assertPricingContract(?ShippingCarrier $carrier, array $validated): void
    {
        $isApiCarrier = $carrier?->provider !== null && $carrier->provider !== ShippingCarrier::PROVIDER_MANUAL;

        if ($isApiCarrier) {
            return;
        }

        if (! filled($validated['base_cost'] ?? null)) {
            throw ValidationException::withMessages([
                'base_cost' => 'Base cost is required for manual carriers.',
            ]);
        }
    }

    private function normalizePricingContract(?ShippingCarrier $carrier, array $validated): array
    {
        $isApiCarrier = $carrier?->provider !== null && $carrier->provider !== ShippingCarrier::PROVIDER_MANUAL;

        if ($isApiCarrier) {
            $validated['base_cost'] = 0;
            $validated['free_shipping_threshold'] = null;
            $validated['estimated_days'] = null;

            return $validated;
        }

        $validated['estimated_days'] = trim((string) ($validated['estimated_days'] ?? '')) ?: $this->shippingSettings->defaultEstimatedDays();

        return $validated;
    }

    private function normalizeCoverageMetadata(array $validated): array
    {
        $metadata = (array) ($validated['metadata'] ?? []);
        $allowedCountries = collect($metadata['allowed_countries'] ?? [])
            ->map(fn ($country) => strtoupper(trim((string) $country)))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $metadata['allowed_countries'] = $allowedCountries;
        $validated['metadata'] = $metadata;

        return $validated;
    }

    private function syncDistrictOverrides(ShippingMethod $method, Request $request, ?ShippingCarrier $carrier): void
    {
        if (! $carrier || $carrier->provider === ShippingCarrier::PROVIDER_MANUAL) {
            $method->districtOverrides()->delete();

            return;
        }

        $rawOverrides = collect($request->input('district_overrides', []))
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->filter(fn ($value) => $value !== null && $value !== '');

        $districts = $carrier->districts()
            ->whereIn('external_id', $rawOverrides->keys()->all())
            ->get()
            ->keyBy(fn ($district) => (string) $district->external_id);

        $keepIds = [];

        foreach ($rawOverrides as $externalId => $forcedPrice) {
            $district = $districts->get((string) $externalId);

            if (! $district) {
                continue;
            }

            $override = ShippingMethodDistrictOverride::updateOrCreate(
                [
                    'shipping_method_id' => $method->id,
                    'shipping_carrier_district_id' => $district->id,
                ],
                [
                    'forced_price' => (float) $forcedPrice,
                ]
            );

            $keepIds[] = $override->id;
        }

        $method->districtOverrides()
            ->when($keepIds !== [], fn ($query) => $query->whereNotIn('id', $keepIds))
            ->when($keepIds === [], fn ($query) => $query)
            ->delete();
    }

    private function apiCarrierDirectory($carriers): array
    {
        return ShippingCarrier::query()
            ->whereIn('id', $carriers->pluck('id'))
            ->where('provider', '!=', ShippingCarrier::PROVIDER_MANUAL)
            ->with(['districts' => function ($query): void {
                $query->where('is_active', true)
                    ->select([
                        'id',
                        'shipping_carrier_id',
                        'external_id',
                        'city',
                        'district_name',
                        'price',
                        'estimated_delivery',
                        'updated_at',
                    ]);
            }])
            ->get()
            ->mapWithKeys(function (ShippingCarrier $carrier): array {
                return [
                    (string) $carrier->id => [
                        'id' => $carrier->id,
                        'name' => $carrier->name,
                        'provider' => $carrier->provider,
                        'synced_at' => data_get($carrier->metadata ?? [], 'districts_synced_at'),
                        'districts' => $carrier->districts->map(fn ($district) => [
                            'id' => (string) $district->external_id,
                            'district' => trim((string) $district->district_name),
                            'city' => trim((string) $district->city),
                            'api_price' => (float) ($district->price ?? 0),
                            'estimated_delivery' => $district->estimated_delivery ?: 'TBD',
                            'updated_at' => optional($district->updated_at)->format('M d, Y H:i'),
                        ])->values()->all(),
                    ],
                ];
            })
            ->all();
    }

    private function districtOverrideMap(ShippingMethod $method): array
    {
        return $method->districtOverrides()
            ->with('district:id,external_id')
            ->get()
            ->filter(fn (ShippingMethodDistrictOverride $override) => $override->district !== null)
            ->mapWithKeys(fn (ShippingMethodDistrictOverride $override) => [
                (string) $override->district->external_id => number_format((float) $override->forced_price, 2, '.', ''),
            ])
            ->all();
    }
}
