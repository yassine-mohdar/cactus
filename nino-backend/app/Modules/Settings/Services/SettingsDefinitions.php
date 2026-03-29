<?php

namespace App\Modules\Settings\Services;

/**
 * Defines validation rules and field metadata for each settings group.
 * Central source of truth for what fields exist, their types, and constraints.
 */
class SettingsDefinitions
{
    /**
     * Return field definitions for a settings group.
     *
     * Each field: [key, label, type, rules, encrypt?, placeholder?]
     */
    public static function forGroup(string $group): array
    {
        return match ($group) {
            'general'  => self::general(),
            'seo'      => self::seo(),
            'mail'     => self::mail(),
            'notifications' => self::notifications(),
            'shipping' => self::shipping(),
            'system' => self::system(),
            'security' => self::security(),
            'finance'  => self::finance(),
            default    => [],
        };
    }

    /**
     * All group labels for the tab navigation.
     */
    public static function groups(): array
    {
        return [
            'general'  => 'General',
            'seo'      => 'SEO & Analytics',
            'mail'     => 'Mail / SMTP',
            'notifications' => 'Notifications',
            'shipping' => 'Shipping & Fulfillment',
            'system' => 'System & Maintenance',
            'security' => 'Security',
            'finance'  => 'Finance & Currency',
        ];
    }

    /**
     * Validation rules for a group (keyed by field key).
     */
    public static function rules(string $group): array
    {
        $defs = self::forGroup($group);
        $rules = [];
        foreach ($defs as $field) {
            $rules[$field['key']] = $field['rules'];
        }
        return $rules;
    }

    private static function general(): array
    {
        return [
            ['key' => 'site_name',                 'label' => 'Site Name',                 'type' => 'string',   'rules' => 'nullable|string|max:100',  'encrypt' => false, 'placeholder' => 'NinoWorld', 'section' => 'Website Information'],
            ['key' => 'site_tagline',              'label' => 'Tagline',                   'type' => 'string',   'rules' => 'nullable|string|max:255',  'encrypt' => false, 'placeholder' => 'Your kawaii companion store', 'section' => 'Website Information'],
            ['key' => 'site_url',                  'label' => 'Canonical Site URL',        'type' => 'url',      'rules' => 'nullable|url|max:255',     'encrypt' => false, 'placeholder' => 'https://ninoworld.ma', 'section' => 'Website Information'],

            ['key' => 'company_name',              'label' => 'Operating Company Name',    'type' => 'string',   'rules' => 'nullable|string|max:150',  'encrypt' => false, 'placeholder' => 'NinoWorld SARL', 'section' => 'Company Information'],
            ['key' => 'company_legal_name',        'label' => 'Legal Entity Name',         'type' => 'string',   'rules' => 'nullable|string|max:180',  'encrypt' => false, 'placeholder' => 'NinoWorld SARL AU', 'section' => 'Company Information'],
            ['key' => 'company_registration_id',   'label' => 'Registration / RC Number',  'type' => 'string',   'rules' => 'nullable|string|max:80',   'encrypt' => false, 'placeholder' => 'RC Casablanca 123456', 'section' => 'Company Information'],

            ['key' => 'company_email',             'label' => 'Primary Contact Email',     'type' => 'email',    'rules' => 'nullable|email|max:150',   'encrypt' => false, 'placeholder' => 'contact@ninoworld.com', 'section' => 'Contact Information'],
            ['key' => 'support_email',             'label' => 'Support Email',             'type' => 'email',    'rules' => 'nullable|email|max:150',   'encrypt' => false, 'placeholder' => 'support@ninoworld.com', 'section' => 'Contact Information'],
            ['key' => 'company_phone',             'label' => 'Primary Contact Phone',     'type' => 'string',   'rules' => 'nullable|string|max:30',   'encrypt' => false, 'placeholder' => '+212 600 000000', 'section' => 'Contact Information'],
            ['key' => 'company_address',           'label' => 'Business Address',          'type' => 'textarea', 'rules' => 'nullable|string|max:500',  'encrypt' => false, 'placeholder' => '201 Boulevard Ghandi, Casablanca, Morocco', 'section' => 'Contact Information'],

            ['key' => 'brand_logo_url',            'label' => 'Logo URL',                  'type' => 'url',      'rules' => 'nullable|url|max:255',     'encrypt' => false, 'placeholder' => 'https://cdn.ninoworld.ma/brand/logo.svg', 'section' => 'Branding Basics'],
            ['key' => 'brand_favicon_url',         'label' => 'Favicon URL',               'type' => 'url',      'rules' => 'nullable|url|max:255',     'encrypt' => false, 'placeholder' => 'https://cdn.ninoworld.ma/brand/favicon.ico', 'section' => 'Branding Basics'],
            ['key' => 'brand_primary_color',       'label' => 'Primary Brand Color',       'type' => 'color',    'rules' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'], 'encrypt' => false, 'placeholder' => '#17302A', 'section' => 'Branding Basics'],
            ['key' => 'brand_accent_color',        'label' => 'Accent Brand Color',        'type' => 'color',    'rules' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'], 'encrypt' => false, 'placeholder' => '#C18B3B', 'section' => 'Branding Basics'],

            ['key' => 'locale',                    'label' => 'Application Locale',        'type' => 'string',   'rules' => 'nullable|string|max:10',   'encrypt' => false, 'placeholder' => 'en', 'section' => 'Regional Defaults'],
            ['key' => 'timezone',                  'label' => 'Timezone',                  'type' => 'string',   'rules' => 'nullable|string|max:50',   'encrypt' => false, 'placeholder' => 'Africa/Casablanca', 'section' => 'Regional Defaults'],
            ['key' => 'currency',                  'label' => 'Base Currency',             'type' => 'string',   'rules' => 'nullable|string|max:5',    'encrypt' => false, 'placeholder' => 'MAD', 'section' => 'Regional Defaults'],
        ];
    }

    private static function seo(): array
    {
        return [
            ['key' => 'meta_title',         'label' => 'Default Meta Title',        'type' => 'string', 'rules' => 'nullable|string|max:70',   'encrypt' => false, 'placeholder' => 'NinoWorld — Kawaii Store', 'section' => 'SEO Defaults'],
            ['key' => 'meta_description',   'label' => 'Default Meta Description',  'type' => 'string', 'rules' => 'nullable|string|max:160',  'encrypt' => false, 'placeholder' => 'Shop the cutest plushies...', 'section' => 'SEO Defaults'],
            ['key' => 'google_analytics',   'label' => 'Google Analytics ID',       'type' => 'string', 'rules' => 'nullable|string|max:30',   'encrypt' => false, 'placeholder' => 'G-XXXXXXXXXX', 'section' => 'Analytics Integrations'],
            ['key' => 'google_tag_manager', 'label' => 'Google Tag Manager ID',     'type' => 'string', 'rules' => 'nullable|string|max:30',   'encrypt' => false, 'placeholder' => 'GTM-XXXXXXX', 'section' => 'Analytics Integrations'],
            ['key' => 'google_verification','label' => 'Google Verification Token', 'type' => 'string', 'rules' => 'nullable|string|max:100',  'encrypt' => false, 'placeholder' => 'google-site-verification=...', 'section' => 'Search Verification'],
            ['key' => 'facebook_pixel',     'label' => 'Facebook Pixel ID',         'type' => 'string', 'rules' => 'nullable|string|max:30',   'encrypt' => false, 'placeholder' => '123456789012345', 'section' => 'Advertising Pixels'],
            ['key' => 'tiktok_pixel',       'label' => 'TikTok Pixel ID',           'type' => 'string', 'rules' => 'nullable|string|max:30',   'encrypt' => false, 'placeholder' => '', 'section' => 'Advertising Pixels'],
        ];
    }

    private static function mail(): array
    {
        return [];
    }

    private static function security(): array
    {
        return [
            ['key' => 'force_2fa',                   'label' => 'Force Staff/Admin Two-Factor Authentication', 'type' => 'boolean', 'rules' => 'nullable|boolean', 'encrypt' => false, 'placeholder' => '', 'section' => 'Access Security'],
            ['key' => 'session_lifetime',            'label' => 'Session Lifetime (min)',                       'type' => 'integer', 'rules' => 'nullable|integer|min:5|max:1440', 'encrypt' => false, 'placeholder' => '120', 'section' => 'Access Security'],
            ['key' => 'password_min_length',         'label' => 'Minimum Password Length',                      'type' => 'integer', 'rules' => 'nullable|integer|min:6|max:30', 'encrypt' => false, 'placeholder' => '8', 'section' => 'Password Policy'],
            ['key' => 'password_require_mixed_case', 'label' => 'Require Uppercase and Lowercase Letters',     'type' => 'boolean', 'rules' => 'nullable|boolean', 'encrypt' => false, 'placeholder' => '', 'section' => 'Password Policy'],
            ['key' => 'password_require_numbers',    'label' => 'Require Numbers',                              'type' => 'boolean', 'rules' => 'nullable|boolean', 'encrypt' => false, 'placeholder' => '', 'section' => 'Password Policy'],
            ['key' => 'password_require_symbols',    'label' => 'Require Symbols',                              'type' => 'boolean', 'rules' => 'nullable|boolean', 'encrypt' => false, 'placeholder' => '', 'section' => 'Password Policy'],
            ['key' => 'security_alert_email',        'label' => 'Primary Security Alert Email',                 'type' => 'email',   'rules' => 'nullable|email|max:150', 'encrypt' => false, 'placeholder' => 'security@ninoworld.ma', 'section' => 'Security Alerts'],
            ['key' => 'security_alert_recipients',   'label' => 'Additional Alert Recipients (CSV)',           'type' => 'textarea','rules' => 'nullable|string|max:500', 'encrypt' => false, 'placeholder' => 'ops@ninoworld.ma, cto@ninoworld.ma', 'section' => 'Security Alerts'],
        ];
    }

    private static function notifications(): array
    {
        return [
            ['key' => 'email_enabled',  'label' => 'Enable Email Notifications',    'type' => 'boolean',  'rules' => 'nullable|boolean',           'encrypt' => false, 'placeholder' => '', 'section' => 'Channel Controls'],
            ['key' => 'sms_enabled',    'label' => 'Enable SMS Notifications',      'type' => 'boolean',  'rules' => 'nullable|boolean',           'encrypt' => false, 'placeholder' => '', 'section' => 'Channel Controls'],
            ['key' => 'whatsapp_enabled','label' => 'Enable WhatsApp Notifications','type' => 'boolean',  'rules' => 'nullable|boolean',           'encrypt' => false, 'placeholder' => '', 'section' => 'Channel Controls'],
            ['key' => 'sender_name',    'label' => 'Notification Sender Name',      'type' => 'string',   'rules' => 'nullable|string|max:120',    'encrypt' => false, 'placeholder' => 'NinoWorld', 'section' => 'Sender Defaults'],
            ['key' => 'footer_text',    'label' => 'Template Footer Text',          'type' => 'textarea', 'rules' => 'nullable|string|max:1000',   'encrypt' => false, 'placeholder' => 'Thank you for shopping with NinoWorld.', 'section' => 'Template Globals'],
            ['key' => 'signature',      'label' => 'Template Signature',            'type' => 'string',   'rules' => 'nullable|string|max:255',    'encrypt' => false, 'placeholder' => 'The NinoWorld Team', 'section' => 'Template Globals'],
            ['key' => 'queue_name',     'label' => 'Notification Queue Name',       'type' => 'string',   'rules' => 'nullable|string|max:80',     'encrypt' => false, 'placeholder' => 'notifications', 'section' => 'Queue & Retry'],
            ['key' => 'max_attempts',   'label' => 'Maximum Delivery Attempts',     'type' => 'integer',  'rules' => 'nullable|integer|min:1|max:10', 'encrypt' => false, 'placeholder' => '3', 'section' => 'Queue & Retry'],
            ['key' => 'retry_base_delay_minutes', 'label' => 'Retry Base Delay (Minutes)', 'type' => 'integer', 'rules' => 'nullable|integer|min:1|max:60', 'encrypt' => false, 'placeholder' => '2', 'section' => 'Queue & Retry'],
        ];
    }

    private static function shipping(): array
    {
        return [
            ['key' => 'default_method_code',   'label' => 'Default Checkout Shipping Method', 'type' => 'string',   'rules' => 'nullable|string|max:100', 'encrypt' => false, 'placeholder' => 'standard', 'section' => 'Shipping Defaults'],
            ['key' => 'default_shipping_cost', 'label' => 'Default Shipping Cost',            'type' => 'number',   'rules' => 'nullable|numeric|min:0|max:5000', 'encrypt' => false, 'placeholder' => '45', 'section' => 'Shipping Defaults'],
            ['key' => 'free_shipping_threshold','label' => 'Free Shipping Threshold',         'type' => 'number',   'rules' => 'nullable|numeric|min:0|max:50000', 'encrypt' => false, 'placeholder' => '500', 'section' => 'Shipping Defaults'],
            ['key' => 'default_carrier_name',  'label' => 'Default Carrier Name',             'type' => 'string',   'rules' => 'nullable|string|max:120', 'encrypt' => false, 'placeholder' => 'Amana', 'section' => 'Carrier & Method Foundation'],
            ['key' => 'default_estimated_days','label' => 'Default Estimated Delivery Window','type' => 'string',   'rules' => 'nullable|string|max:80',  'encrypt' => false, 'placeholder' => '2-4 business days', 'section' => 'Carrier & Method Foundation'],
            ['key' => 'carrier_directory',     'label' => 'Preferred Carrier Directory (CSV)','type' => 'textarea', 'rules' => 'nullable|string|max:500', 'encrypt' => false, 'placeholder' => 'Amana, DHL, Chronopost, FedEx, UPS', 'section' => 'Carrier & Method Foundation'],
            ['key' => 'tracking_required_on_dispatch', 'label' => 'Require Tracking Number Before Dispatch', 'type' => 'boolean', 'rules' => 'nullable|boolean', 'encrypt' => false, 'placeholder' => '', 'section' => 'Tracking Defaults'],
            ['key' => 'tracking_url_template', 'label' => 'Fallback Tracking URL Template',   'type' => 'string',   'rules' => 'nullable|string|max:500', 'encrypt' => false, 'placeholder' => 'https://tracking.example.com/{tracking_number}', 'section' => 'Tracking Defaults'],
        ];
    }

    private static function system(): array
    {
        return [
            ['key' => 'maintenance_mode_enabled', 'label' => 'Enable Maintenance Mode', 'type' => 'boolean', 'rules' => 'nullable|boolean', 'encrypt' => false, 'placeholder' => '', 'section' => 'Maintenance Mode'],
            ['key' => 'maintenance_message', 'label' => 'Maintenance Message', 'type' => 'textarea', 'rules' => 'nullable|string|max:1000', 'encrypt' => false, 'placeholder' => 'We are performing scheduled maintenance. Please check back shortly.', 'section' => 'Maintenance Mode'],
            ['key' => 'maintenance_bypass_staff', 'label' => 'Allow Logged-In Staff To Bypass Maintenance Mode', 'type' => 'boolean', 'rules' => 'nullable|boolean', 'encrypt' => false, 'placeholder' => '', 'section' => 'Maintenance Mode'],
            ['key' => 'default_media_disk', 'label' => 'Default Media Disk', 'type' => 'string', 'rules' => 'nullable|in:public,local,s3', 'encrypt' => false, 'placeholder' => 'public', 'section' => 'Media & Storage'],
            ['key' => 'avatar_media_directory', 'label' => 'Avatar Upload Directory', 'type' => 'string', 'rules' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9_\\/-]+$/'], 'encrypt' => false, 'placeholder' => 'avatars', 'section' => 'Media & Storage'],
            ['key' => 'catalog_media_directory', 'label' => 'Catalog Upload Directory', 'type' => 'string', 'rules' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9_\\/-]+$/'], 'encrypt' => false, 'placeholder' => 'categories', 'section' => 'Media & Storage'],
            ['key' => 'feature_promotions_enabled', 'label' => 'Enable Promotions Module', 'type' => 'boolean', 'rules' => 'nullable|boolean', 'encrypt' => false, 'placeholder' => '', 'section' => 'Feature Flags'],
            ['key' => 'feature_cms_enabled', 'label' => 'Enable CMS Module', 'type' => 'boolean', 'rules' => 'nullable|boolean', 'encrypt' => false, 'placeholder' => '', 'section' => 'Feature Flags'],
            ['key' => 'feature_community_enabled', 'label' => 'Enable Community Module Foundation', 'type' => 'boolean', 'rules' => 'nullable|boolean', 'encrypt' => false, 'placeholder' => '', 'section' => 'Feature Flags'],
        ];
    }

    private static function finance(): array
    {
        return [
            ['key' => 'base_currency',          'label' => 'Base Currency',                         'type' => 'string',   'rules' => 'nullable|string|size:3|alpha', 'encrypt' => false, 'placeholder' => 'MAD', 'section' => 'Currency Defaults'],
            ['key' => 'multi_currency_enabled', 'label' => 'Enable Multi-Currency Foundation',     'type' => 'boolean',  'rules' => 'nullable|boolean', 'encrypt' => false, 'placeholder' => '', 'section' => 'Currency Strategy'],
            ['key' => 'supported_currencies',   'label' => 'Supported Currencies (CSV)',           'type' => 'textarea', 'rules' => 'nullable|string|max:120', 'encrypt' => false, 'placeholder' => 'MAD, EUR, USD', 'section' => 'Currency Strategy'],
            ['key' => 'conversion_adjustment_percent', 'label' => 'Conversion Adjustment Percent', 'type' => 'number',   'rules' => 'nullable|numeric|min:-25|max:25', 'encrypt' => false, 'placeholder' => '0', 'section' => 'Conversion Rules'],
            ['key' => 'price_rounding_strategy',       'label' => 'Price Rounding Strategy',        'type' => 'string',   'rules' => 'nullable|in:none,nearest_0_05,nearest_0_10,nearest_whole', 'encrypt' => false, 'placeholder' => 'none', 'section' => 'Conversion Rules'],
            ['key' => 'default_tax_rate',              'label' => 'Default Tax Rate (%)',           'type' => 'number',   'rules' => 'nullable|numeric|min:0|max:100', 'encrypt' => false, 'placeholder' => '20', 'section' => 'Financial Defaults'],
            ['key' => 'default_payment_terms_days',    'label' => 'Default Payment Terms (Days)',   'type' => 'integer',  'rules' => 'nullable|integer|min:0|max:365', 'encrypt' => false, 'placeholder' => '0', 'section' => 'Financial Defaults'],
        ];
    }
}
