<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Inventory\Models\StockMovement;
use Exception;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Adjust the physical quantity of a stock item.
     * Can be positive (addition) or negative (deduction).
     *
     * @param StockItem $stockItem
     * @param int $quantityChange The amount to add or subtract (e.g., +5, -2)
     * @param string $reason e.g., 'restock', 'damage', 'manual_adjustment', 'return'
     * @param int|null $userId ID of the user performing the action
     * @param string|null $notes Optional notes
     * @return StockMovement
     * @throws Exception
     */
    public function adjustStock(
        StockItem $stockItem,
        int $quantityChange,
        string $reason,
        ?int $userId = null,
        ?string $notes = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): StockMovement {
        if ($quantityChange === 0) {
            throw new Exception("Quantity change cannot be zero.");
        }

        return DB::transaction(function () use ($stockItem, $quantityChange, $reason, $userId, $notes, $referenceType, $referenceId) {
            // Lock the row for update to prevent race conditions
            $lockedItem = StockItem::lockForUpdate()->find($stockItem->id);

            $quantityBefore = $lockedItem->quantity;
            $quantityAfter = $quantityBefore + $quantityChange;

            // Optional: prevent negative stock? For manual adjustments, we might allow it (backorders), 
            // but normally physical stock shouldn't be negative. Let's allow it as a reality check,
            // or we could throw an exception if $quantityAfter < 0. For now, we allow it.

            $type = $quantityChange > 0 ? 'addition' : 'deduction';

            // Create movement record
            $movement = StockMovement::create([
                'stock_item_id' => $lockedItem->id,
                'user_id' => $userId,
                'type' => $type,
                'reason' => $reason,
                'quantity' => $quantityChange,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'notes' => $notes,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);

            // Update item
            $lockedItem->quantity = $quantityAfter;

            // Recalculate status
            if ($lockedItem->getAvailableQuantityAttribute() <= 0) {
                $lockedItem->status = 'out_of_stock';
            } elseif ($lockedItem->getAvailableQuantityAttribute() <= $lockedItem->low_stock_threshold) {
                $lockedItem->status = 'low_stock';
            } else {
                $lockedItem->status = 'in_stock';
            }

            $lockedItem->save();

            $lockedItem->loadMissing(['product', 'variant', 'branch']);
            $this->audit->log(
                action: 'inventory.stock_adjusted',
                target: $lockedItem,
                oldValues: $this->stockSnapshot($lockedItem, $quantityBefore, $lockedItem->reserved_quantity, $this->statusForSnapshot($quantityBefore, $lockedItem->reserved_quantity, $lockedItem->low_stock_threshold)),
                newValues: $this->stockSnapshot($lockedItem, $quantityAfter, $lockedItem->reserved_quantity, $lockedItem->status),
                notes: $notes,
                context: [
                    'module' => 'inventory',
                    'source' => 'inventory_service',
                    'movement_id' => $movement->id,
                    'movement_type' => $type,
                    'reason' => $reason,
                    'quantity_change' => $quantityChange,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                ],
                targetLabel: $this->stockTargetLabel($lockedItem),
            );

            return $movement;
        });
    }

    /**
     * Override stock absolutely. Calculates the difference and performs an adjustment.
     */
    public function setAbsoluteStock(
        StockItem $stockItem,
        int $newQuantity,
        string $reason = 'manual_adjustment',
        ?int $userId = null,
        ?string $notes = null
    ): StockMovement {
        return DB::transaction(function () use ($stockItem, $newQuantity, $reason, $userId, $notes) {
            $lockedItem = StockItem::lockForUpdate()->find($stockItem->id);
            $quantityChange = $newQuantity - $lockedItem->quantity;

            if ($quantityChange === 0) {
                // Return a dummy/empty movement or throw exception?
                // Let's just create a 'set' movement with 0 change for audit.
                $movement = StockMovement::create([
                    'stock_item_id' => $lockedItem->id,
                    'user_id' => $userId,
                    'type' => 'set',
                    'reason' => $reason,
                    'quantity' => 0,
                    'quantity_before' => $lockedItem->quantity,
                    'quantity_after' => $newQuantity,
                    'notes' => $notes,
                ]);

                $lockedItem->loadMissing(['product', 'variant', 'branch']);
                $this->audit->log(
                    action: 'inventory.stock_adjusted',
                    target: $lockedItem,
                    oldValues: $this->stockSnapshot($lockedItem, $lockedItem->quantity, $lockedItem->reserved_quantity, $lockedItem->status),
                    newValues: $this->stockSnapshot($lockedItem, $newQuantity, $lockedItem->reserved_quantity, $lockedItem->status),
                    notes: $notes,
                    context: [
                        'module' => 'inventory',
                        'source' => 'inventory_service',
                        'movement_id' => $movement->id,
                        'movement_type' => 'set',
                        'reason' => $reason,
                        'quantity_change' => 0,
                    ],
                    targetLabel: $this->stockTargetLabel($lockedItem),
                );

                return $movement;
            }

            return $this->adjustStock(
                $lockedItem,
                $quantityChange,
                $reason,
                $userId,
                $notes
            );
        });
    }

    /**
     * Reserve stock for an order or checkout session.
     * Moves available stock to reserved, preventing overselling.
     * 
     * @throws InsufficientStockException
     */
    public function reserveStock(
        StockItem $stockItem,
        int $quantity,
        ?int $userId = null,
        ?string $referenceType = null,
        ?string $referenceId = null
    ): StockMovement {
        if ($quantity <= 0) {
            throw new Exception("Reservation quantity must be greater than zero.");
        }

        return DB::transaction(function () use ($stockItem, $quantity, $userId, $referenceType, $referenceId) {
            $lockedItem = StockItem::lockForUpdate()->find($stockItem->id);

            if ($lockedItem->getAvailableQuantityAttribute() < $quantity) {
                throw new \App\Modules\Inventory\Exceptions\InsufficientStockException(
                    "Insufficient stock to reserve {$quantity} units for Item ID {$stockItem->id}."
                );
            }

            // Increment reserved quantity
            $lockedItem->reserved_quantity += $quantity;
            
            // Recalculate status
            if ($lockedItem->getAvailableQuantityAttribute() <= 0) {
                $lockedItem->status = 'out_of_stock';
            } elseif ($lockedItem->getAvailableQuantityAttribute() <= $lockedItem->low_stock_threshold) {
                $lockedItem->status = 'low_stock';
            } else {
                $lockedItem->status = 'in_stock';
            }
            
            $lockedItem->save();

            // Create movement record for the reservation
            return StockMovement::create([
                'stock_item_id' => $lockedItem->id,
                'user_id' => $userId,
                'type' => 'reservation',
                'reason' => 'order_reserved',
                'quantity' => 0, // Physical quantity remains unchanged
                'quantity_before' => $lockedItem->quantity,
                'quantity_after' => $lockedItem->quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => "Reserved {$quantity} units.",
            ]);
        });
    }

    /**
     * Release previously reserved stock.
     * Used when an order is cancelled or a checkout session expires.
     */
    public function releaseStock(
        StockItem $stockItem,
        int $quantity,
        ?int $userId = null,
        ?string $referenceType = null,
        ?string $referenceId = null
    ): StockMovement {
        if ($quantity <= 0) {
            throw new Exception("Release quantity must be greater than zero.");
        }

        return DB::transaction(function () use ($stockItem, $quantity, $userId, $referenceType, $referenceId) {
            $lockedItem = StockItem::lockForUpdate()->find($stockItem->id);

            // Don't release more than what is reserved
            $actualRelease = min($quantity, $lockedItem->reserved_quantity);

            $lockedItem->reserved_quantity -= $actualRelease;
            
            // Recalculate status
            if ($lockedItem->getAvailableQuantityAttribute() <= 0) {
                $lockedItem->status = 'out_of_stock';
            } elseif ($lockedItem->getAvailableQuantityAttribute() <= $lockedItem->low_stock_threshold) {
                $lockedItem->status = 'low_stock';
            } else {
                $lockedItem->status = 'in_stock';
            }
            
            $lockedItem->save();

            // Create movement record for the release
            return StockMovement::create([
                'stock_item_id' => $lockedItem->id,
                'user_id' => $userId,
                'type' => 'release',
                'reason' => 'order_cancelled',
                'quantity' => 0, // Physical quantity remains unchanged
                'quantity_before' => $lockedItem->quantity,
                'quantity_after' => $lockedItem->quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => "Released {$actualRelease} reserved units.",
            ]);
        });
    }

    private function stockTargetLabel(StockItem $stockItem): string
    {
        $name = $stockItem->variant?->sku
            ?? $stockItem->product?->name
            ?? ('Stock Item #' . $stockItem->id);
        $branch = $stockItem->branch?->name;

        return $branch
            ? "StockItem: {$name} @ {$branch}"
            : "StockItem: {$name}";
    }

    private function stockSnapshot(StockItem $stockItem, int $quantity, int $reservedQuantity, string $status): array
    {
        return [
            'quantity' => $quantity,
            'reserved_quantity' => $reservedQuantity,
            'available_quantity' => max(0, $quantity - $reservedQuantity),
            'status' => $status,
            'sku' => $stockItem->sku,
            'branch_id' => $stockItem->branch_id,
        ];
    }

    private function statusForSnapshot(int $quantity, int $reservedQuantity, int $lowStockThreshold): string
    {
        $available = max(0, $quantity - $reservedQuantity);

        if ($available <= 0) {
            return 'out_of_stock';
        }

        if ($available <= $lowStockThreshold) {
            return 'low_stock';
        }

        return 'in_stock';
    }
}
