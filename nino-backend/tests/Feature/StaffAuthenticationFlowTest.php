<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\IAM\Models\Role;
use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffAuthenticationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(PermissionsSeeder::class);
        $this->activateAdminTheme('nino-v2');
    }

    public function test_staff_login_page_renders_admin_auth_surface(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSeeText('Sign in to Operations Hub');
        $response->assertSeeText('Forgot your password?');
    }

    public function test_active_staff_can_login_reach_admin_home_and_logout(): void
    {
        $staff = User::factory()->create([
            'name' => 'Admin Staff',
            'email' => 'admin.staff@example.test',
            'password' => Hash::make('SecretPass123!'),
            'type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'organization_scope' => 'platform',
        ]);
        $staff->assignRole(Role::EMPLOYEE);

        $response = $this->post(route('login'), [
            'email' => $staff->email,
            'password' => 'SecretPass123!',
            'remember' => '1',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($staff);

        $this->get(route('admin.dashboard'))->assertOk();

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_customer_inactive_staff_and_suspended_staff_cannot_use_staff_login_surface(): void
    {
        $customer = User::factory()->create([
            'email' => 'customer.on.staff.surface@example.test',
            'password' => Hash::make('SecretPass123!'),
            'type' => User::TYPE_CUSTOMER,
            'status' => User::STATUS_ACTIVE,
        ]);

        $inactiveStaff = User::factory()->create([
            'email' => 'inactive.staff@example.test',
            'password' => Hash::make('SecretPass123!'),
            'type' => User::TYPE_STAFF,
            'status' => User::STATUS_INACTIVE,
        ]);

        $suspendedStaff = User::factory()->create([
            'email' => 'suspended.staff@example.test',
            'password' => Hash::make('SecretPass123!'),
            'type' => User::TYPE_STAFF,
            'status' => User::STATUS_SUSPENDED,
        ]);

        foreach ([$customer, $inactiveStaff, $suspendedStaff] as $user) {
            $this->post(route('login'), [
                'email' => $user->email,
                'password' => 'SecretPass123!',
            ])->assertSessionHasErrors('email');

            $this->assertGuest();
        }
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
