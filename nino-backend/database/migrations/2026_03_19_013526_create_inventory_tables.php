<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table to track physical and reserved stock per product/variant per branch
        Schema::create('inventory_stock_items', function (Blueprint $table) {
            $table->id();
            
            // Link to the stockable entity (Product or ProductVariant)
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            
            // Link to physical location (branch). Null means global/central warehouse.
            $table->foreignId('branch_id')->nullable()->constrained('organizations')->nullOnDelete();
            
            // Stock levels
            $table->integer('quantity')->default(0)->comment('Actual physical stock level');
            $table->integer('reserved_quantity')->default(0)->comment('Stock reserved during checkout/processing');
            $table->integer('low_stock_threshold')->default(10);
            
            $table->enum('status', ['in_stock', 'low_stock', 'out_of_stock'])->default('out_of_stock');
            
            $table->timestamps();
            
            // Ensure unique tracking per product/variant per branch
            $table->unique(['product_id', 'product_variant_id', 'branch_id'], 'unique_stock_item');
        });

        // Table to track all inventory adjustments securely (audit log for stock)
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_item_id')->constrained('inventory_stock_items')->cascadeOnDelete();
            
            // Who performed the action
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Nature of the movement
            $table->enum('type', ['addition', 'deduction', 'reservation', 'release', 'set']);
            
            // Business reason
            $table->string('reason', 50)->comment('e.g., restock, sale, return, damage, shrinkage, manual_adjustment');
            
            // Amounts
            $table->integer('quantity')->comment('The amount added/removed (can be negative for deductions)');
            $table->integer('quantity_before')->default(0);
            $table->integer('quantity_after')->default(0);
            
            // Polymorphic link to related business object (e.g., Order, Return, Shipment)
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_stock_items');
    }
};
