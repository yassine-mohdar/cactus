<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ───────────────────────────────────────────────────────
        // Shipping Methods — admin-configurable delivery options
        // ───────────────────────────────────────────────────────
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');                               // "Standard Delivery", "Express", "Same-Day"
            $table->string('slug')->unique();                     // "standard-delivery"
            $table->string('carrier')->nullable();                // "Amana", "DHL", "Chronopost", "Internal"
            $table->text('description')->nullable();
            $table->decimal('base_cost', 10, 2)->default(0);      // Base flat rate
            $table->decimal('free_shipping_threshold', 10, 2)->nullable(); // Free above this amount
            $table->string('estimated_days', 30)->nullable();     // "2-4 business days"
            $table->boolean('is_enabled')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();                 // Carrier-specific config (API keys, zone maps, etc.)
            $table->timestamps();
        });

        // ───────────────────────────────────────────────────────
        // Shipments — one per order (or per split fulfillment)
        // ───────────────────────────────────────────────────────
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('shipping_method_id')->nullable()->constrained('shipping_methods')->nullOnDelete();
            $table->string('status', 30)->default('pending')->index();

            // Carrier & tracking
            $table->string('carrier_name', 100)->nullable();       // Denormalized from shipping_method for immutability
            $table->string('carrier_service', 100)->nullable();    // "Express", "Economy"
            $table->string('tracking_number')->nullable()->index();
            $table->string('tracking_url')->nullable();            // Full URL to carrier tracking page

            // Fulfillment metadata
            $table->decimal('weight', 8, 2)->nullable();           // kg
            $table->string('dimensions', 50)->nullable();          // "30x20x15 cm"
            $table->unsignedInteger('package_count')->default(1);

            // Timestamps for each state
            $table->timestamp('packed_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('returned_at')->nullable();

            // Issue tracking
            $table->boolean('has_delivery_issue')->default(false);
            $table->text('delivery_issue_notes')->nullable();
            $table->string('failure_reason')->nullable();          // "Customer absent", "Wrong address", "Refused"

            // Staff attribution
            $table->foreignId('packed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('dispatched_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('internal_notes')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });

        // ───────────────────────────────────────────────────────
        // Shipment Status History — immutable audit trail
        // ───────────────────────────────────────────────────────
        Schema::create('shipment_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->string('status_from', 30)->nullable();
            $table->string('status_to', 30);
            $table->text('notes')->nullable();                     // "Customer was not home"
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('changed_by_name')->nullable();         // Denormalized for audit readability
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['shipment_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_status_history');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('shipping_methods');
    }
};
