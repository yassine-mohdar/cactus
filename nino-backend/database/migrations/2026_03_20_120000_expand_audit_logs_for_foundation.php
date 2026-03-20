<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('actor_type')->nullable()->after('user_id');
            $table->string('actor_name')->nullable()->after('actor_type');
            $table->string('actor_email')->nullable()->after('actor_name');
            $table->string('target_label')->nullable()->after('auditable_id');
            $table->json('context')->nullable()->after('new_values');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn([
                'actor_type',
                'actor_name',
                'actor_email',
                'target_label',
                'context',
            ]);
        });
    }
};
