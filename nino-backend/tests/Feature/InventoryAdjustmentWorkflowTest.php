<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Inventory\Models\StockMovement;
use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryAdjustmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
        $this->withoutVite();
        $this->activateAdminTheme('nino-v2');
    }

    public function test_manual_adjustment_page_renders_selected_stock_context_and_operator_details(): void
    {
        $manager = $this->createInventoryManager();
        $stockItem = $this->createStockItem(quantity: 14, reserved: 3);

        $response = $this->actingAs($manager)->get(route('admin.inventory.adjustments.create', [
            'stock_item_id' => $stockItem->id,
        ]));

        $response->assertOk();
        $response->assertSeeText('Manual Inventory Adjustment');
        $response->assertSeeText($stockItem->product->name);
        $response->assertSeeText($manager->name);
        $response->assertSeeText('Record adjustment');
    }

    public function test_manual_adjustment_supports_positive_adjustments_with_reason_and_staff_attribution(): void
    {
        $manager = $this->createInventoryManager();
        $stockItem = $this->createStockItem(quantity: 10, reserved: 2);

        $response = $this->actingAs($manager)->post(route('admin.inventory.adjustments.store'), [
            'stock_item_id' => $stockItem->id,
            'adjustment_type' => 'add',
            'quantity' => 5,
            'reason' => 'restock',
            'notes' => 'Supplier delivery received.',
        ]);

        $response->assertRedirect(route('admin.inventory.adjustments.create', [
            'stock_item_id' => $stockItem->id,
        ]));

        $stockItem->refresh();
        $movement = StockMovement::query()->latest('id')->firstOrFail();

        $this->assertSame(15, $stockItem->quantity);
        $this->assertSame('addition', $movement->type);
        $this->assertSame('restock', $movement->reason);
        $this->assertSame($manager->id, $movement->user_id);
        $this->assertSame('Supplier delivery received.', $movement->notes);
    }

    public function test_manual_adjustment_supports_negative_adjustments_with_reason_and_staff_attribution(): void
    {
        $manager = $this->createInventoryManager();
        $stockItem = $this->createStockItem(quantity: 10, reserved: 1);

        $response = $this->actingAs($manager)->post(route('admin.inventory.adjustments.store'), [
            'stock_item_id' => $stockItem->id,
            'adjustment_type' => 'subtract',
            'quantity' => 4,
            'reason' => 'damage',
            'notes' => 'Broken units removed after count.',
        ]);

        $response->assertRedirect(route('admin.inventory.adjustments.create', [
            'stock_item_id' => $stockItem->id,
        ]));

        $stockItem->refresh();
        $movement = StockMovement::query()->latest('id')->firstOrFail();

        $this->assertSame(6, $stockItem->quantity);
        $this->assertSame('deduction', $movement->type);
        $this->assertSame('damage', $movement->reason);
        $this->assertSame($manager->id, $movement->user_id);
        $this->assertSame('Broken units removed after count.', $movement->notes);
    }

    private function createInventoryManager(): User
    {
        $user = User::factory()->create([
            'type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'organization_scope' => 'platform',
        ]);

        $user->givePermissionTo([
            'inventory.viewAny',
            'inventory.adjust',
        ]);

        return $user;
    }

    private function createStockItem(int $quantity, int $reserved): StockItem
    {
        $product = Product::factory()->published()->create();

        return StockItem::factory()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'reserved_quantity' => $reserved,
            'status' => StockItem::STATUS_IN_STOCK,
        ]);
    }

    private function activateAdminTheme(string $theme): void
    {
        config(['admin_theme.active' => $theme]);

        $finder = app('view.finder');
        $finder->flush();

        $provider = new AdminThemeServiceProvider($this->app);
        $provider->boot($this->app->make(AdminThemeManager::class));
    }
}
