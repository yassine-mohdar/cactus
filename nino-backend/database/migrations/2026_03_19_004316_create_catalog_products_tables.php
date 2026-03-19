<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Core Products Table
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['simple', 'variable'])->default('simple');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            
            // Base properties
            $table->string('sku')->nullable()->unique();
            $table->string('barcode')->nullable()->unique();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            
            // Pricing (for simple products, or base price for variable)
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->decimal('cost_price', 10, 2)->nullable();
            
            // Inventory (for simple products)
            $table->integer('quantity')->default(0);
            
            // Physical Dimensions
            $table->decimal('weight', 8, 2)->nullable();
            $table->decimal('length', 8, 2)->nullable();
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('height', 8, 2)->nullable();
            
            // Merchandising & SEO
            $table->boolean('is_featured')->default(false);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });

        // Many-to-Many Categories Pivot
        Schema::create('category_product', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['category_id', 'product_id']);
        });

        // Product Images Gallery
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->string('alt_text')->nullable();
            $table->timestamps();
        });

        // Product Options (e.g., Size, Color)
        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g. "Size"
            $table->integer('position')->default(0);
            $table->timestamps();
            
            $table->unique(['product_id', 'name']);
        });

        // Product Option Values (e.g., Small, Medium, Large)
        Schema::create('product_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_option_id')->constrained()->cascadeOnDelete();
            $table->string('value'); // e.g. "Small"
            $table->integer('position')->default(0);
            $table->timestamps();
            
            $table->unique(['product_option_id', 'value']);
        });

        // Product Variants (The actual SKUs generated from Option Combinations)
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->decimal('price', 10, 2)->nullable(); // Optional override of base price
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->integer('quantity')->default(0); // Variant level stock
            $table->string('image_path')->nullable(); // Specific image for this variant
            $table->timestamps();
        });

        // Pivot connecting a Variant to its specific Option Values
        Schema::create('product_variant_option_value', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('option_value_id')->constrained('product_option_values')->cascadeOnDelete();
            
            $table->primary(['product_variant_id', 'option_value_id'], 'variant_option_value_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_option_value');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_option_values');
        Schema::dropIfExists('product_options');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('products');
    }
};
