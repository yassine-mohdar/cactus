<?php

namespace App\Modules\Reports\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notifications\Enums\NotificationChannel;
use App\Modules\Notifications\Enums\NotificationStatus;
use App\Modules\Notifications\Models\NotificationLog;
use App\Modules\Payments\Models\PaymentLog;
use App\Modules\Reports\Models\ObservabilityEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ObservabilityReportController extends Controller
{
    public function index()
    {
        $notificationFailuresQuery = NotificationLog::query()
            ->whereIn('status', [NotificationStatus::FAILED->value, NotificationStatus::RETRYING->value]);

        $paymentCallbackIssuesQuery = PaymentLog::query()
            ->where(function ($query) {
                $query->where('is_successful', false)
                    ->orWhereNotNull('error_message');
            })
            ->whereIn('event_type', ['callback', 'webhook', 'error', 'verification']);

        $hasObservabilityEventsTable = Schema::hasTable('observability_events');
        $hasJobsTable = Schema::hasTable('jobs');
        $hasFailedJobsTable = Schema::hasTable('failed_jobs');
        $hasJobBatchesTable = Schema::hasTable('job_batches');

        $slowOperationsQuery = $hasObservabilityEventsTable
            ? ObservabilityEvent::query()->where('event_type', 'slow_request')
            : null;

        $notificationStats = [
            'failed_total' => (clone $notificationFailuresQuery)->count(),
            'retryable' => NotificationLog::retryable()->count(),
            'last_24h' => (clone $notificationFailuresQuery)->where('created_at', '>=', now()->subDay())->count(),
            'channels_affected' => (clone $notificationFailuresQuery)->distinct()->count('channel'),
        ];

        $notificationChannelBreakdown = collect(NotificationChannel::cases())
            ->map(fn (NotificationChannel $channel) => [
                'channel' => $channel,
                'count' => (int) ((clone $notificationFailuresQuery)
                    ->where('channel', $channel->value)
                    ->count()),
            ])
            ->filter(fn (array $row) => $row['count'] > 0)
            ->values();

        $recentNotificationFailures = (clone $notificationFailuresQuery)
            ->with('template')
            ->latest()
            ->limit(10)
            ->get();

        $paymentStats = [
            'failed_total' => (clone $paymentCallbackIssuesQuery)->count(),
            'webhook_failures' => (clone $paymentCallbackIssuesQuery)->where('event_type', 'webhook')->count(),
            'callback_failures' => (clone $paymentCallbackIssuesQuery)->where('event_type', 'callback')->count(),
            'last_24h' => (clone $paymentCallbackIssuesQuery)->where('created_at', '>=', now()->subDay())->count(),
        ];

        $paymentGatewayBreakdown = (clone $paymentCallbackIssuesQuery)
            ->selectRaw('gateway, COUNT(*) as aggregate')
            ->groupBy('gateway')
            ->orderByDesc('aggregate')
            ->get()
            ->map(fn ($row) => [
                'gateway' => $row->gateway,
                'count' => (int) $row->aggregate,
            ]);

        $recentPaymentFailures = (clone $paymentCallbackIssuesQuery)
            ->latest()
            ->limit(10)
            ->get();

        $queueStats = [
            'pending_jobs' => $hasJobsTable ? DB::table('jobs')->count() : 0,
            'failed_jobs' => $hasFailedJobsTable ? DB::table('failed_jobs')->count() : 0,
            'failed_last_24h' => $hasFailedJobsTable
                ? DB::table('failed_jobs')->where('failed_at', '>=', now()->subDay())->count()
                : 0,
            'active_batches' => $hasJobBatchesTable
                ? DB::table('job_batches')->whereNull('finished_at')->count()
                : 0,
        ];

        $queueBreakdown = $hasJobsTable
            ? DB::table('jobs')
                ->selectRaw('queue, COUNT(*) as aggregate, MIN(created_at) as oldest_created_at')
                ->groupBy('queue')
                ->orderByDesc('aggregate')
                ->get()
                ->map(fn ($row) => [
                    'queue' => $row->queue,
                    'count' => (int) $row->aggregate,
                    'oldest_created_at' => $row->oldest_created_at ? Carbon::createFromTimestamp((int) $row->oldest_created_at) : null,
                ])
            : collect();

        $recentFailedJobs = $hasFailedJobsTable
            ? DB::table('failed_jobs')
                ->orderByDesc('failed_at')
                ->limit(10)
                ->get()
                ->map(function ($job) {
                    $payload = json_decode($job->payload, true);
                    $jobName = data_get($payload, 'displayName')
                        ?? data_get($payload, 'data.commandName')
                        ?? data_get($payload, 'job')
                        ?? 'Queue Job';

                    return [
                        'id' => $job->id,
                        'queue' => $job->queue,
                        'name' => Str::afterLast((string) $jobName, '\\'),
                        'failed_at' => $job->failed_at,
                        'summary' => Str::limit(Str::before((string) $job->exception, "\n"), 180),
                    ];
                })
            : collect();

        $slowOperationStats = [
            'events_24h' => $hasObservabilityEventsTable
                ? (clone $slowOperationsQuery)->where('occurred_at', '>=', now()->subDay())->count()
                : 0,
            'avg_duration_ms' => $hasObservabilityEventsTable
                ? (int) round((clone $slowOperationsQuery)->where('occurred_at', '>=', now()->subDay())->avg('duration_ms') ?? 0)
                : 0,
            'max_duration_ms' => $hasObservabilityEventsTable
                ? (int) ((clone $slowOperationsQuery)->where('occurred_at', '>=', now()->subDay())->max('duration_ms') ?? 0)
                : 0,
            'threshold_ms' => (int) config('observability.slow_request_threshold_ms', 1200),
        ];

        $recentSlowOperations = $hasObservabilityEventsTable
            ? (clone $slowOperationsQuery)
                ->latest('occurred_at')
                ->limit(10)
                ->get()
            : collect();

        return view('admin.reports.observability', [
            'notificationStats' => $notificationStats,
            'notificationChannelBreakdown' => $notificationChannelBreakdown,
            'recentNotificationFailures' => $recentNotificationFailures,
            'paymentStats' => $paymentStats,
            'paymentGatewayBreakdown' => $paymentGatewayBreakdown,
            'recentPaymentFailures' => $recentPaymentFailures,
            'queueStats' => $queueStats,
            'queueBreakdown' => $queueBreakdown,
            'recentFailedJobs' => $recentFailedJobs,
            'slowOperationStats' => $slowOperationStats,
            'recentSlowOperations' => $recentSlowOperations,
            'observabilityTables' => [
                'events' => $hasObservabilityEventsTable,
                'jobs' => $hasJobsTable,
                'failed_jobs' => $hasFailedJobsTable,
                'job_batches' => $hasJobBatchesTable,
            ],
        ]);
    }
}
