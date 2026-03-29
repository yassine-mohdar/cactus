<?php

namespace App\Modules\Notifications\Services;

use App\Modules\Notifications\Enums\NotificationChannel;
use App\Modules\Settings\Services\SettingsService;

class NotificationSettingsService
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function isChannelEnabled(NotificationChannel $channel): bool
    {
        return (bool) $this->settings->get(
            'notifications',
            $this->channelSettingKey($channel),
            true,
        );
    }

    public function senderName(): string
    {
        return trim((string) $this->settings->get(
            'notifications',
            'sender_name',
            config('app.name', 'NinoWorld'),
        )) ?: (string) config('app.name', 'NinoWorld');
    }

    public function templateFooterText(): ?string
    {
        return $this->normalizedText('footer_text');
    }

    public function templateSignature(): ?string
    {
        return $this->normalizedText('signature');
    }

    public function queueName(): string
    {
        return trim((string) $this->settings->get(
            'notifications',
            'queue_name',
            config('performance.queues.notifications', 'notifications'),
        )) ?: (string) config('performance.queues.notifications', 'notifications');
    }

    public function maxAttempts(): int
    {
        return max(1, (int) $this->settings->get('notifications', 'max_attempts', 3));
    }

    public function retryBaseDelayMinutes(): int
    {
        return max(1, (int) $this->settings->get('notifications', 'retry_base_delay_minutes', 2));
    }

    public function retryDelayMinutesForAttempt(int $attempts): int
    {
        return (int) ($this->retryBaseDelayMinutes() * pow(2, max(0, $attempts - 1)));
    }

    /**
     * @return array<string, string|null>
     */
    public function templateVariables(): array
    {
        return [
            'notification_sender_name' => $this->senderName(),
            'notification_footer_text' => $this->templateFooterText(),
            'notification_signature' => $this->templateSignature(),
        ];
    }

    public function appendTemplateGlobals(string $body): string
    {
        $segments = array_values(array_filter([
            trim($body),
            $this->templateFooterText(),
            $this->templateSignature(),
        ], fn (?string $value): bool => filled($value)));

        return implode("\n\n", $segments);
    }

    private function channelSettingKey(NotificationChannel $channel): string
    {
        return match ($channel) {
            NotificationChannel::EMAIL => 'email_enabled',
            NotificationChannel::SMS => 'sms_enabled',
            NotificationChannel::WHATSAPP => 'whatsapp_enabled',
        };
    }

    private function normalizedText(string $key): ?string
    {
        $value = trim((string) $this->settings->get('notifications', $key, ''));

        return $value !== '' ? $value : null;
    }
}
