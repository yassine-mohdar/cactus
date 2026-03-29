<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Inventory\Models\StockMovement;
use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryDamageWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
        $this->withoutVite();
        $this->activateAdminTheme('nino-v2');
    }

    public function test_damage_workflow_creates_dedicated_damage_movement_and_audit_log(): void
    {
        $manager = $this->createInventoryManager();
        $stockItem = $this->createStockItem(quantity: 9);

        $this->actingAs($manager)
            ->post(route('admin.inventory.damage.store'), [
                'stock_item_id' => $stockItem->id,
                'quantity' => 2,
                'notes' => 'Broken during shelf handling.',
            ])
            ->assertRedirect(route('admin.inventory.damage.create', ['stock_item_id' => $stockItem->id]));

        $stockItem->refresh();
        $movement = StockMovement::query()->latest('id')->firstOrFail();
        $audit = AuditLog::query()->where('action', 'inventory.damage_reported')->latest('id')->firstOrFail();

        $this->assertSame(7, $stockItem->quantity);
        $this->assertSame('damage', $movement->reason);
        $this->assertSame('deduction', $movement->type);
        $this->assertSame($manager->id, $movement->user_id);
        $this->assertSame('Broken during shelf handling.', $movement->notes);
        $this->assertSame('damage_report', $audit->context['inventory_workflow'] ?? null);
    }

    public function test_damage_report_page_lists_damage_only_movements(): void
    {
        $manager = $this->createInventoryManager();
        $stockItem = $this->createStockItem(quantity: 12);

        StockMovement::create([
            'stock_item_id' => $stockItem->id,
            'user_id' => $manager->id,
            'type' => 'deduction',
            'reason' => 'damage',
            'quantity' => -1,
            'quantity_before' => 12,
            'quantity_after' => 11,
            'notes' => 'Cracked packaging',
        ]);

        StockMovement::create([
            'stock_item_id' => $stockItem->id,
            'user_id' => $manager->id,
            'type' => 'deduction',
            'reason' => 'manual_adjustment',
            'quantity' => -1,
            'quantity_before' => 11,
            'quantity_after' => 10,
            'notes' => 'Cycle count',
        ]);

        $this->actingAs($manager)
            ->get(route('admin.inventory.damage.index'))
            ->assertOk()
            ->assertSeeText('Damaged Stock History')
            ->assertSeeText('Cracked packaging')
            ->assertDontSeeText('Cycle count');
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

    private function createStockItem(int $quantity): StockItem
    {
        $product = Product::factory()->published()->create();

        return StockItem::factory()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'reserved_quantity' => 0,
            'low_stock_threshold' => 5,
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
