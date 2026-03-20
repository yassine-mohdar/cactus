<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('created_at', 'orders_created_at_idx');
            $table->index(['status', 'created_at'], 'orders_status_created_at_idx');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->index(['status', 'updated_at'], 'shipments_status_updated_at_idx');
            $table->index(['has_delivery_issue', 'status', 'updated_at'], 'shipments_issue_status_updated_idx');
        });

        Schema::table('notification_logs', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'notification_logs_status_created_idx');
        });

        Schema::table('payment_logs', function (Blueprint $table) {
            $table->index(['event_type', 'created_at'], 'payment_logs_event_created_idx');
        });

        Schema::table('observability_events', function (Blueprint $table) {
            $table->index(['event_type', 'occurred_at'], 'observability_events_type_occurred_idx');
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->index(['queue', 'reserved_at', 'available_at'], 'jobs_queue_reserve_available_idx');
        });

        Schema::table('job_batches', function (Blueprint $table) {
            $table->index('finished_at', 'job_batches_finished_at_idx');
        });

        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->index('failed_at', 'failed_jobs_failed_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->dropIndex('failed_jobs_failed_at_idx');
        });

        Schema::table('job_batches', function (Blueprint $table) {
            $table->dropIndex('job_batches_finished_at_idx');
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex('jobs_queue_reserve_available_idx');
        });

        Schema::table('observability_events', function (Blueprint $table) {
            $table->dropIndex('observability_events_type_occurred_idx');
        });

        Schema::table('payment_logs', function (Blueprint $table) {
            $table->dropIndex('payment_logs_event_created_idx');
        });

        Schema::table('notification_logs', function (Blueprint $table) {
            $table->dropIndex('notification_logs_status_created_idx');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex('shipments_issue_status_updated_idx');
            $table->dropIndex('shipments_status_updated_at_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_status_created_at_idx');
            $table->dropIndex('orders_created_at_idx');
        });
    }
};
