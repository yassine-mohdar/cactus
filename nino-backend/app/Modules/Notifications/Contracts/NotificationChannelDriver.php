<?php

namespace App\Modules\Notifications\Contracts;

use App\Modules\Notifications\Models\NotificationLog;

/**
 * Channel drivers implement this contract.
 * Each channel (Email, SMS, WhatsApp) is responsible for
 * delivering a notification and returning the external message ID.
 */
interface NotificationChannelDriver
{
    /**
     * Send the notification.
     *
     * @param NotificationLog $log The log record containing recipient, subject, body, etc.
     * @return string|null The external message ID from the provider (for tracking).
     * @throws \Exception on failure
     */
    public function send(NotificationLog $log): ?string;

    /**
     * Check if this channel driver is configured and ready.
     */
    public function isConfigured(): bool;
}
