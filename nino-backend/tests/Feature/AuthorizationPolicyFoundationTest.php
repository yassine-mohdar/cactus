<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\IAM\Models\Permission;
use App\Modules\Payments\Models\GatewaySetting;
use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuthorizationPolicyFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->activateAdminTheme('nino-v2');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_product_index_requires_products_view_any_permission(): void
    {
        Permission::findOrCreate('products.viewAny', 'web');

        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->get(route('admin.catalog.products.index'))
            ->assertForbidden();

        $staff->givePermissionTo('products.viewAny');

        $this->actingAs($staff->fresh())
            ->get(route('admin.catalog.products.index'))
            ->assertOk();
    }

    public function test_customer_admin_index_requires_customer_permission_policy(): void
    {
        Permission::findOrCreate('customers.viewAny', 'web');

        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->get(route('admin.customers.index'))
            ->assertForbidden();

        $staff->givePermissionTo('customers.viewAny');

        $this->actingAs($staff->fresh())
            ->get(route('admin.customers.index'))
            ->assertOk();
    }

    public function test_gateways_route_group_uses_permission_middleware(): void
    {
        Permission::findOrCreate('finance.manage_gateways', 'web');

        $staff = User::factory()->staff()->create();

        GatewaySetting::query()->create([
            'gateway_id' => 'stripe',
            'name' => 'Stripe',
            'is_enabled' => false,
            'mode' => 'test',
            'credentials' => [],
            'metadata' => [],
        ]);

        $this->actingAs($staff)
            ->get(route('admin.gateways.index'))
            ->assertForbidden();

        $staff->givePermissionTo('finance.manage_gateways');

        $this->actingAs($staff->fresh())
            ->get(route('admin.gateways.index'))
            ->assertOk();
    }

    public function test_reports_route_group_uses_permission_middleware(): void
    {
        Permission::findOrCreate('reports.viewAny', 'web');

        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->get(route('admin.reports.orders'))
            ->assertForbidden();

        $staff->givePermissionTo('reports.viewAny');

        $this->actingAs($staff->fresh())
            ->get(route('admin.reports.orders'))
            ->assertOk();
    }
    private function activateAdminTheme(string $theme): void
    {
        config(['admin_theme.active' => $theme]);

        $provider = new AdminThemeServiceProvider($this->app);
        $provider->boot($this->app->make(AdminThemeManager::class));
    }
}
