<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_transaction_id')->nullable()->constrained('payment_transactions')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('gateway', 50)->index();                 // 'cmi', 'stripe', 'payzone', 'offline_transfer'
            $table->enum('direction', ['outbound', 'inbound']);      // outbound = we sent, inbound = we received
            $table->enum('event_type', [
                'initiation',       // Payment request sent to gateway
                'callback',         // Synchronous browser redirect from gateway
                'webhook',          // Asynchronous server-to-server notification
                'verification',     // Signature/hash verification attempt
                'refund_request',   // Refund initiated
                'refund_response',  // Refund result received
                'status_change',    // Internal status transition
                'error',            // Caught exception or failure
            ]);
            $table->string('status_before', 30)->nullable();         // PaymentStatus value before transition
            $table->string('status_after', 30)->nullable();          // PaymentStatus value after transition
            $table->boolean('is_successful')->default(false);
            $table->string('gateway_reference')->nullable()->index();
            $table->json('request_payload')->nullable();             // What we sent (redacted)
            $table->json('response_payload')->nullable();            // What we received (redacted)
            $table->text('error_message')->nullable();
            $table->string('error_code', 100)->nullable();
            $table->string('ip_address', 45)->nullable();            // Source IP for webhook forensics
            $table->string('user_agent')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->unsignedInteger('response_time_ms')->nullable(); // Gateway response latency
            $table->timestamps();

            // Composite index for efficient admin queries
            $table->index(['gateway', 'event_type', 'created_at']);
            $table->index(['order_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
    }
};
