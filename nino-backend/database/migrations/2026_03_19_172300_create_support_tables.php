<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ───────────────────────────────────────────────────────
        // Internal Notes — polymorphic, attachable to orders/customers/etc
        // ───────────────────────────────────────────────────────
        Schema::create('internal_notes', function (Blueprint $table) {
            $table->id();
            $table->morphs('notable');                               // notable_type + notable_id
            $table->text('content');
            $table->boolean('is_pinned')->default(false);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['notable_type', 'notable_id', 'created_at']);
        });

        // ───────────────────────────────────────────────────────
        // Activity Timeline — polymorphic audit trail
        // ───────────────────────────────────────────────────────
        Schema::create('activity_timeline', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');                               // subject_type + subject_id
            $table->string('action', 50);                            // e.g. status_changed, note_added, payment_received
            $table->string('description');                           // Human-readable description
            $table->json('metadata')->nullable();                    // old_value, new_value, extra context
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id', 'created_at']);
        });

        // ───────────────────────────────────────────────────────
        // Support Issues — queue for support attention
        // ───────────────────────────────────────────────────────
        Schema::create('support_issues', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 64)->unique();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_name')->nullable();

            // Classification
            $table->string('type', 20)->default('general');           // order|payment|shipping|refund|product|account|general
            $table->string('status', 20)->default('open');            // open|in_progress|waiting_customer|resolved|closed
            $table->string('priority', 10)->default('medium');        // low|medium|high|urgent

            // Content
            $table->string('subject');
            $table->text('description')->nullable();

            // Assignment
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index(['order_id']);
            $table->index(['customer_id']);
            $table->index(['assigned_to', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_issues');
        Schema::dropIfExists('activity_timeline');
        Schema::dropIfExists('internal_notes');
    }
};
