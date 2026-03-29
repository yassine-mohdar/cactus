<?php

namespace App\Modules\Settings\Services;

class FeatureFlagService
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function isEnabled(string $feature): bool
    {
        return match ($feature) {
            'promotions' => (bool) $this->settings->get('system', 'feature_promotions_enabled', config('features.promotions', true)),
            'cms' => (bool) $this->settings->get('system', 'feature_cms_enabled', config('features.cms', true)),
            'community' => (bool) $this->settings->get('system', 'feature_community_enabled', config('features.community', true)),
            default => true,
        };
    }
}
