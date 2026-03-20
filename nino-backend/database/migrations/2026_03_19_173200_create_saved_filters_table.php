<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_filters', function (Blueprint $table) {
            $table->id();
            $table->string('name');                               // User-defined name
            $table->string('module', 50);                         // e.g. orders, products, transactions
            $table->json('filters');                               // Serialized filter state
            $table->boolean('is_default')->default(false);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['module', 'created_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_filters');
    }
};
