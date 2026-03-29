<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use App\Modules\IAM\Models\Role as IamRole;
use App\Modules\Catalog\Navigation\CatalogMenu;
use App\Modules\Support\Navigation\SupportMenu;
use App\Modules\Shipping\Navigation\ShippingMenu;
use App\Modules\Orders\Navigation\OrdersMenu;
use App\Modules\Payments\Navigation\GatewaysMenu;
use App\Modules\Reports\Navigation\ReportsMenu;
use App\Modules\Settings\Navigation\SettingsMenu;
use App\Modules\Shared\Navigation\DTOs\MenuItem;
use App\Modules\Shared\Navigation\Services\MenuRegistry;
use App\Modules\Shared\Navigation\Services\MenuBuilder;
use App\Modules\Shared\Navigation\Services\MenuVisibilityResolver;
use App\Modules\Shared\Navigation\Services\ActiveMenuResolver;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionsSeeder::class);
        Permission::create(['name' => 'view.secret.menu']);
    }

    public function test_menu_builder_filters_by_permission()
    {
        $registry = new MenuRegistry();
        
        $registry->add(MenuItem::make('public.item')->setLabel('Public'));
        $registry->add(MenuItem::make('secret.item')->setLabel('Secret')->requirePermission('view.secret.menu'));

        $builder = new MenuBuilder($registry, new MenuVisibilityResolver(), new ActiveMenuResolver());
        
        // Unauthenticated
        $this->assertCount(0, $builder->build());

        // Authenticated without permission
        $user1 = User::factory()->create();
        $this->actingAs($user1);
        $built = $builder->build();
        
        $this->assertCount(1, $built);
        $this->assertEquals('public.item', $built[0]->key);

        // Authenticated with permission
        $user2 = User::factory()->create();
        $user2->givePermissionTo('view.secret.menu');
        $this->actingAs($user2);
        
        $builtWithPerms = $builder->build();
        $this->assertCount(2, $builtWithPerms);
    }
    
    public function test_menu_builder_resolves_nesting_and_order()
    {
        $registry = new MenuRegistry();
        
        $registry->add(MenuItem::make('child.2')->setParent('parent.1')->setOrder(2));
        $registry->add(MenuItem::make('parent.1')->setOrder(10));
        $registry->add(MenuItem::make('child.1')->setParent('parent.1')->setOrder(1));

        $user = User::factory()->create();
        $this->actingAs($user);

        $builder = new MenuBuilder($registry, new MenuVisibilityResolver(), new ActiveMenuResolver());
        $built = $builder->build();

        $this->assertCount(1, $built);
        $this->assertEquals('parent.1', $built[0]->key);
        $this->assertCount(2, $built[0]->children);
        
        // Assert Ordering inside children
        $this->assertEquals('child.1', $built[0]->children[0]->key);
        $this->assertEquals('child.2', $built[0]->children[1]->key);
    }

    public function test_real_admin_navigation_is_permission_safe_and_prunes_empty_headers(): void
    {
        $registry = new MenuRegistry();
        (new CatalogMenu())->registerAdminMenu($registry);
        (new OrdersMenu())->registerAdminMenu($registry);
        (new GatewaysMenu())->registerAdminMenu($registry);
        (new ReportsMenu())->registerAdminMenu($registry);
        (new SettingsMenu())->registerAdminMenu($registry);

        $user = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
            'organization_scope' => 'platform',
        ]);
        $user->givePermissionTo(['orders.viewAny']);

        $this->actingAs($user);

        $built = (new MenuBuilder($registry, new MenuVisibilityResolver(), new ActiveMenuResolver()))->build();
        $keys = $this->flattenMenuKeys($built);

        $this->assertContains('orders.list', $keys);
        $this->assertNotContains('catalog.categories', $keys);
        $this->assertNotContains('catalog.products', $keys);
        $this->assertNotContains('settings.gateways', $keys);
        $this->assertNotContains('reports.sales', $keys);
        $this->assertNotContains('system.settings', $keys);
        $this->assertNotContains('system_header', $keys);
        $this->assertNotContains('reports_header', $keys);
    }

    public function test_menu_builder_prioritizes_sidebar_sections_for_the_current_role(): void
    {
        $registry = new MenuRegistry();
        (new OrdersMenu())->registerAdminMenu($registry);
        (new ShippingMenu())->registerAdminMenu($registry);
        (new SupportMenu())->registerAdminMenu($registry);

        $user = User::factory()->create([
            'type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'organization_scope' => 'platform',
        ]);
        $user->assignRole(IamRole::CUSTOMER_SUPPORT_AGENT);

        $this->actingAs($user);

        $built = (new MenuBuilder($registry, new MenuVisibilityResolver(), new ActiveMenuResolver()))->build();
        $keys = array_map(fn (MenuItem $item) => $item->key, $built);

        $this->assertSame('support_header', $keys[0] ?? null);
        $this->assertLessThan(
            array_search('shipping_header', $keys, true),
            array_search('support_header', $keys, true),
        );
        $this->assertContains('support.lookup', $this->flattenMenuKeys($built));
    }

    /**
     * @param  array<int, MenuItem>  $items
     * @return array<int, string>
     */
    private function flattenMenuKeys(array $items): array
    {
        $keys = [];

        foreach ($items as $item) {
            $keys[] = $item->key;

            if ($item->children !== []) {
                $keys = array_merge($keys, $this->flattenMenuKeys($item->children));
            }
        }

        return $keys;
    }
}
