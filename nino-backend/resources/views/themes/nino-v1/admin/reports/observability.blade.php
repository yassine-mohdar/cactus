@extends('admin.layouts.app')

@section('title', 'Observability')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Observability</h1>
        <p class="page-subtitle">Notification failures, payment callback incidents, queue health, and slow-request signals.</p>
    </div>
    <a href="{{ route('admin.notifications.logs.index', ['status' => 'failed']) }}" class="btn-secondary">Notification Failures</a>
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
    <div class="space-y-6">
        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-md border border-slate-200 bg-white p-4"><p class="text-xs font-medium uppercase text-slate-500">Notification Incidents</p><p class="mt-1 text-2xl font-bold text-red-600">{{ $notificationStats['failed_total'] }}</p></div>
            <div class="rounded-md border border-slate-200 bg-white p-4"><p class="text-xs font-medium uppercase text-slate-500">Retryable</p><p class="mt-1 text-2xl font-bold text-slate-900">{{ $notificationStats['retryable'] }}</p></div>
            <div class="rounded-md border border-slate-200 bg-white p-4"><p class="text-xs font-medium uppercase text-slate-500">Failures 24h</p><p class="mt-1 text-2xl font-bold text-slate-900">{{ $notificationStats['last_24h'] }}</p></div>
            <div class="rounded-md border border-slate-200 bg-white p-4"><p class="text-xs font-medium uppercase text-slate-500">Channels Affected</p><p class="mt-1 text-2xl font-bold text-slate-900">{{ $notificationStats['channels_affected'] }}</p></div>
        </div>

        <div class="page-section p-6">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-900">Notification Failure Breakdown</h2>
            @if($notificationChannelBreakdown->isEmpty())
                <p class="text-sm text-slate-500">No notification incidents detected.</p>
            @else
                <table class="nino-table">
                    <thead><tr><th>Channel</th><th class="text-right">Incidents</th></tr></thead>
                    <tbody>
                        @foreach($notificationChannelBreakdown as $row)
                            <tr><td class="px-4 py-3 text-slate-900">{{ $row['channel']->label() }}</td><td class="px-4 py-3 text-right font-semibold text-slate-900">{{ $row['count'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="page-section p-6">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-900">Recent Notification Failures</h2>
            @if($recentNotificationFailures->isEmpty())
                <p class="text-sm text-slate-500">No failed notification logs found.</p>
            @else
                <table class="nino-table">
                    <thead><tr><th>Event</th><th>Recipient</th><th>Status</th><th>Error</th></tr></thead>
                    <tbody>
                        @foreach($recentNotificationFailures as $log)
                            <tr>
                                <td class="px-4 py-3"><a href="{{ route('admin.notifications.logs.show', $log) }}" class="font-medium text-slate-900 hover:text-indigo-600">{{ $log->event?->label() ?? $log->event }}</a></td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-900">{{ $log->recipient }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $log->status?->label() ?? $log->status }}</td>
                                <td class="px-4 py-3 text-sm text-slate-500">{{ \Illuminate\Support\Str::limit($log->error_message ?? 'No provider error recorded.', 100) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-md border border-slate-200 bg-white p-4"><p class="text-xs font-medium uppercase text-slate-500">Payment Incidents</p><p class="mt-1 text-2xl font-bold text-red-600">{{ $paymentStats['failed_total'] }}</p></div>
            <div class="rounded-md border border-slate-200 bg-white p-4"><p class="text-xs font-medium uppercase text-slate-500">Webhook Failures</p><p class="mt-1 text-2xl font-bold text-slate-900">{{ $paymentStats['webhook_failures'] }}</p></div>
            <div class="rounded-md border border-slate-200 bg-white p-4"><p class="text-xs font-medium uppercase text-slate-500">Callback Failures</p><p class="mt-1 text-2xl font-bold text-slate-900">{{ $paymentStats['callback_failures'] }}</p></div>
            <div class="rounded-md border border-slate-200 bg-white p-4"><p class="text-xs font-medium uppercase text-slate-500">Slow Ops 24h</p><p class="mt-1 text-2xl font-bold text-slate-900">{{ $slowOperationStats['events_24h'] }}</p></div>
        </div>

        <div class="page-section p-6">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-900">Payment Gateway Incident Load</h2>
            @if($paymentGatewayBreakdown->isEmpty())
                <p class="text-sm text-slate-500">No payment callback issues recorded.</p>
            @else
                <table class="nino-table">
                    <thead><tr><th>Gateway</th><th class="text-right">Incidents</th></tr></thead>
                    <tbody>
                        @foreach($paymentGatewayBreakdown as $row)
                            <tr><td class="px-4 py-3 font-mono text-xs uppercase text-slate-900">{{ $row['gateway'] }}</td><td class="px-4 py-3 text-right font-semibold text-slate-900">{{ $row['count'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="page-section p-6">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-900">Recent Payment Callback Errors</h2>
            @if($recentPaymentFailures->isEmpty())
                <p class="text-sm text-slate-500">No payment callback errors recorded.</p>
            @else
                <table class="nino-table">
                    <thead><tr><th>Gateway</th><th>Event</th><th>Error</th></tr></thead>
                    <tbody>
                        @foreach($recentPaymentFailures as $log)
                            <tr><td class="px-4 py-3 font-mono text-xs uppercase text-slate-900">{{ $log->gateway }}</td><td class="px-4 py-3 text-slate-900">{{ str_replace('_', ' ', $log->event_type) }}</td><td class="px-4 py-3 text-sm text-slate-500">{{ \Illuminate\Support\Str::limit($log->error_message ?? 'No error message recorded.', 100) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="page-section p-6">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-900">Queue Health</h2>
            <div class="mb-4 grid grid-cols-2 gap-4">
                <div class="rounded-md border border-slate-200 bg-slate-50 p-4"><p class="text-xs font-medium uppercase text-slate-500">Pending Jobs</p><p class="mt-1 text-xl font-bold text-slate-900">{{ $queueStats['pending_jobs'] }}</p></div>
                <div class="rounded-md border border-slate-200 bg-slate-50 p-4"><p class="text-xs font-medium uppercase text-slate-500">Failed Jobs</p><p class="mt-1 text-xl font-bold text-red-600">{{ $queueStats['failed_jobs'] }}</p></div>
            </div>

            @if($recentFailedJobs->isEmpty())
                <p class="text-sm text-slate-500">No failed jobs recorded.</p>
            @else
                <table class="nino-table">
                    <thead><tr><th>Job</th><th>Queue</th><th>Failure</th></tr></thead>
                    <tbody>
                        @foreach($recentFailedJobs as $job)
                            <tr><td class="px-4 py-3 text-slate-900">{{ $job['name'] }}</td><td class="px-4 py-3 font-mono text-xs text-slate-900">{{ $job['queue'] }}</td><td class="px-4 py-3 text-sm text-slate-500">{{ $job['summary'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="page-section p-6">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-900">Slow Operations</h2>
            <p class="mb-4 text-sm text-slate-500">Threshold: {{ $slowOperationStats['threshold_ms'] }} ms. Average: {{ $slowOperationStats['avg_duration_ms'] }} ms. Max: {{ $slowOperationStats['max_duration_ms'] }} ms.</p>
            @if($recentSlowOperations->isEmpty())
                <p class="text-sm text-slate-500">No slow requests have been recorded.</p>
            @else
                <table class="nino-table">
                    <thead><tr><th>Route</th><th>Method</th><th class="text-right">Duration</th></tr></thead>
                    <tbody>
                        @foreach($recentSlowOperations as $event)
                            <tr><td class="px-4 py-3 text-slate-900">{{ $event->name }}</td><td class="px-4 py-3 font-mono text-xs uppercase text-slate-900">{{ $event->method }}</td><td class="px-4 py-3 text-right font-semibold text-slate-900">{{ $event->duration_ms }} ms</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
