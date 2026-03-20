<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ───────────────────────────────────────────────────────
        // Coupons — core promotion engine
        // ───────────────────────────────────────────────────────
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');                                 // Admin-friendly name
            $table->text('description')->nullable();

            // Type & Value
            $table->string('type', 20)->default('percentage');      // fixed | percentage
            $table->decimal('value', 10, 2);                       // Amount or %
            $table->decimal('max_discount', 10, 2)->nullable();    // Cap for percentage type

            // Status
            $table->boolean('is_active')->default(true);

            // Dates
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            // Usage limits
            $table->unsignedInteger('usage_limit')->nullable();    // Total uses allowed
            $table->unsignedInteger('usage_count')->default(0);    // Current usage
            $table->unsignedInteger('per_user_limit')->nullable(); // Per customer cap

            // Cart requirements
            $table->decimal('minimum_cart_total', 10, 2)->nullable();
            $table->decimal('maximum_cart_total', 10, 2)->nullable();
            $table->unsignedInteger('minimum_items')->nullable();

            // Targeting — JSON arrays of IDs
            $table->json('product_ids')->nullable();               // Apply only to these products
            $table->json('category_ids')->nullable();              // Apply only to these categories
            $table->json('excluded_product_ids')->nullable();      // Never apply to these products
            $table->json('excluded_category_ids')->nullable();     // Never apply to these categories
            $table->json('customer_ids')->nullable();              // Limit to specific customers

            // Stackability
            $table->boolean('is_stackable')->default(false);       // Can combine with other coupons
            $table->json('stackable_with')->nullable();            // IDs of coupons this can stack with

            // Meta
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        // ───────────────────────────────────────────────────────
        // Coupon Usage — per-user tracking
        // ───────────────────────────────────────────────────────
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('discount_amount', 10, 2);             // Actual discount applied
            $table->decimal('order_total_before', 10, 2);          // Cart total before discount
            $table->decimal('order_total_after', 10, 2);           // Cart total after discount
            $table->timestamps();

            $table->index(['coupon_id', 'customer_id']);
        });

        // ───────────────────────────────────────────────────────
        // Abandoned Carts — recovery foundation
        // ───────────────────────────────────────────────────────
        Schema::create('abandoned_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_id')->nullable()->index();
            $table->string('email')->nullable();

            // Cart snapshot
            $table->json('cart_items');                             // Array of {product_id, variant_id, qty, price}
            $table->decimal('cart_total', 10, 2);
            $table->unsignedInteger('items_count');
            $table->string('currency', 3)->default('MAD');

            // Recovery state
            $table->string('status', 20)->default('abandoned');    // abandoned | notified | recovered | expired
            $table->unsignedTinyInteger('notification_count')->default(0);
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamp('recovered_at')->nullable();
            $table->foreignId('recovered_order_id')->nullable()->constrained('orders')->nullOnDelete();

            // Timing
            $table->timestamp('abandoned_at')->useCurrent();
            $table->timestamps();

            $table->index(['status', 'abandoned_at']);
            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abandoned_carts');
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupons');
    }
};
