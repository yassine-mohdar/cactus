<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            
            $table->string('gateway'); // 'stripe', 'cmi', 'payzone', 'offline'
            $table->string('status'); // 'pending', 'authorized', 'captured', 'failed', 'refunded'
            
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('MAD');
            
            $table->string('gateway_reference')->nullable()->index(); // ID provided by Stripe or CMI
            
            $table->json('payload')->nullable(); // Store the raw webhook payload for debugging
            $table->string('error_code')->nullable(); // To track specific failures
            $table->text('error_message')->nullable(); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
