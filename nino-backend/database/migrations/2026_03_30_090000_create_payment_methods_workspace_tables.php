<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name', 120);
            $table->string('channel', 20)->default('offline');
            $table->string('behavior', 30)->default('offline_manual');
            $table->foreignId('gateway_setting_id')->nullable()->constrained('gateway_settings')->nullOnDelete();
            $table->boolean('is_enabled')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['is_enabled', 'sort_order']);
            $table->index(['channel', 'behavior']);
        });

        Schema::create('payment_method_shipping_carrier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_method_id')->constrained('payment_methods')->cascadeOnDelete();
            $table->foreignId('shipping_carrier_id')->constrained('shipping_carriers')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['payment_method_id', 'shipping_carrier_id'], 'payment_method_carrier_unique');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('payment_method_id')
                ->nullable()
                ->after('payment_method')
                ->constrained('payment_methods')
                ->nullOnDelete();
            $table->string('payment_method_label')->nullable()->after('payment_method_id');
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->foreignId('payment_method_id')
                ->nullable()
                ->after('payment_method')
                ->constrained('payment_methods')
                ->nullOnDelete();
            $table->string('payment_method_label', 160)->nullable()->after('payment_method_id');
            $table->string('payment_method_behavior', 30)->nullable()->after('payment_method_label');
        });

        $this->seedDefaultPaymentMethods();
        $this->backfillExistingOrderPaymentMethods();
        $this->backfillExistingTransactionPaymentMethods();
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_method_id');
            $table->dropColumn(['payment_method_label', 'payment_method_behavior']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_method_id');
            $table->dropColumn('payment_method_label');
        });

        Schema::dropIfExists('payment_method_shipping_carrier');
        Schema::dropIfExists('payment_methods');
    }

    private function seedDefaultPaymentMethods(): void
    {
        $gatewaySettings = DB::table('gateway_settings')
            ->select(['id', 'gateway_id', 'name', 'is_enabled', 'metadata'])
            ->get()
            ->keyBy('gateway_id');

        $defaults = [
            [
                'code' => 'cod',
                'name' => 'Cash on Delivery',
                'channel' => 'offline',
                'behavior' => 'cod',
                'gateway_setting_id' => null,
                'is_enabled' => true,
                'sort_order' => 10,
                'metadata' => [
                    'checkout_title' => 'Cash on delivery',
                    'checkout_description' => 'Collect payment from the customer when the shipment is delivered.',
                    'instructions' => 'The courier will collect the amount upon delivery.',
                    'admin_instructions' => 'Use COD reconciliation after collection by the delivery carrier.',
                ],
            ],
            [
                'code' => 'bank_transfer',
                'name' => 'Bank Transfer',
                'channel' => 'offline',
                'behavior' => 'offline_manual',
                'gateway_setting_id' => $gatewaySettings['offline_transfer']->id ?? null,
                'is_enabled' => (bool) ($gatewaySettings['offline_transfer']->is_enabled ?? true),
                'sort_order' => 20,
                'metadata' => array_merge([
                    'method_label' => 'Bank Transfer',
                    'checkout_title' => 'Bank transfer instructions',
                    'checkout_description' => 'Complete the transfer manually and wait for finance verification.',
                    'instructions' => 'Please complete the transfer using the provided bank details.',
                    'admin_instructions' => 'Finance should validate the transfer and mark it as completed only after confirmation.',
                    'payment_window_hours' => 48,
                    'reference_prefix' => 'NINO',
                    'require_receipt' => true,
                ], (array) json_decode($gatewaySettings['offline_transfer']->metadata ?? '[]', true)),
            ],
            [
                'code' => 'cmi',
                'name' => 'CMI',
                'channel' => 'online',
                'behavior' => 'gateway',
                'gateway_setting_id' => $gatewaySettings['cmi']->id ?? null,
                'is_enabled' => (bool) ($gatewaySettings['cmi']->is_enabled ?? false),
                'sort_order' => 30,
                'metadata' => [
                    'provider_gateway_id' => 'cmi',
                    'checkout_title' => 'CMI card payment',
                ],
            ],
            [
                'code' => 'payzone',
                'name' => 'Payzone',
                'channel' => 'online',
                'behavior' => 'gateway',
                'gateway_setting_id' => $gatewaySettings['payzone']->id ?? null,
                'is_enabled' => (bool) ($gatewaySettings['payzone']->is_enabled ?? false),
                'sort_order' => 40,
                'metadata' => [
                    'provider_gateway_id' => 'payzone',
                    'checkout_title' => 'Payzone checkout',
                ],
            ],
            [
                'code' => 'stripe',
                'name' => 'Stripe',
                'channel' => 'online',
                'behavior' => 'gateway',
                'gateway_setting_id' => $gatewaySettings['stripe']->id ?? null,
                'is_enabled' => (bool) ($gatewaySettings['stripe']->is_enabled ?? false),
                'sort_order' => 50,
                'metadata' => [
                    'provider_gateway_id' => 'stripe',
                    'checkout_title' => 'Stripe checkout',
                ],
            ],
        ];

        foreach ($defaults as $default) {
            DB::table('payment_methods')->updateOrInsert(
                ['code' => $default['code']],
                [
                    'name' => $default['name'],
                    'channel' => $default['channel'],
                    'behavior' => $default['behavior'],
                    'gateway_setting_id' => $default['gateway_setting_id'],
                    'is_enabled' => $default['is_enabled'],
                    'sort_order' => $default['sort_order'],
                    'metadata' => json_encode($default['metadata'], JSON_THROW_ON_ERROR),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    private function backfillExistingOrderPaymentMethods(): void
    {
        $methods = DB::table('payment_methods')
            ->select(['id', 'code', 'name', 'behavior'])
            ->get()
            ->keyBy('code');

        DB::table('orders')
            ->select(['id', 'payment_method'])
            ->orderBy('id')
            ->chunkById(200, function ($orders) use ($methods): void {
                foreach ($orders as $order) {
                    $normalizedCode = $this->normalizeLegacyMethodCode($order->payment_method);
                    $method = $normalizedCode ? ($methods[$normalizedCode] ?? null) : null;

                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update([
                            'payment_method_id' => $method->id ?? null,
                            'payment_method_label' => $method->name ?? $this->legacyMethodLabel($order->payment_method),
                        ]);
                }
            });
    }

    private function backfillExistingTransactionPaymentMethods(): void
    {
        $methods = DB::table('payment_methods')
            ->select(['id', 'code', 'name', 'behavior'])
            ->get()
            ->keyBy('code');

        DB::table('payment_transactions')
            ->select(['id', 'payment_method'])
            ->orderBy('id')
            ->chunkById(200, function ($transactions) use ($methods): void {
                foreach ($transactions as $transaction) {
                    $normalizedCode = $this->normalizeLegacyMethodCode($transaction->payment_method);
                    $method = $normalizedCode ? ($methods[$normalizedCode] ?? null) : null;

                    DB::table('payment_transactions')
                        ->where('id', $transaction->id)
                        ->update([
                            'payment_method_id' => $method->id ?? null,
                            'payment_method_label' => $method->name ?? $this->legacyMethodLabel($transaction->payment_method),
                            'payment_method_behavior' => $method->behavior ?? ($normalizedCode === 'cod' ? 'cod' : 'offline_manual'),
                        ]);
                }
            });
    }

    private function normalizeLegacyMethodCode(?string $code): ?string
    {
        $code = strtolower(trim((string) $code));

        return match ($code) {
            '', 'other' => null,
            'cash_on_delivery' => 'cod',
            'offline_transfer' => 'bank_transfer',
            default => $code,
        };
    }

    private function legacyMethodLabel(?string $code): ?string
    {
        $normalizedCode = $this->normalizeLegacyMethodCode($code);

        return match ($normalizedCode) {
            'cod' => 'Cash on Delivery',
            'bank_transfer' => 'Bank Transfer',
            'cmi' => 'CMI',
            'payzone' => 'Payzone',
            'stripe' => 'Stripe',
            default => $code ? str($code)->replace(['_', '-'], ' ')->title()->value() : null,
        };
    }
};
