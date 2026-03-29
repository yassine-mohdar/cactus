<?php

namespace Tests\Feature;

use App\Modules\IAM\Support\PermissionNaming;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Services\OrganizationHierarchyService;
use Database\Seeders\OrganizationFoundationSeeder;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class OrganizationFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_permissions_follow_the_canonical_naming_convention(): void
    {
        $this->seed(PermissionsSeeder::class);

        $permissions = \App\Modules\IAM\Models\Permission::query()->pluck('name')->all();

        $this->assertNotEmpty($permissions);

        foreach ($permissions as $permission) {
            $this->assertTrue(
                PermissionNaming::isValid($permission),
                "Permission [{$permission}] does not follow the canonical naming convention.",
            );
        }
    }

    public function test_organization_hierarchy_service_creates_platform_franchise_and_branch_entities(): void
    {
        $service = app(OrganizationHierarchyService::class);

        $platform = $service->createPlatform([
            'name' => 'NinoWorld Platform',
            'code' => 'PLT-001',
        ]);
        $franchise = $service->createFranchise($platform, [
            'name' => 'Casablanca Franchise',
            'code' => 'FR-001',
        ]);
        $branch = $service->createBranch($franchise, [
            'name' => 'Maarif Branch',
            'code' => 'BR-001',
        ]);

        $this->assertTrue($platform->isPlatform());
        $this->assertNull($platform->parent_id);
        $this->assertTrue($franchise->isFranchise());
        $this->assertSame($platform->id, $franchise->parent_id);
        $this->assertTrue($branch->isBranch());
        $this->assertSame($franchise->id, $branch->parent_id);
    }

    public function test_franchise_must_belong_to_platform(): void
    {
        $service = app(OrganizationHierarchyService::class);
        $branch = Organization::factory()->branch()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Franchise organizations must belong to a platform.');

        $service->createFranchise($branch, [
            'name' => 'Invalid Franchise',
        ]);
    }

    public function test_branch_must_belong_to_franchise(): void
    {
        $service = app(OrganizationHierarchyService::class);
        $platform = Organization::factory()->platform()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Branch organizations must belong to a franchise.');

        $service->createBranch($platform, [
            'name' => 'Invalid Branch',
        ]);
    }

    public function test_organization_foundation_seeder_creates_default_platform_once(): void
    {
        $this->seed(OrganizationFoundationSeeder::class);
        $this->seed(OrganizationFoundationSeeder::class);

        $this->assertDatabaseCount('organizations', 1);
        $this->assertDatabaseHas('organizations', [
            'type' => Organization::TYPE_PLATFORM,
            'name' => 'NinoWorld Platform',
            'code' => 'PLT-001',
        ]);
    }
}
