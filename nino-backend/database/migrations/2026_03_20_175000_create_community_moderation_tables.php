<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('community_reports')) {
            Schema::create('community_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('group_id')->nullable()->constrained('community_groups')->nullOnDelete();
                $table->morphs('reportable');
                $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('reason', 40)->default('other')->index();
                $table->string('state', 30)->default('submitted')->index();
                $table->text('description')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('resolution_notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['group_id', 'state']);
                $table->index(['reporter_id', 'state']);
            });
        }

        if (! Schema::hasTable('community_moderation_queue_items')) {
            Schema::create('community_moderation_queue_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('group_id')->nullable()->constrained('community_groups')->nullOnDelete();
                $table->foreignId('report_id')->nullable()->unique()->constrained('community_reports')->nullOnDelete();
                $table->string('moderatable_type');
                $table->unsignedBigInteger('moderatable_id');
                $table->string('status', 30)->default('open')->index();
                $table->unsignedSmallInteger('priority')->default(100)->index();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('assigned_at')->nullable();
                $table->timestamp('queued_at')->nullable()->index();
                $table->timestamp('resolved_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['group_id', 'status']);
                $table->index(['assigned_to', 'status']);
                $table->index(['moderatable_type', 'moderatable_id'], 'cmqi_moderatable_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('community_moderation_queue_items');
        Schema::dropIfExists('community_reports');
    }
};
