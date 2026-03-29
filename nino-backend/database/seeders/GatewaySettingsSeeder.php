<?php

namespace Database\Seeders;

use App\Modules\Payments\Models\GatewaySetting;
use Illuminate\Database\Seeder;

class GatewaySettingsSeeder extends Seeder
{
    public function run(): void
    {
        $gateways = [
            [
                'gateway_id' => 'offline_transfer',
                'name' => 'Offline Bank Transfer',
                'is_enabled' => false,
                'mode' => 'test',
                'credentials' => [],
                'metadata' => [
                    'instructions' => 'Share the transfer receipt with finance before the order moves into fulfillment.',
                ],
            ],
            [
                'gateway_id' => 'cmi',
                'name' => 'CMI (Centre Monétique Interbancaire)',
                'is_enabled' => false,
                'mode' => 'test',
                'credentials' => [],
                'metadata' => [
                    'currency_code' => '504',
                    'language' => 'fr',
                ],
            ],
            [
                'gateway_id' => 'payzone',
                'name' => 'Payzone Morocco',
                'is_enabled' => false,
                'mode' => 'test',
                'credentials' => [],
                'metadata' => [
                    'currency' => 'MAD',
                ],
            ],
            [
                'gateway_id' => 'stripe',
                'name' => 'Stripe',
                'is_enabled' => false,
                'mode' => 'test',
                'credentials' => [],
                'metadata' => [
                    'publishable_key' => '',
                ],
            ],
        ];

        foreach ($gateways as $gateway) {
            GatewaySetting::updateOrCreate(
                ['gateway_id' => $gateway['gateway_id']],
                $gateway
            );
        }
    }
}
