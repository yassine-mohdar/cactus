<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            
            // X-Cart-Session-Id header value for guests
            $table->string('session_id')->nullable()->unique()->index();
            
            // Authenticated customer
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            
            // Core constraints
            $table->string('currency', 3)->default('MAD');
            $table->string('coupon_code')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
