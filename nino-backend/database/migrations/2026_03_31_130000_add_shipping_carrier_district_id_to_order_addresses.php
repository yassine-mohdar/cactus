<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_addresses', function (Blueprint $table): void {
            if (! Schema::hasColumn('order_addresses', 'shipping_carrier_district_id')) {
                $table->foreignId('shipping_carrier_district_id')
                    ->nullable()
                    ->after('country')
                    ->constrained('shipping_carrier_districts')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_addresses', function (Blueprint $table): void {
            if (Schema::hasColumn('order_addresses', 'shipping_carrier_district_id')) {
                $table->dropConstrainedForeignId('shipping_carrier_district_id');
            }
        });
    }
};
