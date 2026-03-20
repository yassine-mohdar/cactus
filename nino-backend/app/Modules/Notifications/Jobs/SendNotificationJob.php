<?php

namespace App\Modules\Notifications\Jobs;

use App\Modules\Notifications\Enums\NotificationStatus;
use App\Modules\Notifications\Models\NotificationLog;
use App\Modules\Notifications\Services\NotificationDispatcher;
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
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
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
            $log->markFailed("Channel {$log->channel->value} is not configured.");
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

            $log->markFailed($e->getMessage());

            // If retryable, schedule re-dispatch
            if ($log->canRetry()) {
                $delay = now()->addMinutes(pow(2, $log->attempts)); // 2, 4, 8 min
                self::dispatch($log->id)->delay($delay);
            }
        }
    }
}
