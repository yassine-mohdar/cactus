<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\IAM\Models\Role;
use App\Modules\Organizations\Models\Organization;
use App\Providers\AdminThemeServiceProvider;
use App\Support\AdminThemeManager;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffManagementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(PermissionsSeeder::class);
        $this->activateAdminTheme('nino-v2');
    }

    public function test_staff_create_page_renders_the_add_staff_ui(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $response = $this->actingAs($superAdmin)->get(route('admin.staff.create'));

        $response->assertOk();
        $response->assertSeeText('Profile information');
        $response->assertSeeText('Access and roles');
        $response->assertSeeText('Create Staff Member');
    }

    public function test_staff_edit_page_renders_the_edit_staff_ui(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $staff = User::factory()->create([
            'name' => 'Edit Me',
            'email' => 'edit.me@example.test',
            'type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'organization_scope' => 'platform',
        ]);
        $staff->assignRole('Employee');

        $response = $this->actingAs($superAdmin)->get(route('admin.staff.edit', $staff));

        $response->assertOk();
        $response->assertSee('edit.me@example.test', false);
        $response->assertSeeText('Update Staff Member');
        $response->assertSeeText('Account state');
    }

    public function test_staff_store_assigns_roles_and_scope(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $franchise = Organization::factory()->franchise()->create();
        $branch = Organization::factory()->branch($franchise)->create();

        $response = $this->actingAs($superAdmin)->post(route('admin.staff.store'), [
            'name' => 'Scoped Operator',
            'email' => 'scoped.operator@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'status' => 'active',
            'organization_scope' => 'branch',
            'organization_id' => $branch->id,
            'roles' => ['Shipping Agent'],
        ]);

        $response->assertRedirect(route('admin.staff.index'));

        $staff = User::query()->where('email', 'scoped.operator@example.test')->firstOrFail();

        $this->assertSame('branch', $staff->organization_scope);
        $this->assertSame($branch->id, $staff->organization_id);
        $this->assertTrue($staff->hasRole('Shipping Agent'));
    }

    public function test_staff_update_can_deactivate_and_reactivate_a_staff_member(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $staff = User::factory()->create([
            'name' => 'Lifecycle Staff',
            'email' => 'lifecycle.staff@example.test',
            'type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'organization_scope' => 'platform',
        ]);
        $staff->assignRole('Employee');

        $this->actingAs($superAdmin)->put(route('admin.staff.update', $staff), [
            'name' => $staff->name,
            'email' => $staff->email,
            'phone' => '',
            'status' => 'inactive',
            'roles' => ['Employee'],
            'organization_scope' => 'platform',
        ])->assertRedirect(route('admin.staff.index'));

        $staff->refresh();

        $this->assertSame(User::STATUS_INACTIVE, $staff->status);

        $this->actingAs($superAdmin)->put(route('admin.staff.update', $staff), [
            'name' => $staff->name,
            'email' => $staff->email,
            'phone' => '',
            'status' => 'active',
            'roles' => ['Employee'],
            'organization_scope' => 'platform',
        ])->assertRedirect(route('admin.staff.index'));

        $staff->refresh();

        $this->assertSame(User::STATUS_ACTIVE, $staff->status);
    }

    private function createSuperAdmin(): User
    {
        $user = User::factory()->create([
            'type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'organization_scope' => 'platform',
        ]);
        $user->assignRole(Role::findByName('Super Admin'));

        return $user;
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
