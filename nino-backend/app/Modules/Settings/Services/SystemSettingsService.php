<?php

namespace App\Modules\Settings\Services;

class SystemSettingsService
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function maintenanceModeEnabled(): bool
    {
        return (bool) $this->settings->get('system', 'maintenance_mode_enabled', false);
    }

    public function maintenanceMessage(): string
    {
        return trim((string) $this->settings->get(
            'system',
            'maintenance_message',
            'We are performing scheduled maintenance. Please check back shortly.',
        )) ?: 'We are performing scheduled maintenance. Please check back shortly.';
    }

    public function maintenanceBypassStaff(): bool
    {
        return (bool) $this->settings->get('system', 'maintenance_bypass_staff', true);
    }
}
