<?php

namespace Database\Seeders;

use App\Modules\Organizations\Services\OrganizationHierarchyService;
use Illuminate\Database\Seeder;

class OrganizationFoundationSeeder extends Seeder
{
    public function run(): void
    {
        app(OrganizationHierarchyService::class)->defaultPlatform();
    }
}
