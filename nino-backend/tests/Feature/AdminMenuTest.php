<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
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
}
