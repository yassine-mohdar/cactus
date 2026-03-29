<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
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

    public function test_release_reservations_for_order_releases_matching_reserved_stock_items(): void
    {
        $customer = User::factory()->create([
            'type' => User::TYPE_CUSTOMER,
            'status' => User::STATUS_ACTIVE,
        ]);

        $product = Product::create([
            'name' => 'Reservation Release Product ' . uniqid(),
            'slug' => 'reservation-release-product-' . uniqid(),
            'type' => 'simple',
            'status' => 'published',
            'sku' => 'REL-' . strtoupper(substr(uniqid(), -8)),
            'price' => 49.99,
            'quantity' => 8,
        ]);

        $stockItem = StockItem::create([
            'product_id' => $product->id,
            'quantity' => 8,
            'reserved_quantity' => 3,
            'low_stock_threshold' => 2,
            'status' => 'low_stock',
        ]);

        $order = Order::create([
            'reference_number' => 'ORD-REL-' . strtoupper(substr(uniqid(), -6)),
            'customer_id' => $customer->id,
            'status' => OrderStatus::PENDING,
            'currency' => 'MAD',
            'subtotal' => 149.97,
            'shipping_total' => 0,
            'discount_total' => 0,
            'grand_total' => 149.97,
            'payment_method' => 'stripe',
            'shipping_method' => 'standard',
        ]);

        $order->lineItems()->create([
            'product_id' => $product->id,
            'variant_id' => null,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'unit_price' => 49.99,
            'quantity' => 3,
            'line_total' => 149.97,
        ]);

        $released = app(InventoryService::class)->releaseReservationsForOrder($order, 'payment_failed');

        $stockItem->refresh();

        $this->assertSame(1, $released);
        $this->assertSame(0, $stockItem->reserved_quantity);
        $this->assertSame(8, $stockItem->available_quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'stock_item_id' => $stockItem->id,
            'type' => 'release',
            'reason' => 'payment_failed',
            'reference_type' => Order::class,
            'reference_id' => (string) $order->id,
        ]);
    }

    public function test_release_expired_reservations_only_releases_stale_pending_orders(): void
    {
        $customer = User::factory()->create([
            'type' => User::TYPE_CUSTOMER,
            'status' => User::STATUS_ACTIVE,
        ]);

        $expiredStock = $this->createStockItem(quantity: 10, reservedQuantity: 4, lowStockThreshold: 3, status: 'in_stock');
        $freshStock = $this->createStockItem(quantity: 12, reservedQuantity: 5, lowStockThreshold: 3, status: 'in_stock');

        $expiredOrder = Order::create([
            'reference_number' => 'ORD-EXP-' . strtoupper(substr(uniqid(), -6)),
            'customer_id' => $customer->id,
            'status' => OrderStatus::PENDING,
            'currency' => 'MAD',
            'subtotal' => 100,
            'shipping_total' => 0,
            'discount_total' => 0,
            'grand_total' => 100,
            'payment_method' => 'stripe',
            'shipping_method' => 'standard',
        ]);

        $expiredOrder->forceFill([
            'created_at' => now()->subHours(3),
            'updated_at' => now()->subHours(3),
        ])->saveQuietly();

        $expiredOrder->lineItems()->create([
            'product_id' => $expiredStock->product_id,
            'variant_id' => null,
            'product_name' => $expiredStock->product->name,
            'sku' => $expiredStock->product->sku,
            'unit_price' => 25,
            'quantity' => 4,
            'line_total' => 100,
        ]);

        $freshOrder = Order::create([
            'reference_number' => 'ORD-FRESH-' . strtoupper(substr(uniqid(), -6)),
            'customer_id' => $customer->id,
            'status' => OrderStatus::PENDING,
            'currency' => 'MAD',
            'subtotal' => 125,
            'shipping_total' => 0,
            'discount_total' => 0,
            'grand_total' => 125,
            'payment_method' => 'stripe',
            'shipping_method' => 'standard',
        ]);

        $freshOrder->lineItems()->create([
            'product_id' => $freshStock->product_id,
            'variant_id' => null,
            'product_name' => $freshStock->product->name,
            'sku' => $freshStock->product->sku,
            'unit_price' => 25,
            'quantity' => 5,
            'line_total' => 125,
        ]);

        $releasedOrders = app(InventoryService::class)->releaseExpiredReservations(120);

        $expiredStock->refresh();
        $freshStock->refresh();

        $this->assertSame(1, $releasedOrders);
        $this->assertSame(0, $expiredStock->reserved_quantity);
        $this->assertSame(5, $freshStock->reserved_quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'stock_item_id' => $expiredStock->id,
            'type' => 'release',
            'reason' => 'order_timeout',
        ]);
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
