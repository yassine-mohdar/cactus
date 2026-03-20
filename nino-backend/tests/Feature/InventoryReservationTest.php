<?php

namespace Tests\Feature;

use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reserve_stock_updates_reserved_quantity_records_a_movement_and_recalculates_status(): void
    {
        $stockItem = $this->createStockItem(quantity: 10, reservedQuantity: 0, lowStockThreshold: 5, status: 'in_stock');

        $movement = app(InventoryService::class)->reserveStock(
            $stockItem,
            6,
            userId: null,
            referenceType: 'order',
            referenceId: 'ORD-RESERVE-001',
        );

        $stockItem->refresh();

        $this->assertSame(6, $stockItem->reserved_quantity);
        $this->assertSame(4, $stockItem->available_quantity);
        $this->assertSame('low_stock', $stockItem->status);
        $this->assertSame('reservation', $movement->type);
        $this->assertSame('order_reserved', $movement->reason);
        $this->assertSame('order', $movement->reference_type);
        $this->assertSame('ORD-RESERVE-001', $movement->reference_id);
        $this->assertSame('Reserved 6 units.', $movement->notes);
    }

    public function test_reserve_stock_throws_when_available_stock_is_insufficient(): void
    {
        $stockItem = $this->createStockItem(quantity: 5, reservedQuantity: 2, lowStockThreshold: 1, status: 'in_stock');

        try {
            app(InventoryService::class)->reserveStock($stockItem, 4);
            $this->fail('Expected an insufficient stock exception to be thrown.');
        } catch (InsufficientStockException $exception) {
            $this->assertStringContainsString('Insufficient stock', $exception->getMessage());
        }

        $stockItem->refresh();

        $this->assertSame(2, $stockItem->reserved_quantity);
        $this->assertSame(3, $stockItem->available_quantity);
        $this->assertSame(0, StockMovement::count());
    }

    public function test_release_stock_caps_the_release_amount_at_reserved_quantity_and_restores_status(): void
    {
        $stockItem = $this->createStockItem(quantity: 10, reservedQuantity: 6, lowStockThreshold: 5, status: 'low_stock');

        $movement = app(InventoryService::class)->releaseStock(
            $stockItem,
            10,
            userId: null,
            referenceType: 'order',
            referenceId: 'ORD-RELEASE-001',
        );

        $stockItem->refresh();

        $this->assertSame(0, $stockItem->reserved_quantity);
        $this->assertSame(10, $stockItem->available_quantity);
        $this->assertSame('in_stock', $stockItem->status);
        $this->assertSame('release', $movement->type);
        $this->assertSame('order_cancelled', $movement->reason);
        $this->assertSame('Released 6 reserved units.', $movement->notes);
    }

    private function createStockItem(
        int $quantity,
        int $reservedQuantity,
        int $lowStockThreshold,
        string $status,
    ): StockItem {
        $product = Product::create([
            'name' => 'Inventory Reservation Product ' . uniqid(),
            'slug' => 'inventory-reservation-product-' . uniqid(),
            'type' => 'simple',
            'status' => 'published',
            'sku' => 'INV-' . strtoupper(substr(uniqid(), -8)),
            'price' => 99.99,
            'quantity' => $quantity,
        ]);

        return StockItem::create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'reserved_quantity' => $reservedQuantity,
            'low_stock_threshold' => $lowStockThreshold,
            'status' => $status,
        ]);
    }
}
