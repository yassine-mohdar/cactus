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

            // Notifications Group
            ['group' => 'notifications', 'key' => 'email_enabled', 'value' => true, 'type' => 'boolean'],
            ['group' => 'notifications', 'key' => 'sms_enabled', 'value' => true, 'type' => 'boolean'],
            ['group' => 'notifications', 'key' => 'whatsapp_enabled', 'value' => true, 'type' => 'boolean'],
            ['group' => 'notifications', 'key' => 'sender_name', 'value' => 'NinoWorld', 'type' => 'string'],
            ['group' => 'notifications', 'key' => 'footer_text', 'value' => '', 'type' => 'string'],
            ['group' => 'notifications', 'key' => 'signature', 'value' => '', 'type' => 'string'],
            ['group' => 'notifications', 'key' => 'queue_name', 'value' => 'notifications', 'type' => 'string'],
            ['group' => 'notifications', 'key' => 'max_attempts', 'value' => 3, 'type' => 'integer'],
            ['group' => 'notifications', 'key' => 'retry_base_delay_minutes', 'value' => 2, 'type' => 'integer'],

            // Shipping Group
            ['group' => 'shipping', 'key' => 'default_method_code', 'value' => 'standard', 'type' => 'string'],
            ['group' => 'shipping', 'key' => 'default_shipping_cost', 'value' => 45, 'type' => 'number'],
            ['group' => 'shipping', 'key' => 'free_shipping_threshold', 'value' => 500, 'type' => 'number'],
            ['group' => 'shipping', 'key' => 'default_carrier_name', 'value' => 'Amana', 'type' => 'string'],
            ['group' => 'shipping', 'key' => 'default_estimated_days', 'value' => '2-4 business days', 'type' => 'string'],
            ['group' => 'shipping', 'key' => 'carrier_directory', 'value' => 'Amana, DHL, Chronopost, FedEx, UPS', 'type' => 'string'],
            ['group' => 'shipping', 'key' => 'tracking_required_on_dispatch', 'value' => false, 'type' => 'boolean'],
            ['group' => 'shipping', 'key' => 'tracking_url_template', 'value' => '', 'type' => 'string'],

            // System Group
            ['group' => 'system', 'key' => 'maintenance_mode_enabled', 'value' => false, 'type' => 'boolean'],
            ['group' => 'system', 'key' => 'maintenance_message', 'value' => 'We are performing scheduled maintenance. Please check back shortly.', 'type' => 'string'],
            ['group' => 'system', 'key' => 'maintenance_bypass_staff', 'value' => true, 'type' => 'boolean'],
            ['group' => 'system', 'key' => 'default_media_disk', 'value' => 'public', 'type' => 'string'],
            ['group' => 'system', 'key' => 'avatar_media_directory', 'value' => 'avatars', 'type' => 'string'],
            ['group' => 'system', 'key' => 'catalog_media_directory', 'value' => 'categories', 'type' => 'string'],
            ['group' => 'system', 'key' => 'feature_promotions_enabled', 'value' => true, 'type' => 'boolean'],
            ['group' => 'system', 'key' => 'feature_cms_enabled', 'value' => true, 'type' => 'boolean'],
            ['group' => 'system', 'key' => 'feature_community_enabled', 'value' => true, 'type' => 'boolean'],

            // Security Group
            ['group' => 'security', 'key' => 'force_2fa', 'value' => false, 'type' => 'boolean'],
            ['group' => 'security', 'key' => 'session_lifetime', 'value' => 120, 'type' => 'integer'],
            ['group' => 'security', 'key' => 'password_min_length', 'value' => 8, 'type' => 'integer'],
            ['group' => 'security', 'key' => 'password_require_mixed_case', 'value' => true, 'type' => 'boolean'],
            ['group' => 'security', 'key' => 'password_require_numbers', 'value' => true, 'type' => 'boolean'],
            ['group' => 'security', 'key' => 'password_require_symbols', 'value' => false, 'type' => 'boolean'],
            ['group' => 'security', 'key' => 'security_alert_email', 'value' => 'security@ninoworld.ma', 'type' => 'string'],
            ['group' => 'security', 'key' => 'security_alert_recipients', 'value' => 'ops@ninoworld.ma', 'type' => 'string'],

            // Finance Group
            ['group' => 'finance', 'key' => 'base_currency', 'value' => 'MAD', 'type' => 'string'],
            ['group' => 'finance', 'key' => 'multi_currency_enabled', 'value' => false, 'type' => 'boolean'],
            ['group' => 'finance', 'key' => 'supported_currencies', 'value' => 'MAD, EUR', 'type' => 'string'],
            ['group' => 'finance', 'key' => 'conversion_adjustment_percent', 'value' => 0, 'type' => 'number'],
            ['group' => 'finance', 'key' => 'price_rounding_strategy', 'value' => 'none', 'type' => 'string'],
            ['group' => 'finance', 'key' => 'default_tax_rate', 'value' => 20, 'type' => 'number'],
            ['group' => 'finance', 'key' => 'default_payment_terms_days', 'value' => 0, 'type' => 'integer'],
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
