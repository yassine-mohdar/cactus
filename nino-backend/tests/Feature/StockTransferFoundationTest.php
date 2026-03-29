<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Inventory\Models\StockTransfer;
use App\Modules\Inventory\Services\StockTransferService;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransferFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_service_creates_pending_branch_transfer_and_destination_item(): void
    {
        [$sourceBranch, $destinationBranch] = $this->makeBranches();
        $operator = User::factory()->create();
        $sourceItem = $this->makeSourceStock($sourceBranch, 15);

        $transfer = app(StockTransferService::class)->initiateTransfer(
            $sourceItem,
            $destinationBranch,
            4,
            $operator->id,
            'Rebalance stock to destination branch.',
        );

        $this->assertSame(StockTransfer::STATUS_PENDING, $transfer->status);
        $this->assertSame(4, $transfer->quantity);
        $this->assertSame($sourceItem->id, $transfer->source_stock_item_id);
        $this->assertNotNull($transfer->destination_stock_item_id);
        $this->assertDatabaseHas('inventory_stock_items', [
            'id' => $transfer->destination_stock_item_id,
            'branch_id' => $destinationBranch->id,
            'product_id' => $sourceItem->product_id,
            'product_variant_id' => null,
        ]);
    }

    public function test_transfer_service_can_ship_and_receive_inventory(): void
    {
        [$sourceBranch, $destinationBranch] = $this->makeBranches();
        $operator = User::factory()->create();
        $sourceItem = $this->makeSourceStock($sourceBranch, 20);

        $service = app(StockTransferService::class);
        $transfer = $service->initiateTransfer($sourceItem, $destinationBranch, 6, $operator->id);

        $transfer = $service->markShipped($transfer, $operator->id, 'Sent to destination branch.');
        $sourceItem->refresh();

        $this->assertSame(StockTransfer::STATUS_SHIPPED, $transfer->status);
        $this->assertSame(14, $sourceItem->quantity);
        $this->assertNotNull($transfer->shipped_at);
        $this->assertDatabaseHas('inventory_movements', [
            'stock_item_id' => $sourceItem->id,
            'reason' => 'transfer_out',
            'reference_type' => StockTransfer::class,
            'reference_id' => (string) $transfer->id,
        ]);

        $transfer = $service->markReceived($transfer, $operator->id, 'Received and counted.');
        $destinationItem = StockItem::query()->findOrFail($transfer->destination_stock_item_id);

        $this->assertSame(StockTransfer::STATUS_RECEIVED, $transfer->status);
        $this->assertSame(6, $destinationItem->quantity);
        $this->assertNotNull($transfer->received_at);
        $this->assertDatabaseHas('inventory_movements', [
            'stock_item_id' => $destinationItem->id,
            'reason' => 'transfer_in',
            'reference_type' => StockTransfer::class,
            'reference_id' => (string) $transfer->id,
        ]);
    }

    private function makeBranches(): array
    {
        $platform = Organization::factory()->platform()->create();
        $franchise = Organization::factory()->franchise($platform)->create();

        return [
            Organization::factory()->branch($franchise)->create(['name' => 'Source Branch']),
            Organization::factory()->branch($franchise)->create(['name' => 'Destination Branch']),
        ];
    }

    private function makeSourceStock(Organization $branch, int $quantity): StockItem
    {
        $product = Product::factory()->published()->create();

        return StockItem::factory()->forBranch($branch)->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'reserved_quantity' => 0,
            'status' => StockItem::STATUS_IN_STOCK,
        ]);
    }
}
