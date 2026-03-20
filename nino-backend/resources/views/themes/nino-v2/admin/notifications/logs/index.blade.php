@extends('admin.layouts.app')

@php
    $statusTone = static function ($status): string {
        $value = $status?->value ?? (string) $status;

        return match ($value) {
            'sent' => 'success',
            'failed' => 'danger',
            'retrying', 'queued' => 'warning',
            default => 'info',
        };
    };
@endphp

@section('title', 'Notification Logs')

@section('header')
    <x-nino.page-header
        title="Notification Logs"
        subtitle="Delivery history, retry backlog, and event-level visibility across all channels.">
        <x-slot:actions>
            <x-nino.button href="{{ route('admin.notifications.templates.index') }}" variant="secondary" icon="description">Templates</x-nino.button>
            <x-nino.button href="{{ route('admin.notifications.integrations.index') }}" variant="secondary" icon="settings_input_component">Integrations</x-nino.button>
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

    <div class="stats-grid mb-6">
        <div class="stat-card">
            <p class="stat-label">Total</p>
            <p class="stat-value">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Sent</p>
            <p class="stat-value text-[#1F7A4E]">{{ number_format($stats['sent']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Failed</p>
            <p class="stat-value text-[#C45143]">{{ number_format($stats['failed']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Queued / Retrying</p>
            <p class="stat-value text-[#B8802F]">{{ number_format($stats['queued']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Retryable</p>
            <p class="stat-value text-[#3B6F95]">{{ number_format($stats['retryable']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Last 24 Hours</p>
            <p class="stat-value text-[#245848]">{{ number_format($stats['last_24h']) }}</p>
        </div>
    </div>

    <div class="filter-toolbar mb-6">
        <div class="space-y-4">
            <x-nino.tab-strip label="Notification workspace">
                <a href="{{ route('admin.notifications.logs.index') }}" class="tab-pill tab-pill-active">Delivery Logs</a>
                <a href="{{ route('admin.notifications.templates.index') }}" class="tab-pill">Templates</a>
                <a href="{{ route('admin.notifications.integrations.index') }}" class="tab-pill">Integrations</a>
            </x-nino.tab-strip>

            <form method="GET">
                <div class="filter-grid xl:grid-cols-5">
                    <div class="filter-field">
                        <label class="filter-label" for="notification-status">Status</label>
                        <select id="notification-status" name="status" class="input-field">
                            <option value="">All</option>
                            @foreach(\App\Modules\Notifications\Enums\NotificationStatus::cases() as $s)
                                <option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-field">
                        <label class="filter-label" for="notification-channel">Channel</label>
                        <select id="notification-channel" name="channel" class="input-field">
                            <option value="">All</option>
                            @foreach(\App\Modules\Notifications\Enums\NotificationChannel::cases() as $c)
                                <option value="{{ $c->value }}" {{ request('channel') === $c->value ? 'selected' : '' }}>{{ $c->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-field">
                        <label class="filter-label" for="notification-event">Event</label>
                        <select id="notification-event" name="event" class="input-field">
                            <option value="">All Events</option>
                            @foreach(\App\Modules\Notifications\Enums\NotificationEvent::cases() as $e)
                                <option value="{{ $e->value }}" {{ request('event') === $e->value ? 'selected' : '' }}>{{ $e->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-field">
                        <label class="filter-label" for="notification-search">Search</label>
                        <input id="notification-search" type="text" name="search" value="{{ request('search') }}" placeholder="Recipient, order ref, external ID..." class="input-field">
                    </div>
                    <div class="filter-field xl:items-end">
                        <div class="flex flex-wrap gap-3">
                            <x-nino.button type="submit" variant="primary">Apply Filters</x-nino.button>
                            <x-nino.button href="{{ route('admin.notifications.logs.index') }}" variant="outline">Reset</x-nino.button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(320px,0.9fr)]">
        <div class="datatable-shell">
            <div class="datatable-header">
                <div>
                    <h2 class="datatable-title">Delivery History</h2>
                    <p class="datatable-subtitle">Every notification event with attempt counts, channel, and operator-friendly retry actions.</p>
                </div>
                <span class="datatable-meta">{{ number_format($logs->total()) }} entries</span>
            </div>

            <x-nino.table>
                <x-slot:head>
                    <th>Time</th>
                    <th>Event</th>
                    <th>Channel</th>
                    <th>Recipient</th>
                    <th>Status</th>
                    <th>Attempts</th>
                    <th class="text-right">Actions</th>
                </x-slot:head>

                <x-slot:body>
                    @forelse($logs as $log)
                        <tr class="{{ $log->status->value === 'failed' ? 'bg-[#FCEDEA]/55' : '' }}">
                            <td>
                                <p class="table-mono text-[#61706B]">{{ $log->created_at->format('M d, H:i') }}</p>
                                <p class="mt-1 text-xs text-[#7A8681]">{{ $log->created_at->diffForHumans() }}</p>
                            </td>
                            <td>
                                <p class="text-sm font-semibold text-[#1E2B27]">{{ $log->event->label() }}</p>
                                <p class="mt-1 text-xs text-[#7A8681]">{{ $log->order_reference ?: 'No order reference' }}</p>
                            </td>
                            <td class="table-muted">{{ $log->channel->icon() }} {{ $log->channel->label() }}</td>
                            <td class="max-w-[240px]">
                                <p class="truncate text-sm text-[#1E2B27]">{{ $log->recipient }}</p>
                                @if($log->template?->name)
                                    <p class="mt-1 text-xs text-[#7A8681]">{{ $log->template->name }}</p>
                                @endif
                            </td>
                            <td>
                                <x-nino.status-badge :tone="$statusTone($log->status)" size="sm">{{ $log->status->label() }}</x-nino.status-badge>
                            </td>
                            <td class="font-mono text-sm text-[#1E2B27]">{{ $log->attempts }}/{{ $log->max_attempts }}</td>
                            <td class="text-right">
                                <div class="table-actions">
                                    <a href="{{ route('admin.notifications.logs.show', $log) }}" class="table-action-link">View</a>
                                    @if($log->canRetry())
                                        <form action="{{ route('admin.notifications.logs.retry', $log) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="table-action-link text-[#B8802F] hover:text-[#8A671E]">Retry</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="datatable-empty">
                                <x-nino.empty-state
                                    title="No notification logs yet"
                                    description="Delivery history will appear here as soon as the first outbound notification is generated."
                                    icon="history" />
                            </td>
                        </tr>
                    @endforelse
                </x-slot:body>
            </x-nino.table>

            <div class="datatable-footer">
                {{ $logs->links() }}
            </div>
        </div>

        <div class="detail-stack">
            <x-nino.detail-section title="Channel Mix" subtitle="How outbound traffic is distributed across delivery channels.">
                <div class="space-y-3">
                    @forelse($channelBreakdown as $row)
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-semibold text-[#1E2B27]">{{ $row['channel']->icon() }} {{ $row['channel']->label() }}</span>
                            <span class="font-mono text-sm text-[#61706B]">{{ number_format($row['count']) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-[#61706B]">Channel mix will appear once notifications start flowing.</p>
                    @endforelse
                </div>
            </x-nino.detail-section>

            <x-nino.detail-section title="Top Events" subtitle="Most frequent notification events across the current log history.">
                <div class="space-y-3">
                    @forelse($eventBreakdown as $row)
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-semibold text-[#1E2B27]">{{ $row['event']->label() }}</span>
                            <span class="font-mono text-sm text-[#61706B]">{{ number_format($row['count']) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-[#61706B]">Event distribution will appear once notifications have been generated.</p>
                    @endforelse
                </div>
            </x-nino.detail-section>
        </div>
    </div>
@endsection
