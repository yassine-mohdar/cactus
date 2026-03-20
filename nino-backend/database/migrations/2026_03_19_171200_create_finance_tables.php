<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop legacy tables if they exist — replacing with comprehensive schema
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Schema::dropIfExists('payment_transactions');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ───────────────────────────────────────────────────────
        // Payment Transactions — central financial ledger
        // ───────────────────────────────────────────────────────
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 64)->unique();                // Unique transaction reference
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->unsignedBigInteger('customer_id')->nullable();    // No FK — customers table may not exist yet

            // Type & Status
            $table->string('type', 20)->default('payment');           // payment|refund|partial_refund|chargeback|adjustment|fee
            $table->string('status', 20)->default('pending');         // pending|completed|failed|cancelled|refunded

            // Payment method
            $table->string('payment_method', 30)->default('other');   // cod|credit_card|debit_card|bank_transfer|paypal|stripe|wallet|other
            $table->string('gateway', 50)->nullable();                // e.g. stripe, paypal, cmi
            $table->string('gateway_transaction_id')->nullable();     // External reference

            // Amounts
            $table->decimal('amount', 12, 2);                        // Transaction amount
            $table->decimal('fee_amount', 10, 2)->default(0);        // Gateway/processing fee
            $table->decimal('net_amount', 12, 2);                    // amount - fee
            $table->string('currency', 3)->default('MAD');

            // COD-specific
            $table->string('cod_status', 20)->nullable();             // pending|collected|deposited|reconciled|discrepancy
            $table->timestamp('cod_collected_at')->nullable();
            $table->timestamp('cod_deposited_at')->nullable();
            $table->string('cod_collected_by')->nullable();           // driver/agent name
            $table->decimal('cod_collected_amount', 10, 2)->nullable(); // For discrepancy tracking
            $table->text('cod_notes')->nullable();

            // Financial snapshot
            $table->decimal('order_subtotal', 10, 2)->nullable();
            $table->decimal('order_discount', 10, 2)->nullable();
            $table->decimal('order_shipping', 10, 2)->nullable();
            $table->decimal('order_tax', 10, 2)->nullable();
            $table->decimal('order_total', 10, 2)->nullable();

            // Metadata
            $table->json('metadata')->nullable();                    // Extra gateway response data
            $table->text('failure_reason')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['order_id', 'type']);
            $table->index(['status', 'payment_method']);
            $table->index(['created_at']);
            $table->index(['cod_status']);
            $table->index(['customer_id']);
        });

        // ───────────────────────────────────────────────────────
        // Refund Requests
        // ───────────────────────────────────────────────────────
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 64)->unique();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id')->nullable();    // No FK — customers table may not exist yet
            $table->foreignId('transaction_id')->nullable()->constrained('payment_transactions')->nullOnDelete();

            // Status
            $table->string('status', 20)->default('requested');       // requested|approved|processing|completed|rejected

            // Amounts
            $table->decimal('amount', 10, 2);
            $table->decimal('original_order_total', 10, 2);
            $table->string('currency', 3)->default('MAD');

            // Reason & details
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->json('items')->nullable();                        // Specific items being refunded

            // Processing
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
        Schema::dropIfExists('payment_transactions');
    }
};
