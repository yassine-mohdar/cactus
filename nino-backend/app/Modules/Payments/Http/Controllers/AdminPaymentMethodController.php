<?php

namespace App\Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Payments\Models\GatewaySetting;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\Shipping\Models\ShippingCarrier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminPaymentMethodController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->validateOfflineMethod($request);

        $method = PaymentMethod::query()->create($payload['attributes']);
        $method->shippingCarriers()->sync($payload['carrier_ids']);

        $this->audit->log(
            action: 'payments.method.created',
            target: $method->fresh('shippingCarriers'),
            notes: 'Offline payment method created from the payments workspace.',
            context: [
                'carrier_ids' => $payload['carrier_ids'],
                'behavior' => $method->behavior,
            ],
        );

        return redirect()
            ->route('admin.gateways.index', ['tab' => 'methods', 'method' => $method->id])
            ->with('success', "{$method->checkoutLabel()} payment method created.");
    }

    public function update(Request $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        abort_if($paymentMethod->isOnline(), 422, 'Provider-backed methods are managed from the provider connections tab.');

        $payload = $this->validateOfflineMethod($request, $paymentMethod);
        $before = $paymentMethod->only(['code', 'name', 'behavior', 'is_enabled', 'sort_order', 'metadata']);
        $before['carrier_ids'] = $paymentMethod->shippingCarriers()->pluck('shipping_carriers.id')->all();

        $paymentMethod->update($payload['attributes']);
        $paymentMethod->shippingCarriers()->sync($payload['carrier_ids']);

        $this->audit->log(
            action: 'payments.method.updated',
            target: $paymentMethod->fresh('shippingCarriers'),
            oldValues: $before,
            newValues: array_merge($paymentMethod->only(['code', 'name', 'behavior', 'is_enabled', 'sort_order', 'metadata']), [
                'carrier_ids' => $paymentMethod->shippingCarriers()->pluck('shipping_carriers.id')->all(),
            ]),
            notes: 'Payment method updated from the payments workspace.',
            context: [
                'carrier_ids' => $payload['carrier_ids'],
                'behavior' => $paymentMethod->behavior,
            ],
        );

        return redirect()
            ->route('admin.gateways.index', ['tab' => 'methods', 'method' => $paymentMethod->id])
            ->with('success', "{$paymentMethod->checkoutLabel()} updated.");
    }

    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        abort_if($paymentMethod->isOnline(), 422, 'Provider-backed methods cannot be deleted from the payments workspace.');
        abort_if(in_array($paymentMethod->code, ['cod', 'bank_transfer'], true), 422, 'Default offline methods cannot be deleted.');

        $snapshot = $paymentMethod->only(['code', 'name', 'behavior', 'is_enabled', 'sort_order', 'metadata']);
        $snapshot['carrier_ids'] = $paymentMethod->shippingCarriers()->pluck('shipping_carriers.id')->all();

        $paymentMethod->delete();

        $this->audit->log(
            action: 'payments.method.deleted',
            target: $paymentMethod,
            oldValues: $snapshot,
            notes: 'Payment method deleted from the payments workspace.',
        );

        return redirect()
            ->route('admin.gateways.index', ['tab' => 'methods'])
            ->with('success', 'Payment method deleted.');
    }

    public function toggle(Request $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        abort_if($paymentMethod->isOnline(), 422, 'Provider-backed methods inherit their enabled state from provider connections.');

        $before = $paymentMethod->is_enabled;
        $paymentMethod->update(['is_enabled' => ! $paymentMethod->is_enabled]);

        $this->audit->log(
            action: 'payments.method.toggled',
            target: $paymentMethod,
            oldValues: ['is_enabled' => $before],
            newValues: ['is_enabled' => $paymentMethod->is_enabled],
            notes: 'Payment method toggled from the payments workspace.',
        );

        return redirect()
            ->route('admin.gateways.index', ['tab' => 'methods', 'method' => $paymentMethod->id])
            ->with('success', "{$paymentMethod->checkoutLabel()} is now ".($paymentMethod->is_enabled ? 'enabled' : 'disabled').'.');
    }

    /**
     * @return array{attributes: array<string, mixed>, carrier_ids: array<int, int>}
     */
    private function validateOfflineMethod(Request $request, ?PaymentMethod $method = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required',
                'string',
                'max:60',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('payment_methods', 'code')->ignore($method?->id),
            ],
            'is_enabled' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'behavior' => ['required', Rule::in([PaymentMethod::BEHAVIOR_OFFLINE_MANUAL, PaymentMethod::BEHAVIOR_COD])],
            'metadata.method_label' => ['required', 'string', 'max:120'],
            'metadata.checkout_title' => ['required', 'string', 'max:160'],
            'metadata.checkout_description' => ['nullable', 'string', 'max:500'],
            'metadata.instructions' => ['required', 'string', 'max:2000'],
            'metadata.admin_instructions' => ['nullable', 'string', 'max:2000'],
            'metadata.payment_window_hours' => ['nullable', 'integer', 'min:1', 'max:336'],
            'metadata.reference_prefix' => ['nullable', 'string', 'max:20'],
            'metadata.require_receipt' => ['nullable', 'boolean'],
            'carrier_ids' => ['nullable', 'array'],
            'carrier_ids.*' => ['integer', Rule::exists('shipping_carriers', 'id')],
        ]);

        $carrierIds = collect($validated['carrier_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (($validated['behavior'] ?? null) === PaymentMethod::BEHAVIOR_COD && $carrierIds === []) {
            throw ValidationException::withMessages([
                'carrier_ids' => 'COD payment methods must be linked to at least one shipping carrier.',
            ]);
        }

        return [
            'attributes' => [
                'gateway_setting_id' => $this->resolveGatewaySettingId($method, $validated),
                'name' => trim((string) $validated['name']),
                'code' => strtolower(trim((string) $validated['code'])),
                'channel' => PaymentMethod::CHANNEL_OFFLINE,
                'behavior' => (string) $validated['behavior'],
                'is_enabled' => $request->boolean('is_enabled'),
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
                'metadata' => [
                    'method_label' => trim((string) data_get($validated, 'metadata.method_label')),
                    'checkout_title' => trim((string) data_get($validated, 'metadata.checkout_title')),
                    'checkout_description' => trim((string) data_get($validated, 'metadata.checkout_description', '')),
                    'instructions' => trim((string) data_get($validated, 'metadata.instructions')),
                    'admin_instructions' => trim((string) data_get($validated, 'metadata.admin_instructions', '')),
                    'payment_window_hours' => data_get($validated, 'metadata.payment_window_hours') !== null
                        ? (int) data_get($validated, 'metadata.payment_window_hours')
                        : null,
                    'reference_prefix' => strtoupper(trim((string) data_get($validated, 'metadata.reference_prefix', ''))),
                    'require_receipt' => $request->boolean('metadata.require_receipt'),
                    'sync_from_gateway_defaults' => $this->shouldSyncFromGatewayDefaults($method, $validated),
                ],
            ],
            'carrier_ids' => $carrierIds,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveGatewaySettingId(?PaymentMethod $method, array $validated): ?int
    {
        $code = strtolower(trim((string) ($validated['code'] ?? $method?->code ?? '')));

        if ($code !== 'bank_transfer') {
            return null;
        }

        return $method?->gateway_setting_id
            ?? GatewaySetting::query()
                ->where('gateway_id', 'offline_transfer')
                ->value('id');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function shouldSyncFromGatewayDefaults(?PaymentMethod $method, array $validated): bool
    {
        $code = strtolower(trim((string) ($validated['code'] ?? $method?->code ?? '')));

        if ($code !== 'bank_transfer') {
            return false;
        }

        return false;
    }
}
