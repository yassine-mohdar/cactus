<?php

namespace App\Modules\Notifications\Channels;

use App\Modules\Notifications\Contracts\NotificationChannelDriver;
use App\Modules\Notifications\Models\IntegrationSetting;
use App\Modules\Notifications\Models\NotificationLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Channel Driver — Cloud API (Meta Business Platform)
 *
 * Uses the WhatsApp Business Cloud API to send template-based messages.
 * Supports text messages through the messages endpoint.
 */
class WhatsAppChannel implements NotificationChannelDriver
{
    public function send(NotificationLog $log): ?string
    {
        $settings = IntegrationSetting::forProvider('whatsapp_api');

        if (!$settings) {
            throw new \RuntimeException('WhatsApp API integration not configured.');
        }

        $accessToken = $settings->getCredential('access_token');
        $phoneNumberId = $settings->getCredential('phone_number_id');
        $apiVersion = $settings->getCredential('api_version', 'v18.0');

        if (!$accessToken || !$phoneNumberId) {
            throw new \RuntimeException('WhatsApp API credentials incomplete.');
        }

        // Format recipient to E.164 (remove spaces, dashes)
        $to = preg_replace('/[^\d+]/', '', $log->recipient);

        $response = Http::withToken($accessToken)
            ->post("https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $log->body,
                ],
            ]);

        if ($response->failed()) {
            $error = $response->json('error.message', 'Unknown WhatsApp API error');
            Log::error('[Notifications] WhatsApp send failed', [
                'recipient' => $to,
                'status' => $response->status(),
                'error' => $error,
            ]);
            throw new \RuntimeException("WhatsApp API failed: {$error}");
        }

        $messageId = $response->json('messages.0.id');

        Log::info('[Notifications] WhatsApp message sent', [
            'recipient' => $to,
            'message_id' => $messageId,
            'event' => $log->event->value,
        ]);

        return $messageId;
    }

    public function isConfigured(): bool
    {
        $settings = IntegrationSetting::forProvider('whatsapp_api');
        return $settings
            && $settings->is_enabled
            && $settings->getCredential('access_token')
            && $settings->getCredential('phone_number_id');
    }
}
