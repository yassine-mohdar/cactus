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
            ['key' => 'site_name',        'label' => 'Site Name',        'type' => 'string',  'rules' => 'nullable|string|max:100',  'encrypt' => false, 'placeholder' => 'NinoWorld'],
            ['key' => 'site_tagline',     'label' => 'Tagline',          'type' => 'string',  'rules' => 'nullable|string|max:255',  'encrypt' => false, 'placeholder' => 'Your kawaii companion store'],
            ['key' => 'company_name',     'label' => 'Company Name',     'type' => 'string',  'rules' => 'nullable|string|max:150',  'encrypt' => false, 'placeholder' => 'NinoWorld LLC'],
            ['key' => 'company_email',    'label' => 'Contact Email',    'type' => 'string',  'rules' => 'nullable|email|max:150',   'encrypt' => false, 'placeholder' => 'contact@ninoworld.com'],
            ['key' => 'company_phone',    'label' => 'Contact Phone',    'type' => 'string',  'rules' => 'nullable|string|max:30',   'encrypt' => false, 'placeholder' => '+212 600 000000'],
            ['key' => 'company_address',  'label' => 'Address',          'type' => 'string',  'rules' => 'nullable|string|max:500',  'encrypt' => false, 'placeholder' => '123 Main St, Casablanca'],
            ['key' => 'timezone',         'label' => 'Timezone',         'type' => 'string',  'rules' => 'nullable|string|max:50',   'encrypt' => false, 'placeholder' => 'Africa/Casablanca'],
            ['key' => 'currency',         'label' => 'Base Currency',    'type' => 'string',  'rules' => 'nullable|string|max:5',    'encrypt' => false, 'placeholder' => 'MAD'],
        ];
    }

    private static function seo(): array
    {
        return [
            ['key' => 'meta_title',        'label' => 'Default Meta Title',       'type' => 'string', 'rules' => 'nullable|string|max:70',   'encrypt' => false, 'placeholder' => 'NinoWorld — Kawaii Store'],
            ['key' => 'meta_description',  'label' => 'Default Meta Description', 'type' => 'string', 'rules' => 'nullable|string|max:160',  'encrypt' => false, 'placeholder' => 'Shop the cutest plushies...'],
            ['key' => 'google_analytics',  'label' => 'Google Analytics ID',      'type' => 'string', 'rules' => 'nullable|string|max:30',   'encrypt' => false, 'placeholder' => 'G-XXXXXXXXXX'],
            ['key' => 'google_verification','label' => 'Google Verification',     'type' => 'string', 'rules' => 'nullable|string|max:100',  'encrypt' => false, 'placeholder' => ''],
            ['key' => 'facebook_pixel',    'label' => 'Facebook Pixel ID',        'type' => 'string', 'rules' => 'nullable|string|max:30',   'encrypt' => false, 'placeholder' => ''],
            ['key' => 'tiktok_pixel',      'label' => 'TikTok Pixel ID',          'type' => 'string', 'rules' => 'nullable|string|max:30',   'encrypt' => false, 'placeholder' => ''],
        ];
    }

    private static function mail(): array
    {
        return [];
    }

    private static function security(): array
    {
        return [
            ['key' => 'force_2fa',           'label' => 'Force Staff 2FA',         'type' => 'boolean', 'rules' => 'nullable|boolean', 'encrypt' => false, 'placeholder' => ''],
            ['key' => 'session_lifetime',    'label' => 'Session Lifetime (min)',   'type' => 'integer', 'rules' => 'nullable|integer|min:5|max:1440', 'encrypt' => false, 'placeholder' => '120'],
            ['key' => 'password_min_length', 'label' => 'Min Password Length',      'type' => 'integer', 'rules' => 'nullable|integer|min:6|max:30',  'encrypt' => false, 'placeholder' => '8'],
        ];
    }
}
