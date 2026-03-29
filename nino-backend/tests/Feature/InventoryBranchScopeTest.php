<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Organizations\Models\Organization;
use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryBranchScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
        $this->withoutVite();
        $this->activateAdminTheme('nino-v2');
    }

    public function test_branch_manager_only_sees_stock_for_their_assigned_branch(): void
    {
        [$franchise, $branchA, $branchB] = $this->makeFranchiseNetwork();
        $manager = $this->makeStaffUser(scope: 'branch', organization: $branchA);

        $visibleStock = $this->makeBranchStock($branchA, 'Branch A Product');
        $hiddenStock = $this->makeBranchStock($branchB, 'Branch B Product');
        $globalStock = StockItem::factory()->create([
            'product_id' => Product::factory()->published()->create(['name' => 'Global Product'])->id,
            'branch_id' => null,
            'quantity' => 10,
            'reserved_quantity' => 0,
        ]);

        $response = $this->actingAs($manager)->get(route('admin.inventory.index'));

        $response->assertOk();
        $response->assertSee(route('admin.inventory.adjustments.create', ['stock_item_id' => $visibleStock->id]), false);
        $response->assertDontSee(route('admin.inventory.adjustments.create', ['stock_item_id' => $hiddenStock->id]), false);
        $response->assertDontSee(route('admin.inventory.adjustments.create', ['stock_item_id' => $globalStock->id]), false);
        $response->assertSee('value="'.$branchA->id.'">'.$branchA->name.'</option>', false);
        $response->assertDontSee('value="'.$branchB->id.'">'.$branchB->name.'</option>', false);
    }

    public function test_branch_manager_cannot_adjust_stock_outside_their_branch_scope(): void
    {
        [, $branchA, $branchB] = $this->makeFranchiseNetwork();
        $manager = $this->makeStaffUser(scope: 'branch', organization: $branchA);
        $outOfScopeStock = $this->makeBranchStock($branchB, 'Protected Branch Product');

        $response = $this->actingAs($manager)->post(route('admin.inventory.adjustments.store'), [
            'stock_item_id' => $outOfScopeStock->id,
            'adjustment_type' => 'add',
            'quantity' => 2,
            'reason' => 'restock',
            'notes' => 'Attempted out-of-scope update.',
        ]);

        $response->assertForbidden();
    }

    public function test_franchise_manager_can_see_child_branch_stock_but_not_global_rows(): void
    {
        [$franchise, $branchA, $branchB] = $this->makeFranchiseNetwork();
        $manager = $this->makeStaffUser(scope: 'franchise', organization: $franchise);

        $stockA = $this->makeBranchStock($branchA, 'Franchise Branch A');
        $stockB = $this->makeBranchStock($branchB, 'Franchise Branch B');
        $globalStock = StockItem::factory()->create([
            'product_id' => Product::factory()->published()->create(['name' => 'Platform Global Product'])->id,
            'branch_id' => null,
            'quantity' => 5,
            'reserved_quantity' => 0,
        ]);

        $response = $this->actingAs($manager)->get(route('admin.inventory.index'));

        $response->assertOk();
        $response->assertSee(route('admin.inventory.adjustments.create', ['stock_item_id' => $stockA->id]), false);
        $response->assertSee(route('admin.inventory.adjustments.create', ['stock_item_id' => $stockB->id]), false);
        $response->assertDontSee(route('admin.inventory.adjustments.create', ['stock_item_id' => $globalStock->id]), false);
    }

    private function makeFranchiseNetwork(): array
    {
        $platform = Organization::factory()->create([
            'type' => Organization::TYPE_PLATFORM,
            'status' => Organization::STATUS_ACTIVE,
        ]);

        $franchise = Organization::factory()->create([
            'type' => Organization::TYPE_FRANCHISE,
            'status' => Organization::STATUS_ACTIVE,
            'parent_id' => $platform->id,
        ]);

        $branchA = Organization::factory()->create([
            'type' => Organization::TYPE_BRANCH,
            'status' => Organization::STATUS_ACTIVE,
            'parent_id' => $franchise->id,
            'name' => 'Branch A',
        ]);

        $branchB = Organization::factory()->create([
            'type' => Organization::TYPE_BRANCH,
            'status' => Organization::STATUS_ACTIVE,
            'parent_id' => $franchise->id,
            'name' => 'Branch B',
        ]);

        return [$franchise, $branchA, $branchB];
    }

    private function makeStaffUser(string $scope, Organization $organization): User
    {
        $user = User::factory()->create([
            'type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'organization_scope' => $scope,
            'organization_id' => $organization->id,
        ]);

        $user->givePermissionTo([
            'inventory.viewAny',
            'inventory.adjust',
        ]);

        return $user;
    }

    private function makeBranchStock(Organization $branch, string $productName): StockItem
    {
        $product = Product::factory()->published()->create([
            'name' => $productName,
        ]);

        return StockItem::factory()->create([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => 12,
            'reserved_quantity' => 0,
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
