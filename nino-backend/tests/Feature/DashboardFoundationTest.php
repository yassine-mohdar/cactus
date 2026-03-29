<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\IAM\Models\Role;
use App\Modules\Organizations\Models\Organization;
use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DashboardFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_dashboard_supports_lightweight_date_presets_and_invalid_values_fall_back(): void
    {
        $this->activateAdminTheme('nino-v2');

        $user = $this->makeDashboardUser();

        $this->actingAs($user)
            ->get(route('admin.dashboard', ['preset' => '7d']))
            ->assertOk()
            ->assertSee('Dashboard window')
            ->assertSee('7D')
            ->assertSee('30D')
            ->assertSee('90D')
            ->assertSee('Last 7 days');

        $this->actingAs($user)
            ->get(route('admin.dashboard', ['preset' => 'invalid']))
            ->assertOk()
            ->assertSee('Last 30 days');
    }

    public function test_dashboard_renders_empty_states_and_widget_sections_on_fresh_data(): void
    {
        $this->activateAdminTheme('nino-v2');

        $user = $this->makeDashboardUser('Platform Admin');

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Overview')
            ->assertSee('Performance')
            ->assertSee('Live Operations')
            ->assertSee('No product revenue yet')
            ->assertSee('No order mix yet')
            ->assertSee('No orders yet');
    }

    public function test_super_admin_dashboard_renders_executive_widgets_and_platform_admin_does_not(): void
    {
        $this->activateAdminTheme('nino-v2');

        $superAdmin = $this->makeDashboardUser('Super Admin');
        $platformAdmin = $this->makeDashboardUser('Platform Admin');

        $this->actingAs($superAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Executive Pulse')
            ->assertSee('GMV Summary')
            ->assertSee('Orders Today')
            ->assertSee('Revenue Trend')
            ->assertSee('Failed Payments')
            ->assertSee('Network Health')
            ->assertSee('Low Stock Alerts')
            ->assertSee('Shipping Exceptions')
            ->assertSee('Franchise Performance Summary')
            ->assertSee('Branch Performance Summary')
            ->assertSee('Command Center')
            ->assertSee('Support KPI Widget')
            ->assertSee('Finance Summary Widget')
            ->assertSee('System Alerts Widget')
            ->assertSee('Platform Action Queue')
            ->assertSee('Platform Admin Workspace')
            ->assertSee('Recent Orders Widget')
            ->assertSee('Stock Alerts Widget')
            ->assertSee('Payment Exceptions Widget')
            ->assertSee('Content/Promo Highlights Widget');

        $this->actingAs($platformAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Platform Action Queue')
            ->assertSee('Platform Admin Workspace')
            ->assertSee('Recent Orders Widget')
            ->assertSee('Stock Alerts Widget')
            ->assertSee('Payment Exceptions Widget')
            ->assertSee('Content/Promo Highlights Widget')
            ->assertDontSee('Executive Pulse')
            ->assertDontSee('GMV Summary')
            ->assertDontSee('Failed Payments')
            ->assertDontSee('Network Health')
            ->assertDontSee('Low Stock Alerts')
            ->assertDontSee('Shipping Exceptions')
            ->assertDontSee('Franchise Performance Summary')
            ->assertDontSee('Branch Performance Summary')
            ->assertDontSee('Command Center')
            ->assertDontSee('Support KPI Widget')
            ->assertDontSee('Finance Summary Widget')
            ->assertDontSee('System Alerts Widget');
    }

    public function test_nino_v1_dashboard_uses_the_same_dashboard_payload_contract(): void
    {
        $this->activateAdminTheme('nino-v1');

        $user = $this->makeDashboardUser('Platform Admin');

        $this->actingAs($user)
            ->get(route('admin.dashboard', ['preset' => '90d']))
            ->assertOk()
            ->assertSee('Dashboard window')
            ->assertSee('Last 90 days')
            ->assertSee('Performance Widgets')
            ->assertSee('Operations');
    }

    public function test_franchise_manager_dashboard_renders_franchise_workspace_and_platform_admin_does_not(): void
    {
        $this->activateAdminTheme('nino-v2');

        $platform = Organization::factory()->platform()->create();
        $franchise = Organization::factory()->franchise($platform)->create();
        Organization::factory()->count(2)->branch($franchise)->create();

        $franchiseManager = $this->makeDashboardUser('Franchise Manager', [
            'organization_id' => $franchise->id,
            'organization_scope' => 'franchise',
        ]);

        $platformAdmin = $this->makeDashboardUser('Platform Admin');

        $this->actingAs($franchiseManager)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Franchise Workspace')
            ->assertSee('Sales by Branch Widget')
            ->assertSee('Branch Stock Overview Widget')
            ->assertSee('Staff Performance Summary Widget')
            ->assertSee('Branch Order Metrics Widget')
            ->assertDontSee('Platform Admin Workspace')
            ->assertDontSee('Command Center');

        $this->actingAs($platformAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Franchise Workspace')
            ->assertDontSee('Sales by Branch Widget')
            ->assertDontSee('Branch Stock Overview Widget')
            ->assertDontSee('Staff Performance Summary Widget')
            ->assertDontSee('Branch Order Metrics Widget');
    }

    public function test_branch_manager_dashboard_renders_branch_workspace_and_higher_scopes_do_not(): void
    {
        $this->activateAdminTheme('nino-v2');

        $platform = Organization::factory()->platform()->create();
        $franchise = Organization::factory()->franchise($platform)->create();
        $branch = Organization::factory()->branch($franchise)->create();

        $branchManager = $this->makeDashboardUser('Branch Manager', [
            'organization_id' => $branch->id,
            'organization_scope' => 'branch',
        ]);

        $platformAdmin = $this->makeDashboardUser('Platform Admin');
        $franchiseManager = $this->makeDashboardUser('Franchise Manager', [
            'organization_id' => $franchise->id,
            'organization_scope' => 'franchise',
        ]);

        $this->actingAs($branchManager)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Branch Workspace')
            ->assertSee('Orders to Prepare Widget')
            ->assertSee('Branch Stock Widgets')
            ->assertSee('Dispatch Queue Widget')
            ->assertSee('Branch Issue Queue Widget')
            ->assertDontSee('Platform Admin Workspace')
            ->assertDontSee('Franchise Workspace');

        $this->actingAs($platformAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Branch Workspace')
            ->assertDontSee('Orders to Prepare Widget')
            ->assertDontSee('Branch Stock Widgets')
            ->assertDontSee('Dispatch Queue Widget')
            ->assertDontSee('Branch Issue Queue Widget');

        $this->actingAs($franchiseManager)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Branch Workspace')
            ->assertDontSee('Orders to Prepare Widget')
            ->assertDontSee('Branch Stock Widgets')
            ->assertDontSee('Dispatch Queue Widget')
            ->assertDontSee('Branch Issue Queue Widget');
    }

    public function test_customer_support_agent_dashboard_renders_support_workspace_and_platform_admin_does_not(): void
    {
        $this->activateAdminTheme('nino-v2');

        $supportAgent = $this->makeDashboardUser('Customer Support Agent');
        $platformAdmin = $this->makeDashboardUser('Platform Admin');

        $this->actingAs($supportAgent)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Support Workspace')
            ->assertSee('Order Lookup Widget')
            ->assertSee('Customer Lookup Widget')
            ->assertSee('Open Cases / Refund Queue Widget')
            ->assertSee('Recent Issue Timeline Widget')
            ->assertDontSee('Platform Admin Workspace')
            ->assertDontSee('Branch Workspace')
            ->assertDontSee('Franchise Workspace');

        $this->actingAs($platformAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Support Workspace')
            ->assertDontSee('Order Lookup Widget')
            ->assertDontSee('Customer Lookup Widget')
            ->assertDontSee('Open Cases / Refund Queue Widget')
            ->assertDontSee('Recent Issue Timeline Widget');
    }

    public function test_shipping_agent_dashboard_renders_shipping_workspace_and_platform_admin_does_not(): void
    {
        $this->activateAdminTheme('nino-v2');

        $shippingAgent = $this->makeDashboardUser('Shipping Agent');
        $platformAdmin = $this->makeDashboardUser('Platform Admin');

        $this->actingAs($shippingAgent)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Shipping Workspace')
            ->assertSee('Ready to Ship Widget')
            ->assertSee('Shipped Widget')
            ->assertSee('Failed Delivery Widget')
            ->assertSee('Returned Parcel Queue Widget')
            ->assertSee('Tracking Queue Widget')
            ->assertDontSee('Platform Admin Workspace')
            ->assertDontSee('Support Workspace');

        $this->actingAs($platformAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Shipping Workspace')
            ->assertDontSee('Ready to Ship Widget')
            ->assertDontSee('Shipped Widget')
            ->assertDontSee('Failed Delivery Widget')
            ->assertDontSee('Returned Parcel Queue Widget')
            ->assertDontSee('Tracking Queue Widget');
    }

    public function test_finance_manager_dashboard_renders_finance_workspace_and_platform_admin_does_not(): void
    {
        $this->activateAdminTheme('nino-v2');

        $financeManager = $this->makeDashboardUser('Finance Manager');
        $platformAdmin = $this->makeDashboardUser('Platform Admin');

        $this->actingAs($financeManager)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Finance Workspace')
            ->assertSee('Paid / Unpaid Summary Widget')
            ->assertSee('Payment Method Summary Widget')
            ->assertSee('Gateway Transactions Summary Widget')
            ->assertSee('COD Reconciliation Summary Widget')
            ->assertSee('Refund Summary Widget')
            ->assertSee('Discount / Fee Impact Widget')
            ->assertDontSee('Platform Admin Workspace')
            ->assertDontSee('Shipping Workspace');

        $this->actingAs($platformAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Finance Workspace')
            ->assertDontSee('Paid / Unpaid Summary Widget')
            ->assertDontSee('Payment Method Summary Widget')
            ->assertDontSee('Gateway Transactions Summary Widget')
            ->assertDontSee('COD Reconciliation Summary Widget')
            ->assertDontSee('Refund Summary Widget')
            ->assertDontSee('Discount / Fee Impact Widget');
    }

    public function test_content_manager_dashboard_renders_content_workspace_widgets_and_platform_admin_does_not(): void
    {
        $this->activateAdminTheme('nino-v2');

        $contentManager = $this->makeDashboardUser('SEO / Content Manager');
        $platformAdmin = $this->makeDashboardUser('Platform Admin');

        $this->actingAs($contentManager)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Content Workspace')
            ->assertSee('Drafts Widget')
            ->assertSee('Missing Metadata Widget')
            ->assertSee('SEO Issue Summary Widget')
            ->assertSee('Scheduled Content Widget')
            ->assertDontSee('Platform Admin Workspace')
            ->assertDontSee('Finance Workspace')
            ->assertDontSee('Media Buying Workspace');

        $this->actingAs($platformAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Content Workspace')
            ->assertDontSee('Drafts Widget')
            ->assertDontSee('Missing Metadata Widget')
            ->assertDontSee('SEO Issue Summary Widget')
            ->assertDontSee('Scheduled Content Widget');
    }

    public function test_marketing_manager_dashboard_renders_media_workspace_and_platform_admin_does_not(): void
    {
        $this->activateAdminTheme('nino-v2');

        $marketingManager = $this->makeDashboardUser('Media Buying / Marketing Manager');
        $platformAdmin = $this->makeDashboardUser('Platform Admin');

        $this->actingAs($marketingManager)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Media Buying Workspace')
            ->assertSee('Integration Health Widget')
            ->assertSee('Campaign Hooks Widget')
            ->assertSee('Traffic / Campaign Placeholder Widget')
            ->assertDontSee('Platform Admin Workspace')
            ->assertDontSee('Content Workspace')
            ->assertDontSee('Sales Workspace');

        $this->actingAs($platformAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Media Buying Workspace')
            ->assertDontSee('Integration Health Widget')
            ->assertDontSee('Campaign Hooks Widget')
            ->assertDontSee('Traffic / Campaign Placeholder Widget');
    }

    public function test_sales_manager_dashboard_renders_sales_workspace_and_platform_admin_does_not(): void
    {
        $this->activateAdminTheme('nino-v2');

        $salesManager = $this->makeDashboardUser('Sales Manager');
        $platformAdmin = $this->makeDashboardUser('Platform Admin');

        $this->actingAs($salesManager)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Sales Workspace')
            ->assertSee('Revenue Trend Widget')
            ->assertSee('AOV Widget')
            ->assertSee('Best Sellers Widget')
            ->assertSee('Promo Performance Widget')
            ->assertDontSee('Platform Admin Workspace')
            ->assertDontSee('Media Buying Workspace')
            ->assertDontSee('Stock Workspace');

        $this->actingAs($platformAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Sales Workspace')
            ->assertDontSee('Revenue Trend Widget')
            ->assertDontSee('AOV Widget')
            ->assertDontSee('Best Sellers Widget')
            ->assertDontSee('Promo Performance Widget');
    }

    public function test_stock_manager_dashboard_renders_stock_workspace_and_platform_admin_does_not(): void
    {
        $this->activateAdminTheme('nino-v2');

        $stockManager = $this->makeDashboardUser('Stock Manager');
        $platformAdmin = $this->makeDashboardUser('Platform Admin');

        $this->actingAs($stockManager)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Stock Workspace')
            ->assertSee('Low Stock Widget')
            ->assertSee('Damaged Stock Widget')
            ->assertSee('Adjustment Summary Widget')
            ->assertSee('Transfer-Ready Metrics Widget')
            ->assertDontSee('Platform Admin Workspace')
            ->assertDontSee('Sales Workspace')
            ->assertDontSee('Community Moderator Workspace');

        $this->actingAs($platformAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Stock Workspace')
            ->assertDontSee('Low Stock Widget')
            ->assertDontSee('Damaged Stock Widget')
            ->assertDontSee('Adjustment Summary Widget')
            ->assertDontSee('Transfer-Ready Metrics Widget');
    }

    public function test_community_moderator_dashboard_renders_placeholder_workspace_and_platform_admin_does_not(): void
    {
        $this->activateAdminTheme('nino-v2');

        $moderator = $this->makeDashboardUser('Community Moderator');
        $platformAdmin = $this->makeDashboardUser('Platform Admin');

        $this->actingAs($moderator)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Community Moderator Workspace')
            ->assertSee('Report Queue Placeholder Widget')
            ->assertDontSee('Platform Admin Workspace')
            ->assertDontSee('Stock Workspace');

        $this->actingAs($platformAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Community Moderator Workspace')
            ->assertDontSee('Report Queue Placeholder Widget');
    }

    private function makeDashboardUser(string $roleName = 'Super Admin', array $attributes = []): User
    {
        $role = Role::findOrCreate($roleName, 'web');

        $user = User::factory()->create(array_merge([
            'type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
        ], $attributes));

        $user->assignRole($role);

        return $user;
    }

    private function activateAdminTheme(string $theme): void
    {
        config(['admin_theme.active' => $theme]);

        $provider = new AdminThemeServiceProvider($this->app);
        $provider->boot($this->app->make(AdminThemeManager::class));
    }
}
