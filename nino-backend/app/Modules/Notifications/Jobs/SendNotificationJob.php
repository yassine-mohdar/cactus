<?php

namespace App\Modules\Notifications\Jobs;

use App\Modules\Notifications\Enums\NotificationStatus;
use App\Modules\Notifications\Models\NotificationLog;
use App\Modules\Notifications\Services\NotificationDispatcher;
use App\Modules\Notifications\Services\NotificationSettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Async queue job for delivering a single notification.
 *
 * Features:
 * - Picks up the log record, resolves the channel driver, sends it
 * - Marks the log as sent or failed
 * - Supports automatic retries with exponential backoff (via NotificationLog)
 */
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;       // The log model manages its own retry count
    public int $timeout = 30;

    public function __construct(
        private int $logId
    ) {
        $this->afterCommit();
        $this->onQueue(app(NotificationSettingsService::class)->queueName());
    }

    public function handle(): void
    {
        $notificationSettings = app(NotificationSettingsService::class);
        $log = NotificationLog::find($this->logId);

        if (!$log) {
            Log::warning("[Notifications] Log #{$this->logId} not found, skipping.");
            return;
        }

        // Already sent — idempotency guard
        if ($log->status === NotificationStatus::SENT) {
            return;
        }

        $dispatcher = app(NotificationDispatcher::class);
        $driver = $dispatcher->resolveDriver($log->channel);

        // Check channel config
        if (!$driver->isConfigured()) {
            $log->markFailed(
                "Channel {$log->channel->value} is not configured.",
                $notificationSettings->retryDelayMinutesForAttempt($log->attempts),
            );
            Log::warning("[Notifications] Channel not configured", [
                'channel' => $log->channel->value,
                'log_id' => $log->id,
            ]);
            return;
        }

        $log->incrementAttempts();
        $log->update(['status' => NotificationStatus::SENDING]);

        try {
            $externalId = $driver->send($log);
            $log->markSent($externalId);
        } catch (\Throwable $e) {
            Log::error("[Notifications] Send failed", [
                'log_id' => $log->id,
                'channel' => $log->channel->value,
                'event' => $log->event->value,
                'error' => $e->getMessage(),
            ]);

            $retryDelayMinutes = $notificationSettings->retryDelayMinutesForAttempt($log->attempts);
            $log->markFailed($e->getMessage(), $retryDelayMinutes);

            // If retryable, schedule re-dispatch
            if ($log->canRetry()) {
                $delay = now()->addMinutes($retryDelayMinutes);
                self::dispatch($log->id)->delay($delay);
            }
        }
    }
}
