<?php

namespace App\Modules\Settings\Services;

class SecurityAlertRoutingService
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    /**
     * @return array<int, string>
     */
    public function recipients(): array
    {
        $primary = $this->normalizeEmail((string) $this->settings->get('security', 'security_alert_email', ''));
        $extra = collect(preg_split('/[\s,]+/', (string) $this->settings->get('security', 'security_alert_recipients', ''), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn (string $email): ?string => $this->normalizeEmail($email))
            ->filter()
            ->values()
            ->all();

        return collect([$primary, ...$extra])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function alertsEnabled(): bool
    {
        return $this->recipients() !== [];
    }

    private function normalizeEmail(string $email): ?string
    {
        $normalized = strtolower(trim($email));

        return filter_var($normalized, FILTER_VALIDATE_EMAIL) ? $normalized : null;
    }
}
