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
        Schema::create('gateway_settings', function (Blueprint $table) {
            $table->id();
            $table->string('gateway_id', 50)->unique(); // e.g., 'cmi', 'stripe', 'offline_transfer'
            $table->string('name'); // e.g., 'Bank Transfer', 'Credit Card (CMI)'
            $table->boolean('is_enabled')->default(false);
            $table->enum('mode', ['test', 'live'])->default('test');
            
            // Scalable storage for varying gateway requirements (e.g. CMI needs store_id, client_id, hash_key. Offline needs instruction_html)
            // Using encrypted array casting at the model level to protect secrets at rest
            $table->text('credentials')->nullable(); 
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gateway_settings');
    }
};
