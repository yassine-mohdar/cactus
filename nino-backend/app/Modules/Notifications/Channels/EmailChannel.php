<?php

namespace App\Modules\Notifications\Channels;

use App\Modules\Notifications\Contracts\NotificationChannelDriver;
use App\Modules\Notifications\Models\IntegrationSetting;
use App\Modules\Notifications\Models\NotificationLog;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Email Channel Driver
 * Uses Laravel's built-in Mail facade backed by the SMTP config.
 */
class EmailChannel implements NotificationChannelDriver
{
    public function send(NotificationLog $log): ?string
    {
        $settings = IntegrationSetting::forProvider('smtp');

        $this->applySmtpRuntimeConfig($settings);

        // Determine sender
        $fromAddress = $settings?->getCredential('from_address', config('mail.from.address'));
        $fromName = $settings?->getCredential('from_name', config('mail.from.name'));

        Mail::html($log->body, function ($message) use ($log, $fromAddress, $fromName) {
            $message->to($log->recipient)
                ->from($fromAddress, $fromName)
                ->subject($log->subject ?? 'NinoWorld Notification');
        });

        Log::channel('stack')->info('[Notifications] Email sent', [
            'recipient' => $log->recipient,
            'event' => $log->event->value,
        ]);

        // Laravel Mail does not return an external ID by default
        return 'email-' . $log->id . '-' . now()->timestamp;
    }

    public function isConfigured(): bool
    {
        $settings = IntegrationSetting::forProvider('smtp');

        if ($settings && $settings->is_enabled) {
            return $settings->isConfigured();
        }

        return !empty(config('mail.mailers.smtp.host'));
    }

    private function applySmtpRuntimeConfig(?IntegrationSetting $settings): void
    {
        if (! $settings || ! $settings->is_enabled || ! $settings->isConfigured()) {
            return;
        }

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.host', $settings->getCredential('host'));
        Config::set('mail.mailers.smtp.port', (int) $settings->getCredential('port', 587));
        Config::set('mail.mailers.smtp.username', $settings->getCredential('username'));
        Config::set('mail.mailers.smtp.password', $settings->getCredential('password'));
        Config::set('mail.mailers.smtp.encryption', $settings->getCredential('encryption'));
        Config::set('mail.from.address', $settings->getCredential('from_address', config('mail.from.address')));
        Config::set('mail.from.name', $settings->getCredential('from_name', config('mail.from.name')));
    }
}
