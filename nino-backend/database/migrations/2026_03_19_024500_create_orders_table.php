<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            
            // P7-ORDER-05: Internal reference generation
            $table->string('reference_number')->unique()->index();
            
            // Guest orders might have null customer initially, or just link directly
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            
            // P7-STATUS-*
            $table->string('status')->default('pending')->index();
            
            // P7-ORDER-04: Pricing Summary
            $table->string('currency', 3)->default('MAD');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);

            $table->string('payment_method')->nullable();
            $table->string('shipping_method')->nullable();

            $table->text('customer_notes')->nullable();
            $table->text('admin_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
