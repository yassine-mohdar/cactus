<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_carriers', function (Blueprint $table) {
            // Encrypted casts store opaque ciphertext strings, not valid JSON documents.
            $table->text('credentials')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('shipping_carriers', function (Blueprint $table) {
            $table->json('credentials')->nullable()->change();
        });
    }
};
