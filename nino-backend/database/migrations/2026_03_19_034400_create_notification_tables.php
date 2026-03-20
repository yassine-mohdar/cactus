<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ───────────────────────────────────────────────────────
        // Notification Templates — admin-managed message templates
        // ───────────────────────────────────────────────────────
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('event', 50)->index();              // NotificationEvent enum value
            $table->string('channel', 20);                     // NotificationChannel enum value
            $table->string('name');                            // Human-readable name "Order Placed - Email"
            $table->string('subject')->nullable();             // Email subject line (null for SMS/WhatsApp)
            $table->text('body');                              // Template body with {{placeholders}}
            $table->boolean('is_enabled')->default(true);
            $table->json('metadata')->nullable();              // Extra config per template (sender name, etc.)
            $table->timestamps();

            $table->unique(['event', 'channel']);
        });

        // ───────────────────────────────────────────────────────
        // Notification Logs — immutable delivery history
        // ───────────────────────────────────────────────────────
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->nullable()->constrained('notification_templates')->nullOnDelete();
            $table->string('event', 50)->index();
            $table->string('channel', 20);
            $table->string('status', 20)->default('queued')->index();

            // Recipient
            $table->string('recipient');                       // Email address, phone number, etc.
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();

            // Content snapshot (immutable record of what was sent)
            $table->string('subject')->nullable();
            $table->text('body')->nullable();

            // Context
            $table->string('order_reference')->nullable();
            $table->json('variables')->nullable();             // The variables that were substituted

            // Delivery tracking
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->text('error_message')->nullable();
            $table->string('external_id')->nullable();         // Provider message ID (Twilio SID, etc.)
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'event']);
            $table->index(['status', 'next_retry_at']);
        });

        // ───────────────────────────────────────────────────────
        // Integration Settings — channel provider credentials
        // ───────────────────────────────────────────────────────
        Schema::create('integration_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50)->unique();          // 'smtp', 'twilio', 'whatsapp_api'
            $table->string('name');                            // Human-readable "SMTP Mail Server"
            $table->boolean('is_enabled')->default(false);
            $table->json('credentials')->nullable();           // Encrypted at model level
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_settings');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_templates');
    }
};
