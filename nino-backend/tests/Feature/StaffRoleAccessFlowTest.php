<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\IAM\Notifications\StaffAccessSetupNotification;
use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StaffRoleAccessFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(PermissionsSeeder::class);
        $this->activateAdminTheme('nino-v2');
    }

    public function test_super_admin_can_create_a_custom_role_with_permissions(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $response = $this->actingAs($superAdmin)->post(route('admin.staff.roles.store'), [
            'name' => 'Returns Specialist',
            'permissions' => [
                'orders.viewAny',
                'orders.update',
                'support.manage_tickets',
            ],
        ]);

        $response->assertRedirect(route('admin.staff.roles.index'));

        $role = Role::findByName('Returns Specialist');

        $this->assertSame(
            ['orders.update', 'orders.viewAny', 'support.manage_tickets'],
            $role->permissions->pluck('name')->sort()->values()->all(),
        );
    }

    public function test_staff_update_supports_direct_permission_overrides(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $staff = User::factory()->create([
            'name' => 'Override Staff',
            'email' => 'override.staff@example.test',
            'type' => 'staff',
            'status' => 'active',
        ]);
        $staff->assignRole('Employee');

        $response = $this->actingAs($superAdmin)->put(route('admin.staff.update', $staff), [
            'name' => $staff->name,
            'email' => $staff->email,
            'phone' => '',
            'status' => 'active',
            'roles' => ['Employee'],
            'permission_overrides' => ['settings.manage'],
            'organization_scope' => 'platform',
        ]);

        $response->assertRedirect(route('admin.staff.index'));

        $staff->refresh();

        $this->assertTrue($staff->hasDirectPermission('settings.manage'));

        $this->actingAs($staff)
            ->get(route('admin.settings.index'))
            ->assertOk();
    }

    public function test_staff_access_setup_link_can_be_sent_and_completed(): void
    {
        Notification::fake();

        $superAdmin = $this->createSuperAdmin();

        $staff = User::factory()->create([
            'name' => 'Shipping User',
            'email' => 'shipping.user@example.test',
            'type' => 'staff',
            'status' => 'inactive',
        ]);
        $staff->assignRole('Shipping Agent');

        $this->actingAs($superAdmin)
            ->from(route('admin.staff.edit', $staff))
            ->post(route('admin.staff.access-link.store', $staff))
            ->assertRedirect(route('admin.staff.edit', $staff));

        $setupUrl = null;

        Notification::assertSentTo(
            $staff,
            StaffAccessSetupNotification::class,
            function (StaffAccessSetupNotification $notification) use (&$setupUrl): bool {
                $setupUrl = $notification->setupUrl;

                return str_contains($notification->setupUrl, '/staff/access/setup/');
            }
        );

        $this->assertNotNull($setupUrl);

        auth()->logout();

        $this->get($setupUrl)
            ->assertOk()
            ->assertSee($staff->email, false);

        $token = basename((string) parse_url($setupUrl, PHP_URL_PATH));

        $this->post(route('staff.access.setup.store'), [
            'email' => $staff->email,
            'token' => $token,
            'password' => 'StaffSecurePass123!',
            'password_confirmation' => 'StaffSecurePass123!',
        ])->assertRedirect(route('admin.shipping.shipments.index'));

        $staff->refresh();

        $this->assertAuthenticatedAs($staff);
        $this->assertSame('active', $staff->status);
        $this->assertTrue(Hash::check('StaffSecurePass123!', $staff->password));
    }

    public function test_custom_role_permissions_drive_staff_home_route_after_login(): void
    {
        $role = Role::create([
            'name' => 'Support Specialist',
            'guard_name' => 'web',
        ]);
        $role->syncPermissions(['support.viewAny', 'support.manage_tickets']);

        $staff = User::factory()->create([
            'email' => 'support.specialist@example.test',
            'password' => Hash::make('Password123!'),
            'type' => 'staff',
            'status' => 'active',
        ]);
        $staff->assignRole($role);

        $response = $this->from(route('login'))->post('/login', [
            'email' => $staff->email,
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('admin.support.lookup'));
    }

    public function test_super_admin_can_revoke_staff_sessions_and_rotate_remember_token(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $staff = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
            'remember_token' => 'legacy-token',
        ]);

        \Illuminate\Support\Facades\DB::table('sessions')->insert([
            'id' => 'staff-revoke-session',
            'user_id' => $staff->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Revocation Test',
            'payload' => 'payload',
            'last_activity' => now()->subMinutes(10)->timestamp,
        ]);

        $this->actingAs($superAdmin)
            ->from(route('admin.staff.edit', $staff))
            ->post(route('admin.staff.sessions.revoke', $staff))
            ->assertRedirect(route('admin.staff.edit', $staff));

        $staff->refresh();

        $this->assertDatabaseMissing('sessions', [
            'id' => 'staff-revoke-session',
        ]);
        $this->assertNotSame('legacy-token', $staff->remember_token);

        $audit = AuditLog::query()->where('action', 'staff.sessions.revoked')->latest('id')->first();

        $this->assertNotNull($audit);
        $this->assertSame('staff_session_controller', $audit->context['source'] ?? null);
        $this->assertSame('manual', $audit->new_values['reason'] ?? null);
    }

    public function test_staff_update_revokes_sessions_when_account_is_suspended(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $staff = User::factory()->create([
            'name' => 'Suspendable Staff',
            'email' => 'suspendable.staff@example.test',
            'type' => 'staff',
            'status' => 'active',
            'organization_scope' => 'platform',
        ]);
        $staff->assignRole('Employee');

        \Illuminate\Support\Facades\DB::table('sessions')->insert([
            'id' => 'staff-suspend-session',
            'user_id' => $staff->id,
            'ip_address' => '127.0.0.2',
            'user_agent' => 'Suspension Test',
            'payload' => 'payload',
            'last_activity' => now()->subMinutes(10)->timestamp,
        ]);

        $this->actingAs($superAdmin)->put(route('admin.staff.update', $staff), [
            'name' => $staff->name,
            'email' => $staff->email,
            'phone' => '',
            'status' => 'suspended',
            'roles' => ['Employee'],
            'organization_scope' => 'platform',
        ])->assertRedirect(route('admin.staff.index'));

        $this->assertDatabaseMissing('sessions', [
            'id' => 'staff-suspend-session',
        ]);

        $audit = AuditLog::query()->where('action', 'staff.sessions.revoked')->latest('id')->first();

        $this->assertNotNull($audit);
        $this->assertSame('staff_controller', $audit->context['source'] ?? null);
        $this->assertSame('status_changed', $audit->new_values['reason'] ?? null);
    }

    private function createSuperAdmin(): User
    {
        $user = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
            'organization_scope' => 'platform',
        ]);
        $user->assignRole('Super Admin');

        return $user;
    }

    private function activateAdminTheme(string $theme): void
    {
        config(['admin_theme.active' => $theme]);

        $provider = new AdminThemeServiceProvider($this->app);
        $provider->boot($this->app->make(AdminThemeManager::class));
    }
}
