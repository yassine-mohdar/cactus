@extends('admin.layouts.app')

@php
    $statusTone = match ($log->status->value) {
        'sent' => 'success',
        'failed' => 'danger',
        'retrying', 'queued' => 'warning',
        default => 'info',
    };
@endphp

@section('title', 'Notification Log #' . $log->id)

@section('header')
    <x-nino.page-header
        title="Notification Log #{{ $log->id }}"
        subtitle="Inspect rendered content, delivery status, retry state, and template variables for this outbound notification.">
        <x-slot:actions>
            <x-nino.status-badge :tone="$statusTone">{{ $log->status->label() }}</x-nino.status-badge>
            <x-nino.button href="{{ route('admin.notifications.logs.index') }}" variant="secondary" icon="arrow_back">Back to Logs</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    @if(session('success'))
        <x-nino.inline-alert tone="success" title="Notification action completed" class="mb-6">
            {{ session('success') }}
        </x-nino.inline-alert>
    @endif

    @if(session('error'))
        <x-nino.inline-alert tone="danger" title="Notification action failed" class="mb-6">
            {{ session('error') }}
        </x-nino.inline-alert>
    @endif

    <div class="detail-grid">
        <div class="detail-main">
            <x-nino.detail-section title="Delivery Record" subtitle="Core metadata, routing, and template context for this notification.">
                <x-nino.entity-detail-grid class="xl:grid-cols-3">
                    <div>
                        <p class="detail-kicker">Event</p>
                        <p class="detail-value">{{ $log->event->label() }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Channel</p>
                        <p class="detail-value">{{ $log->channel->icon() }} {{ $log->channel->label() }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Recipient</p>
                        <p class="detail-value">{{ $log->recipient }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Order Reference</p>
                        <p class="detail-value-mono">{{ $log->order_reference ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Template</p>
                        <p class="detail-value">{{ $log->template?->name ?? 'No template' }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">External ID</p>
                        <p class="detail-value-mono">{{ $log->external_id ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Attempts</p>
                        <p class="detail-value-mono">{{ $log->attempts }}/{{ $log->max_attempts }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Created</p>
                        <p class="detail-value">{{ $log->created_at->format('M d, Y H:i:s') }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Sent At</p>
                        <p class="detail-value">{{ $log->sent_at?->format('M d, Y H:i:s') ?? 'Pending' }}</p>
                    </div>
                </x-nino.entity-detail-grid>
            </x-nino.detail-section>

            @if($log->subject)
                <x-nino.detail-section title="Subject" subtitle="Rendered subject line used for email delivery.">
                    <p class="detail-note">{{ $log->subject }}</p>
                </x-nino.detail-section>
            @endif

            <x-nino.detail-section title="Message Body" subtitle="Rendered content that was sent or queued for delivery.">
                <div class="surface-panel font-mono text-xs leading-6 text-[#1E2B27] whitespace-pre-wrap">
                    {{ $log->body }}
                </div>
            </x-nino.detail-section>

            @if($log->error_message)
                <x-nino.detail-section title="Delivery Error" subtitle="Last failure reason returned by the channel transport.">
                    <x-nino.inline-alert tone="danger" title="Delivery failed">
                        <span class="font-mono text-xs">{{ $log->error_message }}</span>
                    </x-nino.inline-alert>
                </x-nino.detail-section>
            @endif
        </div>

        <div class="detail-sidebar">
            @if($log->canRetry())
                <x-nino.detail-section title="Retry Action" subtitle="The notification is still within its allowed retry attempts.">
                    <form action="{{ route('admin.notifications.logs.retry', $log) }}" method="POST">
                        @csrf
                        <x-nino.button type="submit" variant="primary" class="w-full justify-center">Retry Notification</x-nino.button>
                    </form>
                </x-nino.detail-section>
            @endif

            @if($log->variables)
                <x-nino.detail-section title="Template Variables" subtitle="Resolved variable payload injected into the template.">
                    <div class="space-y-2">
                        @foreach($log->variables as $key => $value)
                            <div class="surface-panel-compact flex items-start justify-between gap-3">
                                <span class="font-mono text-xs text-[#61706B]">{{ $key }}</span>
                                <span class="max-w-[180px] text-right text-sm text-[#1E2B27]">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-nino.detail-section>
            @endif

            <x-nino.detail-section title="Timing" subtitle="Delivery lifecycle timestamps for this notification attempt.">
                <x-nino.entity-detail-grid>
                    <div>
                        <p class="detail-kicker">Created</p>
                        <p class="detail-value">{{ $log->created_at->format('M d, H:i:s') }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Sent</p>
                        <p class="detail-value">{{ $log->sent_at?->format('M d, H:i:s') ?? 'Pending' }}</p>
                    </div>
                    <div>
                        <p class="detail-kicker">Next Retry</p>
                        <p class="detail-value">{{ $log->next_retry_at?->format('M d, H:i:s') ?? 'Not scheduled' }}</p>
                    </div>
                </x-nino.entity-detail-grid>
            </x-nino.detail-section>
        </div>
    </div>
@endsection
