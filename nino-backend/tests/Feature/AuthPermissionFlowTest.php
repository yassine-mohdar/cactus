<?php

namespace Tests\Feature;

use App\Models\User;
use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuthPermissionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->activateAdminTheme('nino-v2');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('settings.manage', 'web');
    }

    public function test_guest_is_redirected_to_login_when_accessing_admin_settings(): void
    {
        $response = $this->get(route('admin.settings.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_staff_without_settings_permission_receives_forbidden_response(): void
    {
        $staffUser = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
        ]);

        $response = $this->actingAs($staffUser)->get(route('admin.settings.index'));

        $response->assertForbidden();
    }

    public function test_staff_with_settings_permission_can_view_admin_settings(): void
    {
        $staffUser = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
        ]);
        $staffUser->givePermissionTo('settings.manage');

        $response = $this->actingAs($staffUser)->get(route('admin.settings.index'));

        $response->assertOk();
        $response->assertViewIs('admin.settings.index');
        $response->assertSee('General');
    }

    private function activateAdminTheme(string $theme): void
    {
        config(['admin_theme.active' => $theme]);

        $provider = new AdminThemeServiceProvider($this->app);
        $provider->boot($this->app->make(AdminThemeManager::class));
    }
}
