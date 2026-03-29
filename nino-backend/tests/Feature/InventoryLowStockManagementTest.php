<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\StockItem;
use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryLowStockManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
        $this->withoutVite();
        $this->activateAdminTheme('nino-v2');
    }

    public function test_manual_adjustment_controller_path_writes_inventory_audit_log(): void
    {
        $manager = $this->createInventoryManager();
        $stockItem = $this->createStockItem(quantity: 9, reserved: 0, threshold: 4);

        $this->actingAs($manager)->post(route('admin.inventory.adjustments.store'), [
            'stock_item_id' => $stockItem->id,
            'adjustment_type' => 'subtract',
            'quantity' => 3,
            'reason' => 'manual_adjustment',
            'notes' => 'Cycle count confirmed discrepancy.',
        ])->assertRedirect(route('admin.inventory.adjustments.create', ['stock_item_id' => $stockItem->id]));

        $auditLog = AuditLog::query()->where('action', 'inventory.stock_adjusted')->latest('id')->firstOrFail();

        $this->assertSame(9, $auditLog->old_values['quantity'] ?? null);
        $this->assertSame(6, $auditLog->new_values['quantity'] ?? null);
        $this->assertSame(-3, $auditLog->context['quantity_change'] ?? null);
        $this->assertSame('manual_adjustment', $auditLog->context['reason'] ?? null);
        $this->assertSame($manager->id, $auditLog->user_id);
    }

    public function test_low_stock_page_lists_alerts_and_supports_threshold_updates(): void
    {
        $manager = $this->createInventoryManager();
        $alert = $this->createStockItem(quantity: 4, reserved: 0, threshold: 5);
        $healthy = $this->createStockItem(quantity: 12, reserved: 0, threshold: 3);

        $this->actingAs($manager)
            ->get(route('admin.inventory.low-stock'))
            ->assertOk()
            ->assertSeeText('Low Stock Alerts')
            ->assertSeeText($alert->product->name)
            ->assertDontSeeText($healthy->product->name);

        $this->actingAs($manager)
            ->post(route('admin.inventory.threshold.update', $alert), [
                'low_stock_threshold' => 8,
            ])
            ->assertRedirect();

        $this->assertSame(8, $alert->fresh()->low_stock_threshold);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'inventory.low_stock_threshold_updated',
            'auditable_id' => $alert->id,
        ]);
    }

    public function test_stock_manager_dashboard_links_low_stock_widget_to_dedicated_alert_queue(): void
    {
        $manager = $this->createInventoryManager();
        $this->createStockItem(quantity: 3, reserved: 0, threshold: 5);

        $response = $this->actingAs($manager)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee(route('admin.inventory.low-stock'), false);
        $response->assertSeeText('Low Stock Widget');
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

    private function createStockItem(int $quantity, int $reserved, int $threshold): StockItem
    {
        $product = Product::factory()->published()->create();

        return StockItem::factory()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'reserved_quantity' => $reserved,
            'low_stock_threshold' => $threshold,
            'status' => $quantity - $reserved <= 0
                ? StockItem::STATUS_OUT_OF_STOCK
                : ($quantity - $reserved <= $threshold ? StockItem::STATUS_LOW_STOCK : StockItem::STATUS_IN_STOCK),
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
