<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Organizations\Models\Organization;
use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryReportsFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
        $this->withoutVite();
        $this->activateAdminTheme('nino-v2');
    }

    public function test_current_stock_report_respects_branch_scope(): void
    {
        [, $branchA, $branchB] = $this->makeFranchiseNetwork();
        $manager = $this->makeStaffUser($branchA);

        $visible = $this->makeBranchStock($branchA, 'Visible Stock Product');
        $hidden = $this->makeBranchStock($branchB, 'Hidden Stock Product');

        $response = $this->actingAs($manager)->get(route('admin.inventory.reports.stock'));

        $response->assertOk();
        $response->assertSeeText('Current Stock Report');
        $response->assertSeeText($visible->product->name);
        $response->assertDontSeeText($hidden->product->name);
    }

    public function test_adjustment_history_report_filters_and_scopes_movements(): void
    {
        [, $branchA, $branchB] = $this->makeFranchiseNetwork();
        $manager = $this->makeStaffUser($branchA);

        $visible = $this->makeBranchStock($branchA, 'Scoped Product A');
        $hidden = $this->makeBranchStock($branchB, 'Scoped Product B');

        StockMovement::create([
            'stock_item_id' => $visible->id,
            'user_id' => $manager->id,
            'type' => 'deduction',
            'reason' => 'damage',
            'quantity' => -2,
            'quantity_before' => 10,
            'quantity_after' => 8,
            'notes' => 'Visible movement',
        ]);

        StockMovement::create([
            'stock_item_id' => $hidden->id,
            'user_id' => $manager->id,
            'type' => 'addition',
            'reason' => 'restock',
            'quantity' => 4,
            'quantity_before' => 4,
            'quantity_after' => 8,
            'notes' => 'Hidden movement',
        ]);

        $response = $this->actingAs($manager)->get(route('admin.inventory.reports.adjustments', [
            'reason' => 'damage',
        ]));

        $response->assertOk();
        $response->assertSeeText('Adjustment History');
        $response->assertSeeText($visible->product->name);
        $response->assertDontSeeText($hidden->product->name);
        $response->assertSeeText('Damage Events');
    }

    public function test_low_stock_report_lists_only_low_pressure_rows_in_scope(): void
    {
        [, $branchA, $branchB] = $this->makeFranchiseNetwork();
        $manager = $this->makeStaffUser($branchA);

        $alert = $this->makeBranchStock($branchA, 'Alert Product', quantity: 3, threshold: 5);
        $healthy = $this->makeBranchStock($branchA, 'Healthy Product', quantity: 12, threshold: 4);
        $hidden = $this->makeBranchStock($branchB, 'Other Branch Alert', quantity: 2, threshold: 5);

        $response = $this->actingAs($manager)->get(route('admin.inventory.reports.low-stock'));

        $response->assertOk();
        $response->assertSeeText('Low Stock Report');
        $response->assertSeeText($alert->product->name);
        $response->assertDontSeeText($healthy->product->name);
        $response->assertDontSeeText($hidden->product->name);
    }

    public function test_damaged_stock_report_lists_damage_only_rows_in_scope(): void
    {
        [, $branchA, $branchB] = $this->makeFranchiseNetwork();
        $manager = $this->makeStaffUser($branchA);

        $visible = $this->makeBranchStock($branchA, 'Visible Damage Product');
        $hidden = $this->makeBranchStock($branchB, 'Hidden Damage Product');

        StockMovement::create([
            'stock_item_id' => $visible->id,
            'user_id' => $manager->id,
            'type' => 'deduction',
            'reason' => 'damage',
            'quantity' => -3,
            'quantity_before' => 10,
            'quantity_after' => 7,
            'notes' => 'Visible damage',
        ]);

        StockMovement::create([
            'stock_item_id' => $hidden->id,
            'user_id' => $manager->id,
            'type' => 'deduction',
            'reason' => 'damage',
            'quantity' => -2,
            'quantity_before' => 8,
            'quantity_after' => 6,
            'notes' => 'Hidden damage',
        ]);

        $response = $this->actingAs($manager)->get(route('admin.inventory.reports.damaged-stock'));

        $response->assertOk();
        $response->assertSeeText('Damaged Stock Report');
        $response->assertSeeText($visible->product->name);
        $response->assertDontSeeText($hidden->product->name);
    }

    private function makeFranchiseNetwork(): array
    {
        $platform = Organization::factory()->platform()->create();
        $franchise = Organization::factory()->franchise($platform)->create();
        $branchA = Organization::factory()->branch($franchise)->create(['name' => 'Branch A']);
        $branchB = Organization::factory()->branch($franchise)->create(['name' => 'Branch B']);

        return [$franchise, $branchA, $branchB];
    }

    private function makeStaffUser(Organization $branch): User
    {
        $user = User::factory()->create([
            'type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'organization_scope' => 'branch',
            'organization_id' => $branch->id,
        ]);

        $user->givePermissionTo([
            'inventory.viewAny',
            'inventory.adjust',
        ]);

        return $user;
    }

    private function makeBranchStock(Organization $branch, string $name, int $quantity = 10, int $threshold = 3): StockItem
    {
        $product = Product::factory()->published()->create(['name' => $name]);

        return StockItem::factory()->forBranch($branch)->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'reserved_quantity' => 0,
            'low_stock_threshold' => $threshold,
            'status' => $quantity <= $threshold ? StockItem::STATUS_LOW_STOCK : StockItem::STATUS_IN_STOCK,
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
