<?php

namespace Database\Seeders;

use App\Modules\IAM\Models\Permission;
use App\Modules\IAM\Models\Role;
use App\Modules\IAM\Services\RolePresetRegistry;
use App\Modules\IAM\Support\PermissionNaming;
use Illuminate\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $rolePresets = app(RolePresetRegistry::class);

        // 1. Define all permissions (P1-IAM-01)
        $permissions = array_merge(
            PermissionNaming::domain('users', ['viewAny', 'view', 'create', 'update', 'delete', 'manage_roles']),
            PermissionNaming::domain('organizations', ['viewAny', 'view', 'create', 'update', 'delete']),
            PermissionNaming::domain('categories', ['viewAny', 'create', 'update', 'delete']),
            PermissionNaming::domain('products', ['viewAny', 'view', 'create', 'update', 'delete', 'publish']),
            PermissionNaming::domain('inventory', ['viewAny', 'adjust', 'transfer']),
            PermissionNaming::domain('orders', ['viewAny', 'view', 'create', 'update', 'override_status', 'cancel', 'refund']),
            PermissionNaming::domain('customers', ['viewAny', 'view', 'create', 'update', 'delete']),
            PermissionNaming::domain('shipping', ['viewAny', 'update', 'manage_carriers']),
            PermissionNaming::domain('finance', ['viewAny', 'manage_gateways']),
            PermissionNaming::domain('payments', ['viewAny', 'refund']),
            PermissionNaming::domain('cms', ['viewAny', 'manage_pages', 'manage_blog']),
            PermissionNaming::domain('coupons', ['viewAny', 'create', 'update', 'delete']),
            PermissionNaming::domain('support', ['viewAny', 'manage_tickets']),
            PermissionNaming::domain('reports', ['viewAny']),
            PermissionNaming::domain('community', ['viewAny']),
            PermissionNaming::domain('community.groups', ['viewAny']),
            PermissionNaming::domain('community.posts', ['viewAny']),
            PermissionNaming::domain('community.reports', ['viewAny', 'update']),
            PermissionNaming::domain('community.moderation', ['viewAny', 'update']),
            PermissionNaming::domain('settings', ['view', 'update', 'manage']),
            PermissionNaming::domain('catalog.categories', ['viewAny', 'create', 'update', 'delete']),
            PermissionNaming::domain('catalog.products', ['viewAny', 'create', 'update', 'delete']),
        );

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 2. Fetch or create role presets and attach permissions (P1-IAM-03 / P1-IAM-04)
        foreach ($rolePresets->definitions() as $definition) {
            $role = Role::firstOrCreate([
                'name' => $definition['name'],
                'guard_name' => 'web',
            ]);

            if ($definition['permissions'] === ['*']) {
                $role->syncPermissions(Permission::query()->ordered()->pluck('name')->all());

                continue;
            }

            $role->syncPermissions($definition['permissions']);
        }

        // Platform Admin is intentionally broad, but not allowed the most destructive permissions.
        $platformAdmin = Role::findByName(Role::PLATFORM_ADMIN, 'web');
        $platformAdmin->revokePermissionTo(['users.delete', 'organizations.delete', 'finance.manage_gateways']);
    }
}
