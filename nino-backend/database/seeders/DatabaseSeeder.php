<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\IAM\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionsSeeder::class,
            OrganizationFoundationSeeder::class,
        ]);

        // Create Super Admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@ninoworld.com'],
            [
                'name' => 'NinoWorld Admin',
                'password' => Hash::make('password'),
            ]
        );
        $superAdmin->assignRole(Role::SUPER_ADMIN);

        $this->call([
            SettingsSeeder::class,
            IntegrationSettingsSeeder::class,
            GatewaySettingsSeeder::class,
            CommunitySeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call([
                LocalDemoDataSeeder::class,
            ]);
        }
    }
}
