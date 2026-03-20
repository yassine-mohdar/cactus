<?php

namespace Database\Seeders;

use App\Modules\Notifications\Models\IntegrationSetting;
use Illuminate\Database\Seeder;

class IntegrationSettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (IntegrationSetting::defaultDefinitions() as $definition) {
            IntegrationSetting::updateOrCreate(
                ['provider' => $definition['provider']],
                $definition
            );
        }
    }
}
