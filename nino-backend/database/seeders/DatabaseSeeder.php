<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create role presets
        $roles = [
            'Super Admin',
            'Platform Admin',
            'Franchise Manager',
            'Branch Manager',
            'Customer Support Agent',
            'Shipping Agent',
            'Employee',
            'Finance Manager',
            'SEO / Content Manager',
            'Media Buying / Marketing Manager',
            'Sales Manager',
            'Stock Manager',
            'Community Moderator',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // Create Super Admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@ninoworld.com'],
            [
                'name' => 'NinoWorld Admin',
                'password' => Hash::make('password'),
            ]
        );
        $superAdmin->assignRole('Super Admin');

        $this->call([
            PermissionsSeeder::class,
            SettingsSeeder::class,
            IntegrationSettingsSeeder::class,
            GatewaySettingsSeeder::class,
        ]);
    }
}
