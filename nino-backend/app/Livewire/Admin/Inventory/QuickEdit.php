<?php

namespace App\Livewire\Admin\Inventory;

use Livewire\Component;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Inventory\Services\InventoryService;
use Exception;

class QuickEdit extends Component
{
    public ?StockItem $stockItem = null;
    public $quantity = 0;
    public $adjustment_type = 'add';
    public $reason = 'manual_adjustment';
    public $notes = '';

    protected $listeners = ['openQuickEdit' => 'loadItem'];

    public function loadItem($id)
    {
        $this->stockItem = StockItem::with('product')->find($id);
        if ($this->stockItem) {
            $this->quantity = 1;
            $this->adjustment_type = 'add';
            $this->reason = 'manual_adjustment';
            $this->notes = '';
            $this->dispatch('show-quick-edit-modal');
        }
    }

    public function save(InventoryService $inventoryService)
    {
        $this->validate([
            'quantity' => 'required|integer|min:0',
            'adjustment_type' => 'required|in:add,subtract,set',
            'reason' => 'required|string',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            if ($this->adjustment_type === 'set') {
                $inventoryService->setAbsoluteStock(
                    $this->stockItem,
                    (int) $this->quantity,
                    $this->reason,
                    auth()->id(),
                    $this->notes
                );
            } else {
                $change = (int) $this->quantity;
                if ($this->adjustment_type === 'subtract') {
                    $change = -$change;
                }

                $inventoryService->adjustStock(
                    $this->stockItem,
                    $change,
                    $this->reason,
                    auth()->id(),
                    $this->notes
                );
            }

            $this->dispatch('hide-quick-edit-modal');
            $this->dispatch('notify', ['message' => 'Stock updated successfully.']);
            
            // Refresh parent page or emit event to refresh list
            return redirect(request()->header('Referer'));
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.inventory.quick-edit');
    }
}
