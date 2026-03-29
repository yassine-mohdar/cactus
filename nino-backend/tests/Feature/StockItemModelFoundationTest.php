<?php

namespace Tests\Feature;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockItemModelFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_item_model_exposes_status_helpers_and_available_quantity(): void
    {
        $product = Product::factory()->published()->create([
            'sku' => 'STOCK-MODEL-001',
        ]);

        $stockItem = StockItem::create([
            'product_id' => $product->id,
            'quantity' => 12,
            'reserved_quantity' => 5,
            'low_stock_threshold' => 4,
            'status' => StockItem::STATUS_LOW_STOCK,
        ]);

        $this->assertSame(7, $stockItem->available_quantity);
        $this->assertTrue($stockItem->isLowStock());
        $this->assertFalse($stockItem->isInStock());
        $this->assertFalse($stockItem->isOutOfStock());
        $this->assertSame('STOCK-MODEL-001', $stockItem->sku);
        $this->assertSame((float) $product->price, $stockItem->unit_price);
    }

    public function test_stock_item_scopes_filter_by_status(): void
    {
        $product = Product::factory()->published()->create();

        $inStock = StockItem::create([
            'product_id' => $product->id,
            'quantity' => 8,
            'reserved_quantity' => 0,
            'low_stock_threshold' => 2,
            'status' => StockItem::STATUS_IN_STOCK,
        ]);

        $lowStock = StockItem::create([
            'product_id' => $product->id,
            'quantity' => 3,
            'reserved_quantity' => 0,
            'low_stock_threshold' => 5,
            'status' => StockItem::STATUS_LOW_STOCK,
        ]);

        $outOfStock = StockItem::create([
            'product_id' => $product->id,
            'quantity' => 0,
            'reserved_quantity' => 0,
            'low_stock_threshold' => 5,
            'status' => StockItem::STATUS_OUT_OF_STOCK,
        ]);

        $this->assertSame([$inStock->id], StockItem::query()->inStock()->pluck('id')->all());
        $this->assertSame([$lowStock->id], StockItem::query()->lowStock()->pluck('id')->all());
        $this->assertSame([$outOfStock->id], StockItem::query()->outOfStock()->pluck('id')->all());
    }

    public function test_stock_item_supports_product_level_and_variant_level_stock(): void
    {
        $product = Product::factory()->published()->create();
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'VAR-STOCK-001',
            'price' => 199.90,
            'sale_price' => null,
            'quantity' => 4,
        ]);

        $productLevel = StockItem::factory()->create([
            'product_id' => $product->id,
            'product_variant_id' => null,
        ]);

        $variantLevel = StockItem::factory()->variantLevel($variant)->create();

        $this->assertTrue($productLevel->isProductLevel());
        $this->assertFalse($productLevel->isVariantLevel());
        $this->assertSame('Product stock', $productLevel->stockLevelLabel());

        $this->assertTrue($variantLevel->isVariantLevel());
        $this->assertFalse($variantLevel->isProductLevel());
        $this->assertSame('Variant stock', $variantLevel->stockLevelLabel());

        $this->assertSame([$productLevel->id], StockItem::query()->productLevel()->pluck('id')->all());
        $this->assertSame([$variantLevel->id], StockItem::query()->variantLevel()->pluck('id')->all());
    }

    public function test_stock_item_supports_branch_aware_and_global_stock_scopes(): void
    {
        $branch = Organization::factory()->create([
            'type' => Organization::TYPE_BRANCH,
            'status' => Organization::STATUS_ACTIVE,
        ]);

        $global = StockItem::factory()->create();
        $branchScoped = StockItem::factory()->forBranch($branch)->create();

        $this->assertTrue($global->isGlobalStock());
        $this->assertFalse($global->isBranchAware());
        $this->assertSame('Global stock', $global->stockScopeLabel());

        $this->assertTrue($branchScoped->isBranchAware());
        $this->assertFalse($branchScoped->isGlobalStock());
        $this->assertSame('Branch stock', $branchScoped->stockScopeLabel());
        $this->assertSame($branch->id, $branchScoped->branch?->id);

        $this->assertSame([$global->id], StockItem::query()->globalStock()->pluck('id')->all());
        $this->assertSame([$branchScoped->id], StockItem::query()->forBranch($branch->id)->pluck('id')->all());
    }

    public function test_stock_item_exposes_reservation_ready_helpers(): void
    {
        $stockItem = StockItem::factory()->create([
            'quantity' => 10,
            'reserved_quantity' => 4,
            'low_stock_threshold' => 3,
            'status' => StockItem::STATUS_IN_STOCK,
        ]);

        $this->assertTrue($stockItem->hasReservations());
        $this->assertSame(6, $stockItem->reservableQuantity());
        $this->assertTrue($stockItem->canReserve(6));
        $this->assertFalse($stockItem->canReserve(7));
        $this->assertTrue($stockItem->canRelease(4));
        $this->assertFalse($stockItem->canRelease(5));
    }
}
