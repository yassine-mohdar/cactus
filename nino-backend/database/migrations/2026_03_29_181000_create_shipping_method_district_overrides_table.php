<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shipping_method_district_overrides')) {
            return;
        }

        Schema::create('shipping_method_district_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_method_id')->constrained('shipping_methods')->cascadeOnDelete();
            $table->foreignId('shipping_carrier_district_id')->constrained('shipping_carrier_districts')->cascadeOnDelete();
            $table->decimal('forced_price', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['shipping_method_id', 'shipping_carrier_district_id'], 'shipping_method_district_override_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_method_district_overrides');
    }
};
