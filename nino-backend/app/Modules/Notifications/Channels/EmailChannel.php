<?php

namespace App\Modules\Notifications\Channels;

use App\Modules\Notifications\Contracts\NotificationChannelDriver;
use App\Modules\Notifications\Models\IntegrationSetting;
use App\Modules\Notifications\Models\NotificationLog;
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
        // Email works with default Laravel SMTP config
        return !empty(config('mail.mailers.smtp.host'));
    }
}
