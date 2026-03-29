<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockItem>
 */
class StockItemFactory extends Factory
{
    protected $model = StockItem::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'branch_id' => null,
            'quantity' => fake()->numberBetween(0, 100),
            'reserved_quantity' => 0,
            'low_stock_threshold' => 10,
            'status' => StockItem::STATUS_IN_STOCK,
        ];
    }

    public function variantLevel(?ProductVariant $variant = null): self
    {
        return $this->state(function () use ($variant): array {
            $resolvedVariant = $variant ?? ProductVariant::query()->create([
                'product_id' => Product::factory()->create()->id,
                'sku' => strtoupper(fake()->unique()->bothify('VAR-####')),
                'price' => fake()->randomFloat(2, 10, 999),
                'sale_price' => null,
                'quantity' => fake()->numberBetween(0, 50),
            ]);

            return [
                'product_id' => $resolvedVariant->product_id,
                'product_variant_id' => $resolvedVariant->id,
            ];
        });
    }

    public function forBranch(?Organization $branch = null): self
    {
        return $this->state(function () use ($branch): array {
            $resolvedBranch = $branch ?? Organization::factory()->create([
                'type' => Organization::TYPE_BRANCH,
                'status' => Organization::STATUS_ACTIVE,
            ]);

            return [
                'branch_id' => $resolvedBranch->id,
            ];
        });
    }

    public function reserved(int $quantity = 3): self
    {
        return $this->state(fn () => [
            'reserved_quantity' => $quantity,
        ]);
    }
}
