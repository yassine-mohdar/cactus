<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\StockItem;
use App\Modules\Inventory\Models\StockTransfer;
use App\Modules\Organizations\Models\Organization;
use Exception;
use Illuminate\Support\Facades\DB;

class StockTransferService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function initiateTransfer(
        StockItem $sourceItem,
        Organization $destinationBranch,
        int $quantity,
        ?int $userId = null,
        ?string $notes = null,
    ): StockTransfer {
        if ($quantity <= 0) {
            throw new Exception('Transfer quantity must be greater than zero.');
        }

        if (! $sourceItem->branch_id) {
            throw new Exception('Only branch-owned stock can be prepared for transfer.');
        }

        if (! $destinationBranch->isBranch()) {
            throw new Exception('Transfer destination must be a branch.');
        }

        if ((int) $sourceItem->branch_id === (int) $destinationBranch->id) {
            throw new Exception('Source and destination branches must be different.');
        }

        $destinationItem = StockItem::query()->firstOrCreate(
            [
                'product_id' => $sourceItem->product_id,
                'product_variant_id' => $sourceItem->product_variant_id,
                'branch_id' => $destinationBranch->id,
            ],
            [
                'quantity' => 0,
                'reserved_quantity' => 0,
                'low_stock_threshold' => $sourceItem->low_stock_threshold,
                'status' => StockItem::STATUS_OUT_OF_STOCK,
            ],
        );

        return StockTransfer::query()->create([
            'source_stock_item_id' => $sourceItem->id,
            'destination_stock_item_id' => $destinationItem->id,
            'user_id' => $userId,
            'quantity' => $quantity,
            'status' => StockTransfer::STATUS_PENDING,
            'notes' => $notes,
        ]);
    }

    public function markShipped(StockTransfer $transfer, ?int $userId = null, ?string $notes = null): StockTransfer
    {
        if (! $transfer->isPending()) {
            throw new Exception('Only pending transfers can be shipped.');
        }

        return DB::transaction(function () use ($transfer, $userId, $notes) {
            $transfer->loadMissing('sourceItem');

            $this->inventoryService->adjustStock(
                $transfer->sourceItem,
                -1 * (int) $transfer->quantity,
                'transfer_out',
                $userId,
                $notes ?? $transfer->notes,
                StockTransfer::class,
                $transfer->id,
            );

            $transfer->forceFill([
                'status' => StockTransfer::STATUS_SHIPPED,
                'shipped_at' => now(),
                'user_id' => $userId ?? $transfer->user_id,
                'notes' => $notes ?? $transfer->notes,
            ])->save();

            return $transfer->fresh(['sourceItem', 'destinationItem']);
        });
    }

    public function markReceived(StockTransfer $transfer, ?int $userId = null, ?string $notes = null): StockTransfer
    {
        if (! $transfer->isShipped()) {
            throw new Exception('Only shipped transfers can be received.');
        }

        return DB::transaction(function () use ($transfer, $userId, $notes) {
            $transfer->loadMissing('destinationItem');

            $this->inventoryService->adjustStock(
                $transfer->destinationItem,
                (int) $transfer->quantity,
                'transfer_in',
                $userId,
                $notes ?? $transfer->notes,
                StockTransfer::class,
                $transfer->id,
            );

            $transfer->forceFill([
                'status' => StockTransfer::STATUS_RECEIVED,
                'received_at' => now(),
                'user_id' => $userId ?? $transfer->user_id,
                'notes' => $notes ?? $transfer->notes,
            ])->save();

            return $transfer->fresh(['sourceItem', 'destinationItem']);
        });
    }
}
