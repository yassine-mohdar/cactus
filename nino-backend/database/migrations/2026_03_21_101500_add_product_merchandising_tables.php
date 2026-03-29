<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'badge_labels')) {
                $table->json('badge_labels')->nullable()->after('is_featured');
            }
        });

        if (! Schema::hasTable('product_related_products')) {
            Schema::create('product_related_products', function (Blueprint $table) {
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('related_product_id')->constrained('products')->cascadeOnDelete();
                $table->primary(['product_id', 'related_product_id']);
            });
        }

        if (! Schema::hasTable('product_upsells')) {
            Schema::create('product_upsells', function (Blueprint $table) {
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('upsell_product_id')->constrained('products')->cascadeOnDelete();
                $table->primary(['product_id', 'upsell_product_id']);
            });
        }

        if (! Schema::hasTable('product_cross_sells')) {
            Schema::create('product_cross_sells', function (Blueprint $table) {
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('cross_sell_product_id')->constrained('products')->cascadeOnDelete();
                $table->primary(['product_id', 'cross_sell_product_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_cross_sells');
        Schema::dropIfExists('product_upsells');
        Schema::dropIfExists('product_related_products');

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'badge_labels')) {
                $table->dropColumn('badge_labels');
            }
        });
    }
};
