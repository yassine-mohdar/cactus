<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50)->index();          // e.g. 'general', 'seo', 'smtp', 'security'
            $table->string('key', 100);                     // e.g. 'site_name', 'smtp_host'
            $table->text('value')->nullable();               // stored as text (JSON or plain)
            $table->string('type', 20)->default('string');   // string, boolean, integer, json, secret
            $table->boolean('is_encrypted')->default(false); // true for secrets
            $table->timestamps();

            $table->unique(['group', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
