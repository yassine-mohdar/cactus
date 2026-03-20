<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // P1-USERS-02: User type (staff/customer distinction)
            $table->enum('type', ['staff', 'customer'])->default('staff')->after('name');

            // P1-USERS-05: Active/inactive/suspended states
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('type');

            // P1-USERS-03: Profile attributes
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');

            // P1-USERS-06: Last login tracking
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');

            // P1-ORG-04: Organization assignment
            $table->unsignedBigInteger('organization_id')->nullable()->after('status');
            $table->enum('organization_scope', ['platform', 'franchise', 'branch', 'own'])->nullable()->after('organization_id');

            // Indexes
            $table->index('type');
            $table->index('status');
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['status']);
            $table->dropIndex(['organization_id']);
            $table->dropColumn([
                'type', 'status', 'phone', 'avatar',
                'last_login_at', 'last_login_ip',
                'organization_id', 'organization_scope',
            ]);
        });
    }
};
