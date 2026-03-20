<?php

namespace App\Modules\Notifications\Channels;

use App\Modules\Notifications\Contracts\NotificationChannelDriver;
use App\Modules\Notifications\Models\IntegrationSetting;
use App\Modules\Notifications\Models\NotificationLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SMS Channel Driver — Twilio REST API
 */
class SmsChannel implements NotificationChannelDriver
{
    public function send(NotificationLog $log): ?string
    {
        $settings = IntegrationSetting::forProvider('twilio');

        if (!$settings) {
            throw new \RuntimeException('Twilio integration not configured.');
        }

        $accountSid = $settings->getCredential('account_sid');
        $authToken = $settings->getCredential('auth_token');
        $fromNumber = $settings->getCredential('from_number');

        if (!$accountSid || !$authToken || !$fromNumber) {
            throw new \RuntimeException('Twilio credentials incomplete.');
        }

        $response = Http::withBasicAuth($accountSid, $authToken)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'To' => $log->recipient,
                'From' => $fromNumber,
                'Body' => $log->body,
            ]);

        if ($response->failed()) {
            $error = $response->json('message', 'Unknown Twilio error');
            Log::error('[Notifications] SMS send failed', [
                'recipient' => $log->recipient,
                'status' => $response->status(),
                'error' => $error,
            ]);
            throw new \RuntimeException("Twilio SMS failed: {$error}");
        }

        $sid = $response->json('sid');

        Log::info('[Notifications] SMS sent', [
            'recipient' => $log->recipient,
            'sid' => $sid,
            'event' => $log->event->value,
        ]);

        return $sid;
    }

    public function isConfigured(): bool
    {
        $settings = IntegrationSetting::forProvider('twilio');
        return $settings
            && $settings->is_enabled
            && $settings->getCredential('account_sid')
            && $settings->getCredential('auth_token')
            && $settings->getCredential('from_number');
    }
}
