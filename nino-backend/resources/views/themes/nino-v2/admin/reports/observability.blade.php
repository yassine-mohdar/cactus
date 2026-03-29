@extends('admin.layouts.app')

@section('title', 'Observability')

@section('header')
    <x-nino.page-header
        eyebrow="Operations telemetry"
        title="Observability"
        subtitle="Track delivery failures, payment callback incidents, queue health, and slow request signals from one admin surface."
    >
        <div class="flex flex-wrap items-center gap-3">
            <span class="datatable-meta">Slow threshold {{ number_format($slowOperationStats['threshold_ms']) }} ms</span>
            <x-admin.button href="{{ route('admin.notifications.logs.index', ['status' => 'failed']) }}" variant="secondary">Notification Failures</x-admin.button>
        </div>
    </x-nino.page-header>
@endsection

@section('content')
    <div class="grid gap-6 xl:grid-cols-2">
        <div class="space-y-6">
            <div class="stats-grid">
                <div class="stat-card">
                    <p class="stat-label">Notification Incidents</p>
                    <p class="stat-value text-[#C45143]">{{ number_format($notificationStats['failed_total']) }}</p>
                    <p class="mt-2 text-sm text-[#617169]">Failed or retrying deliveries</p>
                </div>
                <div class="stat-card">
                    <p class="stat-label">Retryable Now</p>
                    <p class="stat-value">{{ number_format($notificationStats['retryable']) }}</p>
                    <p class="mt-2 text-sm text-[#617169]">Ready for manual or queued retry</p>
                </div>
                <div class="stat-card">
                    <p class="stat-label">Failures 24h</p>
                    <p class="stat-value">{{ number_format($notificationStats['last_24h']) }}</p>
                    <p class="mt-2 text-sm text-[#617169]">Recent delivery incidents</p>
                </div>
                <div class="stat-card">
                    <p class="stat-label">Channels Affected</p>
                    <p class="stat-value">{{ number_format($notificationStats['channels_affected']) }}</p>
                    <p class="mt-2 text-sm text-[#617169]">Email, SMS, or WhatsApp</p>
                </div>
            </div>

            <div class="datatable-shell">
                <div class="datatable-header">
                    <div>
                        <h2 class="datatable-title">Notification Failure Breakdown</h2>
                        <p class="datatable-subtitle">Channels currently carrying delivery failures or active retries.</p>
                    </div>
                    <span class="datatable-meta">{{ $notificationChannelBreakdown->count() }} channels</span>
                </div>

                @if($notificationChannelBreakdown->isEmpty())
                    <div class="datatable-empty">
                        <div class="datatable-empty-panel">
                            <p class="text-sm font-semibold text-[#17302A]">No notification incidents detected.</p>
                            <p class="text-sm text-[#617169]">Failed and retrying deliveries will appear here once channels begin erroring.</p>
                        </div>
                    </div>
                @else
                    <x-nino.table>
                        <x-slot:head>
                            <th>Channel</th>
                            <th class="text-right">Incidents</th>
                        </x-slot:head>

                        <x-slot:body>
                            @foreach($notificationChannelBreakdown as $row)
                                <tr>
                                    <td class="font-medium text-[#17302A]">{{ $row['channel']->label() }}</td>
                                    <td class="text-right font-semibold text-[#17302A]">{{ number_format($row['count']) }}</td>
                                </tr>
                            @endforeach
                        </x-slot:body>
                    </x-nino.table>
                @endif
            </div>

            <div class="datatable-shell">
                <div class="datatable-header">
                    <div>
                        <h2 class="datatable-title">Recent Notification Failures</h2>
                        <p class="datatable-subtitle">Latest delivery issues with template, recipient, and retry context.</p>
                    </div>
                    <span class="datatable-meta">{{ $recentNotificationFailures->count() }} rows</span>
                </div>

                @if($recentNotificationFailures->isEmpty())
                    <div class="datatable-empty">
                        <div class="datatable-empty-panel">
                            <p class="text-sm font-semibold text-[#17302A]">No failed notification logs found.</p>
                            <p class="text-sm text-[#617169]">This queue stays empty until a channel returns an error or exhausts its retries.</p>
                        </div>
                    </div>
                @else
                    <x-nino.table scrollClass="max-h-[28rem] overflow-y-auto">
                        <x-slot:head>
                            <th>Event</th>
                            <th>Recipient</th>
                            <th>Status</th>
                            <th>Error</th>
                        </x-slot:head>

                        <x-slot:body>
                            @foreach($recentNotificationFailures as $log)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.notifications.logs.show', $log) }}" class="font-medium text-[#17302A] hover:text-[#245848]">
                                            {{ $log->event?->label() ?? str_replace('_', ' ', $log->event) }}
                                        </a>
                                        <div class="mt-1 text-xs uppercase tracking-[0.18em] text-[#7A8681]">{{ $log->channel?->label() ?? $log->channel }}</div>
                                    </td>
                                    <td class="font-mono text-xs text-[#17302A]">{{ $log->recipient }}</td>
                                    <td>
                                        <x-nino.status-badge :status="$log->status?->value ?? (string) $log->status" />
                                    </td>
                                    <td class="max-w-[20rem] text-sm text-[#617169]">
                                        {{ \Illuminate\Support\Str::limit($log->error_message ?? 'No provider error recorded.', 120) }}
                                    </td>
                                </tr>
                            @endforeach
                        </x-slot:body>
                    </x-nino.table>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="stats-grid">
                <div class="stat-card">
                    <p class="stat-label">Payment Incidents</p>
                    <p class="stat-value text-[#C45143]">{{ number_format($paymentStats['failed_total']) }}</p>
                    <p class="mt-2 text-sm text-[#617169]">Callback, webhook, or verification failures</p>
                </div>
                <div class="stat-card">
                    <p class="stat-label">Webhook Failures</p>
                    <p class="stat-value">{{ number_format($paymentStats['webhook_failures']) }}</p>
                    <p class="mt-2 text-sm text-[#617169]">Asynchronous gateway notifications</p>
                </div>
                <div class="stat-card">
                    <p class="stat-label">Callback Failures</p>
                    <p class="stat-value">{{ number_format($paymentStats['callback_failures']) }}</p>
                    <p class="mt-2 text-sm text-[#617169]">Browser-return verification problems</p>
                </div>
                <div class="stat-card">
                    <p class="stat-label">Payment Failures 24h</p>
                    <p class="stat-value">{{ number_format($paymentStats['last_24h']) }}</p>
                    <p class="mt-2 text-sm text-[#617169]">Fresh payment incident volume</p>
                </div>
            </div>

            <div class="datatable-shell">
                <div class="datatable-header">
                    <div>
                        <h2 class="datatable-title">Payment Gateway Incident Load</h2>
                        <p class="datatable-subtitle">Gateways with callback or webhook failures recorded in `payment_logs`.</p>
                    </div>
                    <span class="datatable-meta">{{ $paymentGatewayBreakdown->count() }} gateways</span>
                </div>

                @if($paymentGatewayBreakdown->isEmpty())
                    <div class="datatable-empty">
                        <div class="datatable-empty-panel">
                            <p class="text-sm font-semibold text-[#17302A]">No payment callback issues recorded.</p>
                            <p class="text-sm text-[#617169]">Gateway callback and webhook failures will appear here automatically.</p>
                        </div>
                    </div>
                @else
                    <x-nino.table>
                        <x-slot:head>
                            <th>Gateway</th>
                            <th class="text-right">Incidents</th>
                        </x-slot:head>

                        <x-slot:body>
                            @foreach($paymentGatewayBreakdown as $row)
                                <tr>
                                    <td class="font-medium uppercase tracking-[0.18em] text-[#17302A]">{{ $row['gateway'] }}</td>
                                    <td class="text-right font-semibold text-[#17302A]">{{ number_format($row['count']) }}</td>
                                </tr>
                            @endforeach
                        </x-slot:body>
                    </x-nino.table>
                @endif
            </div>

            <div class="datatable-shell">
                <div class="datatable-header">
                    <div>
                        <h2 class="datatable-title">Recent Payment Callback Errors</h2>
                        <p class="datatable-subtitle">Latest failed callback, webhook, and verification events captured in `payment_logs`.</p>
                    </div>
                    <span class="datatable-meta">{{ $recentPaymentFailures->count() }} rows</span>
                </div>

                @if($recentPaymentFailures->isEmpty())
                    <div class="datatable-empty">
                        <div class="datatable-empty-panel">
                            <p class="text-sm font-semibold text-[#17302A]">No payment callback errors recorded.</p>
                            <p class="text-sm text-[#617169]">Gateway callback incidents will appear here automatically once they occur.</p>
                        </div>
                    </div>
                @else
                    <x-nino.table scrollClass="max-h-[24rem] overflow-y-auto">
                        <x-slot:head>
                            <th>Gateway</th>
                            <th>Event</th>
                            <th>Error</th>
                        </x-slot:head>
                        <x-slot:body>
                            @foreach($recentPaymentFailures as $log)
                                <tr>
                                    <td class="font-mono text-xs uppercase tracking-[0.18em] text-[#17302A]">{{ $log->gateway }}</td>
                                    <td class="text-sm font-medium text-[#17302A]">{{ str_replace('_', ' ', $log->event_type) }}</td>
                                    <td class="max-w-[20rem] text-sm text-[#617169]">
                                        {{ \Illuminate\Support\Str::limit($log->error_message ?? 'No error message recorded.', 120) }}
                                    </td>
                                </tr>
                            @endforeach
                        </x-slot:body>
                    </x-nino.table>
                @endif
            </div>

            <div class="datatable-shell">
                <div class="datatable-header">
                    <div>
                        <h2 class="datatable-title">Queue Health</h2>
                        <p class="datatable-subtitle">Current pending load, recent failed jobs, and active batch pressure.</p>
                    </div>
                    <span class="datatable-meta">{{ number_format($queueStats['pending_jobs']) }} queued</span>
                </div>

                <div class="stats-grid mb-6">
                    <div class="stat-card">
                        <p class="stat-label">Pending Jobs</p>
                        <p class="stat-value">{{ number_format($queueStats['pending_jobs']) }}</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Failed Jobs</p>
                        <p class="stat-value text-[#C45143]">{{ number_format($queueStats['failed_jobs']) }}</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Failed 24h</p>
                        <p class="stat-value">{{ number_format($queueStats['failed_last_24h']) }}</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Active Batches</p>
                        <p class="stat-value">{{ number_format($queueStats['active_batches']) }}</p>
                    </div>
                </div>

                <x-nino.detail-section title="Pending Queue Breakdown" subtitle="Queued jobs grouped by queue name." icon="queue" class="mb-6">
                    @if($queueBreakdown->isEmpty())
                        <x-nino.empty-state title="No pending jobs in the queue." copy="The jobs table is currently clear." icon="check_circle" />
                    @else
                        <x-nino.table>
                            <x-slot:head>
                                <th>Queue</th>
                                <th class="text-right">Pending</th>
                                <th>Oldest Job</th>
                            </x-slot:head>
                            <x-slot:body>
                                @foreach($queueBreakdown as $row)
                                    <tr>
                                        <td class="font-mono text-xs text-[#17302A]">{{ $row['queue'] }}</td>
                                        <td class="text-right font-semibold text-[#17302A]">{{ number_format($row['count']) }}</td>
                                        <td class="text-sm text-[#617169]">{{ $row['oldest_created_at']?->diffForHumans() ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </x-slot:body>
                        </x-nino.table>
                    @endif
                </x-nino.detail-section>

                <x-nino.detail-section title="Recent Failed Jobs" subtitle="Latest failures from Laravel's `failed_jobs` table." icon="error">
                    @if($recentFailedJobs->isEmpty())
                        <x-nino.empty-state title="No failed jobs recorded." copy="This queue is currently healthy." icon="verified" />
                    @else
                        <x-nino.table scrollClass="max-h-[24rem] overflow-y-auto">
                            <x-slot:head>
                                <th>Job</th>
                                <th>Queue</th>
                                <th>Failure</th>
                            </x-slot:head>
                            <x-slot:body>
                                @foreach($recentFailedJobs as $job)
                                    <tr>
                                        <td class="font-medium text-[#17302A]">{{ $job['name'] }}</td>
                                        <td class="font-mono text-xs text-[#17302A]">{{ $job['queue'] }}</td>
                                        <td class="text-sm text-[#617169]">
                                            <div>{{ $job['summary'] }}</div>
                                            <div class="mt-1 text-xs uppercase tracking-[0.16em] text-[#7A8681]">{{ $job['failed_at'] }}</div>
                                        </td>
                                    </tr>
                                @endforeach
                            </x-slot:body>
                        </x-nino.table>
                    @endif
                </x-nino.detail-section>
            </div>

            <div class="datatable-shell">
                <div class="datatable-header">
                    <div>
                        <h2 class="datatable-title">Slow Operations</h2>
                        <p class="datatable-subtitle">Requests automatically logged after crossing the configured threshold.</p>
                    </div>
                    <span class="datatable-meta">{{ number_format($slowOperationStats['events_24h']) }} in 24h</span>
                </div>

                <div class="stats-grid mb-6">
                    <div class="stat-card">
                        <p class="stat-label">Events 24h</p>
                        <p class="stat-value">{{ number_format($slowOperationStats['events_24h']) }}</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Average Duration</p>
                        <p class="stat-value">{{ number_format($slowOperationStats['avg_duration_ms']) }} ms</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Max Duration</p>
                        <p class="stat-value text-[#C45143]">{{ number_format($slowOperationStats['max_duration_ms']) }} ms</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Threshold</p>
                        <p class="stat-value">{{ number_format($slowOperationStats['threshold_ms']) }} ms</p>
                    </div>
                </div>

                @if($recentSlowOperations->isEmpty())
                    <div class="datatable-empty">
                        <div class="datatable-empty-panel">
                            <p class="text-sm font-semibold text-[#17302A]">No slow requests have been recorded.</p>
                            <p class="text-sm text-[#617169]">Requests exceeding the threshold will be written here automatically.</p>
                        </div>
                    </div>
                @else
                    <x-nino.table>
                        <x-slot:head>
                            <th>Route</th>
                            <th>Method</th>
                            <th class="text-right">Duration</th>
                        </x-slot:head>
                        <x-slot:body>
                            @foreach($recentSlowOperations as $event)
                                <tr>
                                    <td>
                                        <div class="font-medium text-[#17302A]">{{ $event->name }}</div>
                                        <div class="mt-1 text-xs text-[#7A8681]">{{ $event->url }}</div>
                                    </td>
                                    <td class="font-mono text-xs uppercase tracking-[0.18em] text-[#17302A]">{{ $event->method }}</td>
                                    <td class="text-right font-semibold text-[#17302A]">{{ number_format($event->duration_ms) }} ms</td>
                                </tr>
                            @endforeach
                        </x-slot:body>
                    </x-nino.table>
                @endif
            </div>
        </div>
    </div>
@endsection
