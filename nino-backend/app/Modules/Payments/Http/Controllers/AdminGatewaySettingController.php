<?php

namespace App\Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Payments\Gateways\OfflinePaymentGateway;
use App\Modules\Payments\Models\GatewaySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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

        $payload = [
            'is_enabled' => $request->boolean('is_enabled'),
            'mode' => $request->input('mode'),
            'credentials' => is_array($request->input('credentials')) ? $request->input('credentials') : [],
            'metadata' => is_array($request->input('metadata')) ? $request->input('metadata') : [],
        ];

        $payload['credentials'] = $this->mergeMaskedCredentials($gateway, $payload['credentials']);
        $payload['metadata'] = $this->normalizeMetadata(
            $gateway,
            array_merge($gateway->metadata ?? [], $payload['metadata'])
        );

        $validated = Validator::make($payload, $this->rulesForGateway($gateway))->validate();

        $before = [
            'is_enabled' => $gateway->is_enabled,
            'mode' => $gateway->mode,
            'credentials' => $gateway->credentials ?? [],
            'metadata' => $gateway->metadata ?? [],
        ];

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
     * @return array<string, mixed>
     */
    private function rulesForGateway(GatewaySetting $gateway): array
    {
        $rules = [
            'is_enabled' => ['boolean'],
            'mode' => ['required', Rule::in(['test', 'live'])],
            'credentials' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];

        return match ($gateway->gateway_id) {
            'stripe' => array_merge($rules, [
                'credentials.secret_key' => ['required', 'string', 'max:255'],
                'credentials.webhook_secret' => ['required', 'string', 'max:255'],
                'metadata.publishable_key' => ['required', 'string', 'max:255'],
                'metadata.currency' => ['required', 'string', 'size:3', 'alpha'],
            ]),
            'cmi' => array_merge($rules, [
                'credentials.store_id' => ['required', 'string', 'max:120'],
                'credentials.client_id' => ['required', 'string', 'max:120'],
                'credentials.hash_key' => ['required', 'string', 'max:255'],
                'credentials.terminal_id' => ['nullable', 'string', 'max:120'],
                'metadata.currency_code' => ['required', 'regex:/^[0-9]{3}$/'],
                'metadata.language' => ['required', Rule::in(['fr', 'ar', 'en'])],
            ]),
            'payzone' => array_merge($rules, [
                'credentials.merchant_id' => ['required', 'string', 'max:120'],
                'credentials.api_key' => ['required', 'string', 'max:255'],
                'credentials.secret_key' => ['required', 'string', 'max:255'],
                'metadata.currency' => ['required', 'string', 'size:3', 'alpha'],
            ]),
            'offline_transfer' => array_merge($rules, [
                'metadata.method_label' => ['required', 'string', 'max:120'],
                'metadata.checkout_title' => ['required', 'string', 'max:160'],
                'metadata.checkout_description' => ['nullable', 'string', 'max:500'],
                'metadata.instructions' => ['required', 'string', 'max:2000'],
                'metadata.admin_instructions' => ['nullable', 'string', 'max:2000'],
                'metadata.bank_name' => ['nullable', 'string', 'max:150'],
                'metadata.account_holder' => ['nullable', 'string', 'max:150'],
                'metadata.account_number' => ['nullable', 'string', 'max:120'],
                'metadata.iban' => ['nullable', 'string', 'max:40'],
                'metadata.swift_code' => ['nullable', 'string', 'max:20'],
                'metadata.payment_window_hours' => ['nullable', 'integer', 'min:1', 'max:168'],
                'metadata.reference_prefix' => ['nullable', 'string', 'max:20'],
                'metadata.require_receipt' => ['nullable', 'boolean'],
            ]),
            default => $rules,
        };
    }

    /**
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    private function mergeMaskedCredentials(GatewaySetting $gateway, array $incoming): array
    {
        $existing = $gateway->credentials ?? [];

        foreach ($incoming as $key => $value) {
            if ($value === '*******' || $value === '' || $value === null) {
                $incoming[$key] = $existing[$key] ?? null;
            }
        }

        return $incoming;
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function normalizeMetadata(GatewaySetting $gateway, array $metadata): array
    {
        if ($gateway->gateway_id === 'cmi') {
            if (isset($metadata['currency_code'])) {
                $metadata['currency_code'] = preg_replace('/\D+/', '', (string) $metadata['currency_code']);
            }

            if (isset($metadata['language'])) {
                $metadata['language'] = strtolower(trim((string) $metadata['language']));
            }
        }

        if ($gateway->gateway_id === 'payzone' && isset($metadata['currency'])) {
            $metadata['currency'] = strtoupper(trim((string) $metadata['currency']));
        }

        if ($gateway->gateway_id === 'stripe' && isset($metadata['currency'])) {
            $metadata['currency'] = strtoupper(trim((string) $metadata['currency']));
        }

        if ($gateway->gateway_id === 'offline_transfer') {
            foreach (['method_label', 'checkout_title', 'checkout_description', 'instructions', 'admin_instructions', 'bank_name', 'account_holder', 'account_number', 'iban', 'swift_code'] as $key) {
                if (isset($metadata[$key])) {
                    $metadata[$key] = trim((string) $metadata[$key]);
                }
            }

            if (isset($metadata['payment_window_hours'])) {
                $metadata['payment_window_hours'] = (int) $metadata['payment_window_hours'];
            }

            if (isset($metadata['reference_prefix'])) {
                $metadata['reference_prefix'] = strtoupper(trim((string) $metadata['reference_prefix']));
            }

            if (array_key_exists('require_receipt', $metadata)) {
                $metadata['require_receipt'] = filter_var($metadata['require_receipt'], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $metadata;
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
                'metadata' => [
                    'method_label' => 'Bank Transfer',
                    'checkout_title' => 'Bank transfer instructions',
                    'checkout_description' => 'Show the account details after checkout so customers can complete the transfer manually.',
                    'instructions' => 'Please complete the transfer using the bank details below and share your receipt with the finance team.',
                    'admin_instructions' => 'Finance should validate the transfer reference and receipt before marking the transaction as completed.',
                    'payment_window_hours' => 48,
                    'reference_prefix' => 'NINO',
                    'require_receipt' => true,
                ],
            ],
            [
                'gateway_id' => 'cmi',
                'name' => 'CMI (Centre Monétique Interbancaire)',
                'metadata' => [
                    'currency_code' => '504',
                    'language' => 'fr',
                ],
            ],
            [
                'gateway_id' => 'payzone',
                'name' => 'Payzone Morocco',
                'metadata' => [
                    'currency' => 'MAD',
                ],
            ],
            [
                'gateway_id' => 'stripe',
                'name' => 'Stripe',
                'metadata' => [
                    'currency' => 'MAD',
                ],
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
                    'metadata' => $adapter['metadata'] ?? [],
                ]
            );
        }
    }
}
