<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Modules\Settings\Models\Setting;
use Illuminate\Support\Facades\Crypt;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // General Group
            ['group' => 'general', 'key' => 'site_name', 'value' => 'NinoWorld', 'type' => 'string'],
            ['group' => 'general', 'key' => 'site_tagline', 'value' => 'Playful commerce operations for Morocco.', 'type' => 'string'],
            ['group' => 'general', 'key' => 'site_url', 'value' => 'https://ninoworld.ma', 'type' => 'url'],
            ['group' => 'general', 'key' => 'company_name', 'value' => 'NinoWorld SARL', 'type' => 'string'],
            ['group' => 'general', 'key' => 'company_legal_name', 'value' => 'NinoWorld SARL AU', 'type' => 'string'],
            ['group' => 'general', 'key' => 'company_registration_id', 'value' => 'RC Casablanca 123456', 'type' => 'string'],
            ['group' => 'general', 'key' => 'company_email', 'value' => 'hello@ninoworld.ma', 'type' => 'string'],
            ['group' => 'general', 'key' => 'support_email', 'value' => 'support@ninoworld.ma', 'type' => 'string'],
            ['group' => 'general', 'key' => 'company_phone', 'value' => '+212 5 22 00 00 00', 'type' => 'string'],
            ['group' => 'general', 'key' => 'company_address', 'value' => '201 Boulevard Ghandi, Casablanca, Morocco', 'type' => 'string'],
            ['group' => 'general', 'key' => 'brand_logo_url', 'value' => 'https://cdn.ninoworld.ma/brand/logo.svg', 'type' => 'url'],
            ['group' => 'general', 'key' => 'brand_favicon_url', 'value' => 'https://cdn.ninoworld.ma/brand/favicon.ico', 'type' => 'url'],
            ['group' => 'general', 'key' => 'brand_primary_color', 'value' => '#17302A', 'type' => 'color'],
            ['group' => 'general', 'key' => 'brand_accent_color', 'value' => '#C18B3B', 'type' => 'color'],
            ['group' => 'general', 'key' => 'locale', 'value' => 'en', 'type' => 'string'],
            ['group' => 'general', 'key' => 'timezone', 'value' => 'Africa/Casablanca', 'type' => 'string'],
            ['group' => 'general', 'key' => 'currency', 'value' => 'MAD', 'type' => 'string'],

            // SEO Group
            ['group' => 'seo', 'key' => 'meta_title', 'value' => 'NinoWorld - Adopt Your Magical Best Friend', 'type' => 'string'],
            ['group' => 'seo', 'key' => 'meta_description', 'value' => 'Discover the magic of NinoWorld. Adopt cute companions, explore the universe, and bring joy to your everyday life.', 'type' => 'string'],
            ['group' => 'seo', 'key' => 'google_analytics', 'value' => '', 'type' => 'string'],
            ['group' => 'seo', 'key' => 'google_tag_manager', 'value' => '', 'type' => 'string'],
            ['group' => 'seo', 'key' => 'google_verification', 'value' => '', 'type' => 'string'],
            ['group' => 'seo', 'key' => 'facebook_pixel', 'value' => '', 'type' => 'string'],
            ['group' => 'seo', 'key' => 'tiktok_pixel', 'value' => '', 'type' => 'string'],

            // Mail Group
            ['group' => 'mail', 'key' => 'mail_mailer', 'value' => 'smtp', 'type' => 'string'],
            ['group' => 'mail', 'key' => 'mail_host', 'value' => 'smtp.mailtrap.io', 'type' => 'string'],
            ['group' => 'mail', 'key' => 'mail_port', 'value' => '2525', 'type' => 'string'],
            ['group' => 'mail', 'key' => 'mail_username', 'value' => '', 'type' => 'string'],
            ['group' => 'mail', 'key' => 'mail_password', 'value' => null, 'type' => 'string', 'is_encrypted' => true],
            ['group' => 'mail', 'key' => 'mail_encryption', 'value' => 'tls', 'type' => 'string'],
            ['group' => 'mail', 'key' => 'mail_from_address', 'value' => 'notifications@ninoworld.ma', 'type' => 'string'],
            ['group' => 'mail', 'key' => 'mail_from_name', 'value' => 'NinoWorld', 'type' => 'string'],

            // Security Group
            ['group' => 'security', 'key' => 'force_2fa', 'value' => false, 'type' => 'boolean'],
            ['group' => 'security', 'key' => 'session_lifetime', 'value' => 120, 'type' => 'integer'],
            ['group' => 'security', 'key' => 'password_min_length', 'value' => 8, 'type' => 'integer'],
            ['group' => 'security', 'key' => 'password_require_mixed_case', 'value' => true, 'type' => 'boolean'],
            ['group' => 'security', 'key' => 'password_require_numbers', 'value' => true, 'type' => 'boolean'],
            ['group' => 'security', 'key' => 'password_require_symbols', 'value' => false, 'type' => 'boolean'],
        ];

        foreach ($settings as $settingData) {
            $isEncrypted = $settingData['is_encrypted'] ?? false;
            $value = $settingData['value'];

            if ($isEncrypted && !empty($value)) {
                $value = Crypt::encryptString($value);
            }

            Setting::updateOrCreate(
                [
                    'group' => $settingData['group'],
                    'key' => $settingData['key']
                ],
                [
                    'value' => $value,
                    'type' => $settingData['type'],
                    'is_encrypted' => $isEncrypted,
                ]
            );
        }
    }
}
