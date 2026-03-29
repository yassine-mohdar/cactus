<?php

namespace Database\Seeders;

use App\Modules\Community\Services\DefaultCommunityGroupService;
use Illuminate\Database\Seeder;

class CommunitySeeder extends Seeder
{
    public function run(): void
    {
        app(DefaultCommunityGroupService::class)->getOrCreate();
    }
}
