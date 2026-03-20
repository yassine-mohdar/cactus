<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('community_auto_invite_to_default_group')
                ->default(true)
                ->after('marketing_opt_in');
            $table->timestamp('community_default_group_invited_at')
                ->nullable()
                ->after('community_auto_invite_to_default_group');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'community_auto_invite_to_default_group',
                'community_default_group_invited_at',
            ]);
        });
    }
};
