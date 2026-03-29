<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Services\OperationalScopeResolver;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationAssignmentAndStaffIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
        $this->withoutVite();
    }

    public function test_platform_admin_can_assign_staff_to_branch_and_sync_primary_organization(): void
    {
        $platformAdmin = User::factory()->create([
            'type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'organization_scope' => 'platform',
        ]);
        $platformAdmin->givePermissionTo(['users.create']);

        $franchise = Organization::factory()->franchise()->create();
        $branch = Organization::factory()->branch($franchise)->create();

        $response = $this->actingAs($platformAdmin)->post(route('admin.staff.store'), [
            'name' => 'Branch Operator',
            'email' => 'branch.operator@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'status' => 'active',
            'organization_scope' => 'branch',
            'organization_id' => $branch->id,
            'roles' => [],
        ]);

        $response->assertRedirect(route('admin.staff.index'));

        $staff = User::query()->where('email', 'branch.operator@example.test')->firstOrFail();

        $this->assertSame('branch', $staff->organization_scope);
        $this->assertSame($branch->id, $staff->organization_id);
        $this->assertSame([$branch->id], $staff->organizations()->pluck('organizations.id')->all());
    }

    public function test_franchise_manager_cannot_assign_staff_outside_their_tree(): void
    {
        $franchiseA = Organization::factory()->franchise()->create();
        $franchiseB = Organization::factory()->franchise()->create();
        $branchA = Organization::factory()->branch($franchiseA)->create();
        $branchB = Organization::factory()->branch($franchiseB)->create();

        $franchiseManager = User::factory()->create([
            'type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'organization_scope' => 'franchise',
            'organization_id' => $franchiseA->id,
        ]);
        $franchiseManager->givePermissionTo(['users.create']);

        $response = $this->actingAs($franchiseManager)->post(route('admin.staff.store'), [
            'name' => 'Out Of Tree',
            'email' => 'out.of.tree@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'status' => 'active',
            'organization_scope' => 'branch',
            'organization_id' => $branchB->id,
            'roles' => [],
        ]);

        $response->assertSessionHasErrors('organization_id');
        $this->assertDatabaseMissing('users', ['email' => 'out.of.tree@example.test']);

        $validResponse = $this->actingAs($franchiseManager)->post(route('admin.staff.store'), [
            'name' => 'In Tree',
            'email' => 'in.tree@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'status' => 'active',
            'organization_scope' => 'branch',
            'organization_id' => $branchA->id,
            'roles' => [],
        ]);

        $validResponse->assertRedirect(route('admin.staff.index'));
    }

    public function test_operational_scope_resolver_prepares_branch_ids_for_branch_aware_modules(): void
    {
        $franchise = Organization::factory()->franchise()->create();
        $branchA = Organization::factory()->branch($franchise)->create();
        $branchB = Organization::factory()->branch($franchise)->create();

        $franchiseManager = User::factory()->create([
            'type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'organization_scope' => 'franchise',
            'organization_id' => $franchise->id,
        ]);

        $resolver = app(OperationalScopeResolver::class);

        $this->assertEqualsCanonicalizing(
            [$branchA->id, $branchB->id],
            $resolver->branchIdsFor($franchiseManager),
        );

        $branchManager = User::factory()->create([
            'type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'organization_scope' => 'branch',
            'organization_id' => $branchA->id,
        ]);

        $this->assertSame([$branchA->id], $resolver->branchIdsFor($branchManager));
    }

    public function test_staff_index_supports_status_scope_and_organization_filters(): void
    {
        $platformAdmin = User::factory()->create([
            'type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'organization_scope' => 'platform',
        ]);
        $platformAdmin->givePermissionTo(['users.viewAny']);

        $franchise = Organization::factory()->franchise()->create(['name' => 'Casablanca Franchise']);
        $branch = Organization::factory()->branch($franchise)->create(['name' => 'Maarif Branch']);

        User::factory()->create([
            'name' => 'Branch Active',
            'first_name' => 'Branch',
            'last_name' => 'Active',
            'type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'organization_scope' => 'branch',
            'organization_id' => $branch->id,
        ]);
        User::factory()->create([
            'name' => 'Franchise Suspended',
            'first_name' => 'Franchise',
            'last_name' => 'Suspended',
            'type' => User::TYPE_STAFF,
            'status' => User::STATUS_SUSPENDED,
            'organization_scope' => 'franchise',
            'organization_id' => $franchise->id,
        ]);

        $response = $this->actingAs($platformAdmin)->get(route('admin.staff.index', [
            'status' => 'active',
            'scope' => 'branch',
            'organization_id' => $branch->id,
        ]));

        $response->assertOk();
        $response->assertSeeText('Branch Active');
        $response->assertDontSeeText('Franchise Suspended');
        $response->assertViewHas('staff', function ($staff): bool {
            return $staff->total() === 1;
        });
    }
}
