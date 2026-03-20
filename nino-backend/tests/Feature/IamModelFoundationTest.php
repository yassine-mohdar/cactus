<?php

namespace Tests\Feature;

use App\Modules\IAM\Models\Permission;
use App\Modules\IAM\Models\Role;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class IamModelFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_permission_config_uses_local_iam_models(): void
    {
        $this->assertSame(Permission::class, config('permission.models.permission'));
        $this->assertSame(Role::class, config('permission.models.role'));
    }

    public function test_local_role_model_supports_system_and_custom_role_queries(): void
    {
        $superAdmin = Role::firstOrCreate([
            'name' => Role::SUPER_ADMIN,
            'guard_name' => 'web',
        ]);

        $customRole = Role::firstOrCreate([
            'name' => 'Regional Analyst',
            'guard_name' => 'web',
        ]);

        $this->assertTrue($superAdmin->isSystemRole());
        $this->assertFalse($customRole->isSystemRole());
        $this->assertSame([Role::SUPER_ADMIN], Role::query()->systemPresets()->pluck('name')->all());
        $this->assertSame(['Regional Analyst'], Role::query()->custom()->pluck('name')->all());
    }

    public function test_local_permission_model_exposes_group_metadata(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => Permission::MANAGE_ROLES,
            'guard_name' => 'web',
        ]);

        $this->assertSame('users', $permission->groupKey());
        $this->assertSame('IAM', $permission->groupLabel());
    }

    public function test_permission_seeder_creates_role_permission_relationships_for_presets(): void
    {
        $this->seed(PermissionsSeeder::class);

        $stockManager = Role::findByName(Role::STOCK_MANAGER, 'web');
        $supportAgent = Role::findByName(Role::CUSTOMER_SUPPORT_AGENT, 'web');

        $this->assertTrue($stockManager->permissions()->where('name', 'inventory.adjust')->exists());
        $this->assertTrue($stockManager->permissions()->where('name', 'inventory.transfer')->exists());
        $this->assertTrue($supportAgent->permissions()->where('name', 'support.manage_tickets')->exists());
        $this->assertTrue(Permission::findByName('support.manage_tickets', 'web')->roles()->where('name', Role::CUSTOMER_SUPPORT_AGENT)->exists());
    }
}
