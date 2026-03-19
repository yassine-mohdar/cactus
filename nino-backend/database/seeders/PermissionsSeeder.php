<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define all permissions (P1-IAM-01)
        $permissions = [
            // IAM / Users
            'users.viewAny', 'users.view', 'users.create', 'users.update', 'users.delete', 'users.manage_roles',
            
            // Organizations
            'organizations.viewAny', 'organizations.view', 'organizations.create', 'organizations.update', 'organizations.delete',
            
            // Catalog
            'categories.viewAny', 'categories.create', 'categories.update', 'categories.delete',
            'products.viewAny', 'products.view', 'products.create', 'products.update', 'products.delete', 'products.publish',
            
            // Inventory
            'inventory.viewAny', 'inventory.adjust', 'inventory.transfer',
            
            // Orders
            'orders.viewAny', 'orders.view', 'orders.create', 'orders.update', 'orders.cancel', 'orders.refund',
            
            // Customers
            'customers.viewAny', 'customers.view', 'customers.create', 'customers.update', 'customers.delete',
            
            // Shipping
            'shipping.viewAny', 'shipping.update', 'shipping.manage_carriers',
            
            // Finance / Payments
            'finance.viewAny', 'finance.manage_gateways', 'payments.viewAny', 'payments.refund',
            
            // CMS / Marketing
            'cms.viewAny', 'cms.manage_pages', 'cms.manage_blog',
            'coupons.viewAny', 'coupons.create', 'coupons.update', 'coupons.delete',
            
            // Support
            'support.viewAny', 'support.manage_tickets',
            
            // Reports
            'reports.viewAny',

            // Settings
            'settings.view', 'settings.update',
            'settings.manage',

            // Catalog
            'catalog.categories.viewAny',
            'catalog.categories.create',
            'catalog.categories.update',
            'catalog.categories.delete',

            'catalog.products.viewAny',
            'catalog.products.create',
            'catalog.products.update',
            'catalog.products.delete',
        ];

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 2. Fetch or create roles
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $platformAdmin = Role::firstOrCreate(['name' => 'Platform Admin', 'guard_name' => 'web']);
        $franchiseManager = Role::firstOrCreate(['name' => 'Franchise Manager', 'guard_name' => 'web']);
        $branchManager = Role::firstOrCreate(['name' => 'Branch Manager', 'guard_name' => 'web']);
        $customerSupport = Role::firstOrCreate(['name' => 'Customer Support Agent', 'guard_name' => 'web']);
        $shippingAgent = Role::firstOrCreate(['name' => 'Shipping Agent', 'guard_name' => 'web']);
        $financeManager = Role::firstOrCreate(['name' => 'Finance Manager', 'guard_name' => 'web']);
        $contentManager = Role::firstOrCreate(['name' => 'SEO / Content Manager', 'guard_name' => 'web']);
        $marketingManager = Role::firstOrCreate(['name' => 'Media Buying / Marketing Manager', 'guard_name' => 'web']);
        $stockManager = Role::firstOrCreate(['name' => 'Stock Manager', 'guard_name' => 'web']);
        
        // 3. Assign permissions to roles (P1-IAM-03)

        // Super Admin gets everything via a Gate::before rule usually, but we can assign all just in case
        $superAdmin->givePermissionTo(Permission::all());

        // Platform Admin (Everything except highly sensitive destructive or foundational settings maybe, but here mostly everything)
        $platformAdmin->givePermissionTo(Permission::all());
        $platformAdmin->revokePermissionTo(['users.delete', 'organizations.delete', 'finance.manage_gateways']);

        // Franchise Manager (Scoped later to franchise via policies, but gets foundational rights here)
        $franchiseManager->givePermissionTo([
            'users.viewAny', 'users.view', 'users.create', 'users.update',
            'organizations.viewAny', 'organizations.view',
            'products.viewAny', 'products.view',
            'inventory.viewAny', 'inventory.adjust', 'inventory.transfer',
            'orders.viewAny', 'orders.view', 'orders.create', 'orders.update', 'orders.cancel',
            'customers.viewAny', 'customers.view',
            'reports.viewAny' ?? 'settings.view', // Note: missing reports.viewAny from array above, adding placeholder
        ]);

        // Branch Manager (Scoped later to branch. More limited)
        $branchManager->givePermissionTo([
            'users.viewAny', 'users.view',
            'organizations.view',
            'products.viewAny', 'products.view',
            'inventory.viewAny', 'inventory.adjust',
            'orders.viewAny', 'orders.view', 'orders.update', // Mostly order fulfillment
            'customers.viewAny', 'customers.view',
            'shipping.viewAny', 'shipping.update'
        ]);

        // Customer Support Agent
        $customerSupport->givePermissionTo([
            'users.viewAny', 'users.view',
            'orders.viewAny', 'orders.view', 'orders.update', 'orders.cancel', // maybe refunds if policy allows
            'customers.viewAny', 'customers.view', 'customers.update',
            'support.viewAny', 'support.manage_tickets',
            'shipping.viewAny'
        ]);

        // Shipping Agent
        $shippingAgent->givePermissionTo([
            'orders.viewAny', 'orders.view', 'orders.update', // specifically marking shipped
            'shipping.viewAny', 'shipping.update'
        ]);

        // Finance Manager
        $financeManager->givePermissionTo([
            'orders.viewAny', 'orders.view',
            'finance.viewAny', 'finance.manage_gateways',
            'payments.viewAny', 'payments.refund',
            'reports.viewAny' ?? 'settings.view',
        ]);

        // Content / SEO
        $contentManager->givePermissionTo([
            'categories.viewAny', 'categories.create', 'categories.update',
            'products.viewAny', 'products.view', 'products.create', 'products.update',
            'cms.viewAny', 'cms.manage_pages', 'cms.manage_blog',
            'settings.view'
        ]);

        // Stock Manager
        $stockManager->givePermissionTo([
            'products.viewAny', 'products.view',
            'inventory.viewAny', 'inventory.adjust', 'inventory.transfer',
        ]);
        
        // Marketing Manager
        $marketingManager->givePermissionTo([
            'products.viewAny', 'products.view',
            'customers.viewAny', 'customers.view',
            'coupons.viewAny', 'coupons.create', 'coupons.update', 'coupons.delete',
            'cms.viewAny',
        ]);
    }
}
