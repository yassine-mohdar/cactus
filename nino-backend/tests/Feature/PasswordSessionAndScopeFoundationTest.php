<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\IAM\Notifications\CustomerResetPasswordNotification;
use App\Modules\IAM\Notifications\StaffResetPasswordNotification;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Shared\Navigation\DTOs\MenuItem;
use App\Modules\Shared\Navigation\Services\ActiveMenuResolver;
use App\Modules\Shared\Navigation\Services\MenuBuilder;
use App\Modules\Shared\Navigation\Services\MenuRegistry;
use App\Modules\Shared\Navigation\Services\MenuVisibilityResolver;
use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordSessionAndScopeFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(PermissionsSeeder::class);
        $this->activateAdminTheme('nino-v2');
    }

    public function test_staff_password_reset_request_uses_staff_reset_surface(): void
    {
        Notification::fake();

        $staff = User::factory()->create([
            'name' => 'Reset Staff',
            'email' => 'reset.staff@example.test',
            'password' => Hash::make('OldPass123!'),
            'type' => 'staff',
            'status' => 'active',
            'organization_scope' => 'platform',
        ]);

        $this->post(route('password.email'), [
            'email' => $staff->email,
        ])->assertSessionHas('status');

        Notification::assertSentTo(
            $staff,
            StaffResetPasswordNotification::class,
            fn (StaffResetPasswordNotification $notification): bool => str_contains($notification->resetUrl, '/reset-password/')
        );
    }

    public function test_customer_can_request_and_complete_password_reset_and_old_sessions_are_invalidated(): void
    {
        Notification::fake();

        $customer = User::factory()->create([
            'name' => 'Reset Customer',
            'email' => 'reset.customer@example.test',
            'password' => Hash::make('OldPass123!'),
            'type' => 'customer',
            'status' => 'active',
        ]);

        DB::table('sessions')->insert([
            'id' => 'legacy-customer-session',
            'user_id' => $customer->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Legacy Test Agent',
            'payload' => 'payload',
            'last_activity' => now()->subMinutes(15)->timestamp,
        ]);

        $this->post(route('customer.password.email'), [
            'email' => $customer->email,
        ])->assertSessionHas('status');

        $resetUrl = null;

        Notification::assertSentTo(
            $customer,
            CustomerResetPasswordNotification::class,
            function (CustomerResetPasswordNotification $notification) use (&$resetUrl): bool {
                $resetUrl = $notification->resetUrl;

                return str_contains($notification->resetUrl, '/account/reset-password/');
            }
        );

        $this->assertNotNull($resetUrl);

        $this->get($resetUrl)
            ->assertOk()
            ->assertSeeText($customer->email);

        $token = basename((string) parse_url($resetUrl, PHP_URL_PATH));

        $this->post(route('customer.password.reset.store'), [
            'token' => $token,
            'email' => $customer->email,
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ])->assertRedirect(route('customer.account.home'));

        $customer->refresh();

        $this->assertAuthenticatedAs($customer);
        $this->assertTrue(Hash::check('NewSecurePass123!', $customer->password));
        $this->assertDatabaseMissing('sessions', [
            'id' => 'legacy-customer-session',
        ]);
    }

    public function test_authenticated_customer_password_update_invalidates_other_sessions(): void
    {
        $customer = User::factory()->create([
            'email' => 'password.update@example.test',
            'password' => Hash::make('CurrentPass123!'),
            'type' => 'customer',
            'status' => 'active',
        ]);

        DB::table('sessions')->insert([
            'id' => 'other-device-session',
            'user_id' => $customer->id,
            'ip_address' => '127.0.0.2',
            'user_agent' => 'Other Device',
            'payload' => 'payload',
            'last_activity' => now()->subMinutes(5)->timestamp,
        ]);

        $this->actingAs($customer)
            ->putJson(route('api.customer.password.update'), [
                'current_password' => 'CurrentPass123!',
                'password' => 'UpdatedPass123!',
                'password_confirmation' => 'UpdatedPass123!',
            ])->assertOk();

        $customer->refresh();

        $this->assertTrue(Hash::check('UpdatedPass123!', $customer->password));
        $this->assertDatabaseMissing('sessions', [
            'id' => 'other-device-session',
        ]);
    }

    public function test_platform_scope_can_manage_cross_organization_users_and_menu_items(): void
    {
        $platform = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
            'organization_scope' => 'platform',
        ]);
        $platform->givePermissionTo(['users.viewAny', 'users.view', 'users.update']);

        $franchise = Organization::create([
            'name' => 'Franchise A',
            'type' => 'franchise',
            'status' => 'active',
        ]);

        $branch = Organization::create([
            'name' => 'Branch A1',
            'type' => 'branch',
            'status' => 'active',
            'parent_id' => $franchise->id,
        ]);

        $target = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
            'organization_scope' => 'branch',
            'organization_id' => $branch->id,
        ]);

        $this->assertTrue($platform->can('view', $target));
        $this->assertTrue($platform->can('update', $target));

        $this->actingAs($platform);

        $registry = new MenuRegistry();
        $registry->add(MenuItem::make('platform.item')->setLabel('Platform')->requirePermission('users.viewAny')->requireScope('platform'));
        $registry->add(MenuItem::make('franchise.item')->setLabel('Franchise')->requirePermission('users.viewAny')->requireScope('franchise'));

        $built = (new MenuBuilder($registry, new MenuVisibilityResolver(), new ActiveMenuResolver()))->build();

        $this->assertSame(['platform.item', 'franchise.item'], array_map(fn (MenuItem $item) => $item->key, $built));
    }

    public function test_franchise_scope_is_limited_to_its_tree_and_menu_items(): void
    {
        $franchiseA = Organization::create([
            'name' => 'Franchise A',
            'type' => 'franchise',
            'status' => 'active',
        ]);
        $branchA = Organization::create([
            'name' => 'Branch A1',
            'type' => 'branch',
            'status' => 'active',
            'parent_id' => $franchiseA->id,
        ]);
        $franchiseB = Organization::create([
            'name' => 'Franchise B',
            'type' => 'franchise',
            'status' => 'active',
        ]);
        $branchB = Organization::create([
            'name' => 'Branch B1',
            'type' => 'branch',
            'status' => 'active',
            'parent_id' => $franchiseB->id,
        ]);

        $franchiseManager = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
            'organization_scope' => 'franchise',
            'organization_id' => $franchiseA->id,
        ]);
        $franchiseManager->givePermissionTo(['users.viewAny', 'users.view', 'users.update', 'organizations.view']);

        $inScopeUser = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
            'organization_scope' => 'branch',
            'organization_id' => $branchA->id,
        ]);
        $outOfScopeUser = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
            'organization_scope' => 'branch',
            'organization_id' => $branchB->id,
        ]);

        $this->assertTrue($franchiseManager->can('view', $inScopeUser));
        $this->assertTrue($franchiseManager->can('update', $inScopeUser));
        $this->assertFalse($franchiseManager->can('view', $outOfScopeUser));
        $this->assertFalse($franchiseManager->can('view', $franchiseB));
        $this->assertTrue($franchiseManager->can('view', $branchA));

        $this->actingAs($franchiseManager);

        $registry = new MenuRegistry();
        $registry->add(MenuItem::make('platform.item')->setLabel('Platform')->requirePermission('users.viewAny')->requireScope('platform'));
        $registry->add(MenuItem::make('franchise.item')->setLabel('Franchise')->requirePermission('users.viewAny')->requireScope('franchise'));

        $built = (new MenuBuilder($registry, new MenuVisibilityResolver(), new ActiveMenuResolver()))->build();

        $this->assertSame(['franchise.item'], array_map(fn (MenuItem $item) => $item->key, $built));
    }

    public function test_branch_scope_is_limited_to_its_branch_for_users_and_organizations(): void
    {
        $franchise = Organization::create([
            'name' => 'Scoped Franchise',
            'type' => 'franchise',
            'status' => 'active',
        ]);
        $branchA = Organization::create([
            'name' => 'Branch A',
            'type' => 'branch',
            'status' => 'active',
            'parent_id' => $franchise->id,
        ]);
        $branchB = Organization::create([
            'name' => 'Branch B',
            'type' => 'branch',
            'status' => 'active',
            'parent_id' => $franchise->id,
        ]);

        $branchManager = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
            'organization_scope' => 'branch',
            'organization_id' => $branchA->id,
        ]);
        $branchManager->givePermissionTo(['users.viewAny', 'users.view', 'users.update', 'organizations.view']);

        $sameBranchUser = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
            'organization_scope' => 'branch',
            'organization_id' => $branchA->id,
        ]);
        $otherBranchUser = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
            'organization_scope' => 'branch',
            'organization_id' => $branchB->id,
        ]);

        $this->assertTrue($branchManager->can('view', $sameBranchUser));
        $this->assertTrue($branchManager->can('update', $sameBranchUser));
        $this->assertFalse($branchManager->can('view', $otherBranchUser));
        $this->assertTrue($branchManager->can('view', $branchA));
        $this->assertFalse($branchManager->can('view', $branchB));
    }

    public function test_own_scope_only_allows_access_to_self_and_own_scoped_menu_items(): void
    {
        $branch = Organization::create([
            'name' => 'Branch Own',
            'type' => 'branch',
            'status' => 'active',
        ]);

        $staff = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
            'organization_scope' => 'own',
            'organization_id' => $branch->id,
        ]);
        $staff->givePermissionTo(['users.viewAny', 'users.view', 'users.update', 'organizations.view']);

        $teammate = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
            'organization_scope' => 'branch',
            'organization_id' => $branch->id,
        ]);

        $this->assertTrue($staff->can('view', $staff));
        $this->assertTrue($staff->can('update', $staff));
        $this->assertFalse($staff->can('view', $teammate));
        $this->assertTrue($staff->can('view', $branch));

        $this->actingAs($staff);

        $registry = new MenuRegistry();
        $registry->add(MenuItem::make('own.item')->setLabel('Own')->requirePermission('users.viewAny')->requireScope('own'));
        $registry->add(MenuItem::make('branch.item')->setLabel('Branch')->requirePermission('users.viewAny')->requireScope('branch'));

        $built = (new MenuBuilder($registry, new MenuVisibilityResolver(), new ActiveMenuResolver()))->build();

        $this->assertSame(['own.item'], array_map(fn (MenuItem $item) => $item->key, $built));
    }

    private function activateAdminTheme(string $theme): void
    {
        config(['admin_theme.active' => $theme]);

        $provider = new AdminThemeServiceProvider($this->app);
        $provider->boot($this->app->make(AdminThemeManager::class));
    }
}
