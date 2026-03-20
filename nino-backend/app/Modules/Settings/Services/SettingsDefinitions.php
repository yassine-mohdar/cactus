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
            'security' => self::security(),
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
            'security' => 'Security',
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
        ];
    }
}
