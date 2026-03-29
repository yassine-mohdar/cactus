<?php

namespace App\Modules\Notifications\Services;

use App\Modules\Notifications\Models\IntegrationSetting;
use Illuminate\Support\Str;

class IntegrationConnectionTestService
{
    /**
     * Perform a safe, non-delivery validation of the configured provider settings.
     *
     * @return array{status: string, message: string}
     */
    public function test(IntegrationSetting $integration): array
    {
        if (! $integration->is_enabled) {
            return [
                'status' => 'warning',
                'message' => "{$integration->name} is disabled. Enable it before running a live connection test.",
            ];
        }

        if (! $integration->isConfigured()) {
            return [
                'status' => 'error',
                'message' => "{$integration->name} is missing required credentials.",
            ];
        }

        return match ($integration->provider) {
            'smtp' => $this->testSmtp($integration),
            'twilio' => $this->testTwilio($integration),
            'whatsapp_api' => $this->testWhatsApp($integration),
            default => [
                'status' => 'warning',
                'message' => 'No safe connection test is available for this provider.',
            ],
        };
    }

    private function testSmtp(IntegrationSetting $integration): array
    {
        $host = (string) $integration->getCredential('host');
        $port = (int) $integration->getCredential('port', 0);
        $fromAddress = (string) $integration->getCredential('from_address');

        if ($host === '' || ! filter_var($fromAddress, FILTER_VALIDATE_EMAIL) || $port < 1 || $port > 65535) {
            return [
                'status' => 'error',
                'message' => 'SMTP settings are incomplete or invalid. Check host, port, and from address.',
            ];
        }

        return [
            'status' => 'success',
            'message' => "SMTP settings passed safe validation for {$host}:{$port}. No outbound email was sent.",
        ];
    }

    private function testTwilio(IntegrationSetting $integration): array
    {
        $accountSid = (string) $integration->getCredential('account_sid');
        $fromNumber = (string) $integration->getCredential('from_number');

        if (! Str::startsWith($accountSid, 'AC') || strlen($accountSid) < 12) {
            return [
                'status' => 'error',
                'message' => 'Twilio Account SID format is invalid.',
            ];
        }

        if (! preg_match('/^\+?[0-9]{8,15}$/', $fromNumber)) {
            return [
                'status' => 'error',
                'message' => 'Twilio sender number must be a valid E.164-style number.',
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Twilio settings passed safe validation. No outbound SMS was sent.',
        ];
    }

    private function testWhatsApp(IntegrationSetting $integration): array
    {
        $apiVersion = (string) $integration->getCredential('api_version', 'v18.0');
        $phoneNumberId = (string) $integration->getCredential('phone_number_id');
        $businessAccountId = (string) $integration->getCredential('business_account_id');

        if (! preg_match('/^v\d+\.\d+$/', $apiVersion)) {
            return [
                'status' => 'error',
                'message' => 'WhatsApp API version format is invalid.',
            ];
        }

        if ($phoneNumberId === '' || $businessAccountId === '') {
            return [
                'status' => 'error',
                'message' => 'WhatsApp identifiers are incomplete.',
            ];
        }

        return [
            'status' => 'success',
            'message' => 'WhatsApp API settings passed safe validation. No outbound message was sent.',
        ];
    }
}
