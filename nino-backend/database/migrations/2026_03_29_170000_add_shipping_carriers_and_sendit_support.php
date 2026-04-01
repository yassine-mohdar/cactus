<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_carriers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('provider', 40)->default('manual')->index();
            $table->boolean('is_enabled')->default(true);
            $table->string('tracking_url_template')->nullable();
            $table->json('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('shipping_carrier_districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_carrier_id')->constrained('shipping_carriers')->cascadeOnDelete();
            $table->string('external_id');
            $table->string('city')->nullable();
            $table->string('district_name');
            $table->string('arabic_name')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('estimated_delivery', 50)->nullable();
            $table->boolean('is_pickup')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['shipping_carrier_id', 'external_id'], 'carrier_district_external_unique');
        });

        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->foreignId('shipping_carrier_id')
                ->nullable()
                ->after('carrier')
                ->constrained('shipping_carriers')
                ->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('shipping_method_id')
                ->nullable()
                ->after('shipping_method')
                ->constrained('shipping_methods')
                ->nullOnDelete();
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->string('external_reference')->nullable()->after('tracking_number')->index();
            $table->string('external_status', 80)->nullable()->after('external_reference');
            $table->string('label_url')->nullable()->after('tracking_url');
            $table->json('external_payload')->nullable()->after('label_url');
            $table->timestamp('last_provider_sync_at')->nullable()->after('external_payload');
            $table->text('provider_error')->nullable()->after('last_provider_sync_at');
        });

        $now = now();

        $defaults = [
            ['code' => 'amana', 'name' => 'Amana', 'provider' => 'manual', 'is_enabled' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'sendit', 'name' => 'Sendit', 'provider' => 'sendit', 'is_enabled' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'aramex', 'name' => 'Aramex', 'provider' => 'manual', 'is_enabled' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'fedex', 'name' => 'FedEx', 'provider' => 'manual', 'is_enabled' => true, 'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($defaults as $carrier) {
            DB::table('shipping_carriers')->updateOrInsert(
                ['code' => $carrier['code']],
                $carrier
            );
        }

        $existingNames = DB::table('shipping_methods')
            ->whereNotNull('carrier')
            ->pluck('carrier')
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        foreach ($existingNames as $name) {
            $existingId = DB::table('shipping_carriers')
                ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
                ->value('id');

            if ($existingId) {
                continue;
            }

            $baseCode = Str::slug($name);
            $code = $baseCode;
            $counter = 1;

            while (DB::table('shipping_carriers')->where('code', $code)->exists()) {
                $code = $baseCode.'-'.$counter++;
            }

            DB::table('shipping_carriers')->insert([
                'code' => $code,
                'name' => $name,
                'provider' => $code === 'sendit' ? 'sendit' : 'manual',
                'is_enabled' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $carriersByName = DB::table('shipping_carriers')
            ->select('id', 'name')
            ->get()
            ->mapWithKeys(fn ($carrier) => [Str::lower((string) $carrier->name) => (int) $carrier->id]);

        DB::table('shipping_methods')
            ->select('id', 'carrier')
            ->orderBy('id')
            ->chunkById(100, function ($methods) use ($carriersByName): void {
                foreach ($methods as $method) {
                    $carrierName = trim((string) $method->carrier);

                    if ($carrierName === '') {
                        continue;
                    }

                    $carrierId = $carriersByName[Str::lower($carrierName)] ?? null;

                    if (! $carrierId) {
                        continue;
                    }

                    DB::table('shipping_methods')
                        ->where('id', $method->id)
                        ->update(['shipping_carrier_id' => $carrierId]);
                }
            });

        DB::table('shipping_methods')
            ->select('id', 'name', 'slug')
            ->orderBy('id')
            ->chunkById(100, function ($methods): void {
                foreach ($methods as $method) {
                    DB::table('orders')
                        ->whereNull('shipping_method_id')
                        ->where(function ($query) use ($method): void {
                            $query->where('shipping_method', $method->name)
                                ->orWhere('shipping_method', $method->slug);
                        })
                        ->update(['shipping_method_id' => $method->id]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn([
                'external_reference',
                'external_status',
                'label_url',
                'external_payload',
                'last_provider_sync_at',
                'provider_error',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipping_method_id');
        });

        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipping_carrier_id');
        });

        Schema::dropIfExists('shipping_carrier_districts');
        Schema::dropIfExists('shipping_carriers');
    }
};
