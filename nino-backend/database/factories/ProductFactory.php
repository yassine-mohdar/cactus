<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'type' => 'simple',
            'status' => 'draft',
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####')),
            'barcode' => null,
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 10, 999),
            'sale_price' => null,
            'cost_price' => fake()->randomFloat(2, 5, 500),
            'quantity' => fake()->numberBetween(0, 200),
            'weight' => fake()->randomFloat(2, 0.1, 10),
            'length' => fake()->randomFloat(2, 1, 100),
            'width' => fake()->randomFloat(2, 1, 100),
            'height' => fake()->randomFloat(2, 1, 100),
            'is_featured' => false,
            'meta_title' => null,
            'meta_description' => null,
        ];
    }

    public function simple(): self
    {
        return $this->state(fn () => [
            'type' => Product::TYPE_SIMPLE,
        ]);
    }

    public function variable(): self
    {
        return $this->state(fn () => [
            'type' => Product::TYPE_VARIABLE,
            'price' => null,
            'sale_price' => null,
            'quantity' => 0,
        ]);
    }

    public function published(): self
    {
        return $this->state(fn () => [
            'status' => Product::STATUS_PUBLISHED,
        ]);
    }

    public function draft(): self
    {
        return $this->state(fn () => [
            'status' => Product::STATUS_DRAFT,
        ]);
    }

    public function archived(): self
    {
        return $this->state(fn () => [
            'status' => Product::STATUS_ARCHIVED,
        ]);
    }

    public function featured(): self
    {
        return $this->state(fn () => [
            'is_featured' => true,
        ]);
    }

    public function onSale(): self
    {
        return $this->state(fn () => [
            'price' => 199.90,
            'sale_price' => 149.90,
        ]);
    }
}
