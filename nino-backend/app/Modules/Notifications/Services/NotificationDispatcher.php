<?php

namespace App\Modules\Notifications\Services;

use App\Modules\Notifications\Channels\EmailChannel;
use App\Modules\Notifications\Channels\SmsChannel;
use App\Modules\Notifications\Channels\WhatsAppChannel;
use App\Modules\Notifications\Contracts\NotificationChannelDriver;
use App\Modules\Notifications\Enums\NotificationChannel;
use App\Modules\Notifications\Enums\NotificationEvent;
use App\Modules\Notifications\Enums\NotificationStatus;
use App\Modules\Notifications\Jobs\SendNotificationJob;
use App\Modules\Notifications\Models\NotificationLog;
use App\Modules\Notifications\Models\NotificationTemplate;
use Illuminate\Support\Facades\Log;

/**
 * Central notification dispatcher.
 *
 * Usage:
 *   app(NotificationDispatcher::class)->dispatch(
 *       NotificationEvent::ORDER_PLACED,
 *       'customer@email.com',
 *       ['order_reference' => 'NW-001', 'order_total' => '149.00', ...],
 *       customerId: $customer->id,
 *   );
 *
 * The dispatcher:
 * 1. Finds all enabled templates for the event
 * 2. Renders each template with the provided variables
 * 3. Creates a queued NotificationLog per channel
 * 4. Dispatches async queue jobs for delivery
 */
class NotificationDispatcher
{
    /**
     * Resolve a channel driver by enum.
     */
    public function resolveDriver(NotificationChannel $channel): NotificationChannelDriver
    {
        return match($channel) {
            NotificationChannel::EMAIL => new EmailChannel(),
            NotificationChannel::SMS => new SmsChannel(),
            NotificationChannel::WHATSAPP => new WhatsAppChannel(),
        };
    }

    /**
     * Dispatch notifications for a given event across all enabled channels.
     *
     * @param NotificationEvent $event  The business event
     * @param string            $recipient  Primary recipient (email or phone)
     * @param array             $variables  Template variables
     * @param int|null          $customerId Customer ID for log association
     * @param array<NotificationChannel>|null $onlyChannels  Limit to specific channels
     */
    public function dispatch(
        NotificationEvent $event,
        string $recipient,
        array $variables = [],
        ?int $customerId = null,
        ?array $onlyChannels = null,
    ): array {
        // Enrich variables with store defaults
        $variables = array_merge([
            'store_name' => config('app.name', 'NinoWorld'),
            'store_url' => config('app.url', 'https://ninoworld.com'),
        ], $variables);

        $templates = NotificationTemplate::enabled()
            ->forEvent($event)
            ->get();

        if ($templates->isEmpty()) {
            Log::debug("[Notifications] No enabled templates for event: {$event->value}");
            return [];
        }

        $logs = [];

        foreach ($templates as $template) {
            // Skip if channel filter is active and this channel isn't included
            if ($onlyChannels && !in_array($template->channel, $onlyChannels)) {
                continue;
            }

            // Determine recipient per channel
            $channelRecipient = $this->resolveRecipient($template->channel, $recipient, $variables);
            if (!$channelRecipient) {
                Log::debug("[Notifications] No recipient for channel {$template->channel->value}, event {$event->value}");
                continue;
            }

            // Create the log record with rendered content
            $log = NotificationLog::create([
                'template_id' => $template->id,
                'event' => $event,
                'channel' => $template->channel,
                'status' => NotificationStatus::QUEUED,
                'recipient' => $channelRecipient,
                'customer_id' => $customerId,
                'subject' => $template->renderSubject($variables),
                'body' => $template->render($variables),
                'order_reference' => $variables['order_reference'] ?? null,
                'variables' => $variables,
                'attempts' => 0,
                'max_attempts' => 3,
            ]);

            // Dispatch to queue
            SendNotificationJob::dispatch($log->id);

            $logs[] = $log;
        }

        return $logs;
    }

    /**
     * Retry a failed notification.
     */
    public function retry(NotificationLog $log): void
    {
        if (!$log->canRetry()) {
            throw new \RuntimeException("Log #{$log->id} cannot be retried (max attempts reached or not failed).");
        }

        $log->update(['status' => NotificationStatus::RETRYING]);
        SendNotificationJob::dispatch($log->id);
    }

    /**
     * Resolve the correct recipient value per channel.
     * Email → email address, SMS/WhatsApp → phone number.
     */
    private function resolveRecipient(NotificationChannel $channel, string $primary, array $variables): ?string
    {
        return match($channel) {
            NotificationChannel::EMAIL => filter_var($primary, FILTER_VALIDATE_EMAIL) ? $primary : ($variables['customer_email'] ?? null),
            NotificationChannel::SMS, NotificationChannel::WHATSAPP => $this->isPhoneNumber($primary) ? $primary : ($variables['customer_phone'] ?? null),
        };
    }

    private function isPhoneNumber(string $value): bool
    {
        return (bool) preg_match('/^[\+]?[\d\s\-]{8,}$/', $value);
    }
}
