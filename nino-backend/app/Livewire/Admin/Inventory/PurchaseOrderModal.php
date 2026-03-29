<?php

namespace App\Livewire\Admin\Inventory;

use App\Modules\Catalog\Models\Product;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Organizations\Services\OperationalScopeResolver;
use Livewire\Component;
use Exception;

class PurchaseOrderModal extends Component
{
    public $show = false;
    public $supplier_id = '';
    public $branch_id = '';
    public $items = []; // Array of ['product_id', 'quantity']
    public $reference_number = '';
    public $notes = '';

    protected $listeners = ['openPurchaseOrderModal' => 'open'];

    public function mount()
    {
        $this->addItem(); // Start with one empty item
    }

    public function open()
    {
        $this->reset(['supplier_id', 'branch_id', 'items', 'reference_number', 'notes']);
        $this->addItem();

        $visibleBranches = $this->visibleBranches();

        if ($visibleBranches->count() === 1) {
            $this->branch_id = (string) $visibleBranches->first()->id;
        }

        $this->show = true;
    }

    public function addItem()
    {
        $this->items[] = ['product_id' => '', 'quantity' => 1];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save(InventoryService $inventoryService)
    {
        $validated = $this->validate([
            'supplier_id' => 'required|exists:organizations,id',
            'branch_id' => 'required|exists:organizations,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'reference_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string'
        ]);

        abort_unless(
            $this->visibleBranches()->pluck('id')->contains((int) $this->branch_id),
            403
        );

        try {
            foreach ($this->items as $item) {
                // Find or create StockItem for this product/branch
                $stockItem = StockItem::firstOrCreate([
                    'product_id' => $item['product_id'],
                    'branch_id' => $this->branch_id,
                ], [
                    'quantity' => 0,
                    'status' => 'out_of_stock'
                ]);

                $inventoryService->adjustStock(
                    $stockItem,
                    (int) $item['quantity'],
                    'restock',
                    auth()->id(),
                    $this->notes,
                    'purchase_order',
                    null // We don't have a PO model yet
                );
            }

            $this->show = false;
            $this->dispatch('refresh-inventory'); // For future use if table is live
            
            return redirect()->route('admin.inventory.index')->with('success', 'Purchase order processed and stock updated.');
        } catch (Exception $e) {
            $this->addError('save', 'Failed to process purchase order: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.inventory.purchase-order-modal', [
            'suppliers' => Organization::supplier()->active()->get(),
            'branches' => $this->visibleBranches(),
            'products' => Product::active()->get(),
        ]);
    }

    private function visibleBranches()
    {
        $actor = auth()->user();
        $query = Organization::branch()->active()->orderBy('name');

        if (! $actor || $actor->isSuperAdmin() || $actor->hasOrganizationScope('platform')) {
            return $query->get();
        }

        $branchIds = app(OperationalScopeResolver::class)->branchIdsFor($actor);

        return $query->whereIn('id', $branchIds === [] ? [-1] : $branchIds)->get();
    }
}
