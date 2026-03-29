<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->boolean('noindex')->default(false)->after('og_image');
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->string('canonical_url')->nullable()->after('meta_description');
            $table->string('og_title')->nullable()->after('canonical_url');
            $table->text('og_description')->nullable()->after('og_title');
            $table->string('og_image')->nullable()->after('og_description');
            $table->boolean('noindex')->default(false)->after('og_image');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('noindex');
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropColumn([
                'canonical_url',
                'og_title',
                'og_description',
                'og_image',
                'noindex',
            ]);
        });
    }
};
