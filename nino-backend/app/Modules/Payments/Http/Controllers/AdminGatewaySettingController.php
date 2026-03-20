<?php

namespace App\Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Payments\Models\GatewaySetting;
use Illuminate\Http\Request;

class AdminGatewaySettingController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Display a listing of all available payment gateways so Admins can configure their keys.
     */
    public function index()
    {
        $this->authorize('viewAny', GatewaySetting::class);

        // Auto-seed required Gateway skeletons on first load so the Admin has something to configure
        $this->ensureBaseGatewaysExist();

        $gateways = GatewaySetting::orderBy('name')->get();

        return view('admin.gateways.index', compact('gateways'));
    }

    /**
     * Updates the API keys, modes, and enablement status for a specific gateway.
     * Masked values (e.g., '*******') are preserved from the existing record.
     */
    public function update(Request $request, GatewaySetting $gateway)
    {
        $this->authorize('update', $gateway);

        // Validate incoming structure
        $validated = $request->validate([
            'is_enabled' => 'nullable|boolean',
            'mode' => 'required|in:test,live',
            'credentials' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        $before = [
            'is_enabled' => $gateway->is_enabled,
            'mode' => $gateway->mode,
            'credentials' => $gateway->credentials ?? [],
            'metadata' => $gateway->metadata ?? [],
        ];

        // Normalize boolean off state
        $validated['is_enabled'] = $request->has('is_enabled');

        // Smart credential merge: skip masked values so we don't overwrite real secrets
        if (!empty($validated['credentials'])) {
            $existingCreds = $gateway->credentials ?? [];
            foreach ($validated['credentials'] as $key => $value) {
                if ($value === '*******' || $value === '' || $value === null) {
                    // Keep the existing value
                    $validated['credentials'][$key] = $existingCreds[$key] ?? null;
                }
            }
        }

        // Merge metadata with existing values (don't wipe non-submitted keys)
        if (!empty($validated['metadata'])) {
            $validated['metadata'] = array_merge($gateway->metadata ?? [], $validated['metadata']);
        }

        $gateway->update($validated);

        $this->audit->log(
            action: 'payments.gateway.updated',
            target: $gateway,
            oldValues: $before,
            newValues: [
                'is_enabled' => $gateway->is_enabled,
                'mode' => $gateway->mode,
                'credentials' => $gateway->credentials ?? [],
                'metadata' => $gateway->metadata ?? [],
            ],
            notes: "{$gateway->name} payment gateway updated",
            context: [
                'gateway_id' => $gateway->gateway_id,
                'source' => 'admin_gateway_setting_controller',
                'changed_fields' => array_keys($validated),
            ],
        );

        return redirect()->route('admin.gateways.index')->with('success', "{$gateway->name} settings updated successfully.");
    }

    /**
     * Prevents empty database states by injecting the strictly supported adapters.
     */
    private function ensureBaseGatewaysExist(): void
    {
        $adapters = [
            [
                'gateway_id' => 'offline_transfer',
                'name' => 'Offline Bank Transfer',
            ],
            [
                'gateway_id' => 'cmi',
                'name' => 'CMI (Centre Monétique Interbancaire)',
            ],
            [
                'gateway_id' => 'payzone',
                'name' => 'Payzone Morocco',
            ],
            [
                'gateway_id' => 'stripe',
                'name' => 'Stripe',
            ]
        ];

        foreach ($adapters as $adapter) {
            GatewaySetting::firstOrCreate(
                ['gateway_id' => $adapter['gateway_id']],
                [
                    'name' => $adapter['name'],
                    'is_enabled' => false,
                    'mode' => 'test',
                    'credentials' => [],
                    'metadata' => []
                ]
            );
        }
    }
}
