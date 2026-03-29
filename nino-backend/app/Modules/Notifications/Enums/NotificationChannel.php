<?php

namespace App\Modules\Notifications\Enums;

/**
 * Delivery channels for notifications.
 */
enum NotificationChannel: string
{
    case EMAIL = 'email';
    case SMS = 'sms';
    case WHATSAPP = 'whatsapp';

    public function label(): string
    {
        return match($this) {
            self::EMAIL => 'Email',
            self::SMS => 'SMS',
            self::WHATSAPP => 'WhatsApp',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::EMAIL => '✉️',
            self::SMS => '📱',
            self::WHATSAPP => '💬',
        };
    }

    /**
     * The config key used to look up channel credentials in integration_settings.
     */
    public function configKey(): string
    {
        return match($this) {
            self::EMAIL => 'smtp',
            self::SMS => 'twilio',
            self::WHATSAPP => 'whatsapp_api',
        };
    }
}
