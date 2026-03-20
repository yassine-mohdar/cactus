@extends('admin.layouts.app')

@section('title', 'Notification Templates')

@section('header')
    <x-nino.page-header
        title="Notification Templates"
        subtitle="Manage event-by-event content, channel coverage, and enablement across the notification system.">
        <x-slot:actions>
            <x-nino.button href="{{ route('admin.notifications.templates.create') }}" variant="primary" icon="add">New Template</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    @if(session('success'))
        <x-nino.inline-alert tone="success" title="Template updated" class="mb-6">
            {{ session('success') }}
        </x-nino.inline-alert>
    @endif

    @if(session('error'))
        <x-nino.inline-alert tone="danger" title="Template action failed" class="mb-6">
            {{ session('error') }}
        </x-nino.inline-alert>
    @endif

    <div class="stats-grid mb-6">
        <div class="stat-card">
            <p class="stat-label">Templates</p>
            <p class="stat-value">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Enabled</p>
            <p class="stat-value text-[#1F7A4E]">{{ number_format($stats['enabled']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Disabled</p>
            <p class="stat-value text-[#C45143]">{{ number_format($stats['disabled']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Events Covered</p>
            <p class="stat-value text-[#3B6F95]">{{ number_format($stats['events_covered']) }}</p>
        </div>
    </div>

    <div class="filter-toolbar mb-6">
        <x-nino.tab-strip label="Notification workspace">
            <a href="{{ route('admin.notifications.templates.index') }}" class="tab-pill tab-pill-active">Templates</a>
            <a href="{{ route('admin.notifications.logs.index') }}" class="tab-pill">Delivery Logs</a>
            <a href="{{ route('admin.notifications.integrations.index') }}" class="tab-pill">Integrations</a>
        </x-nino.tab-strip>
    </div>

    <div class="space-y-6">
        @forelse($grouped as $group => $groupTemplates)
            <div class="datatable-shell">
                <div class="datatable-header">
                    <div>
                        <h2 class="datatable-title">{{ $group }}</h2>
                        <p class="datatable-subtitle">Templates for the {{ strtolower($group) }} event group.</p>
                    </div>
                    <span class="datatable-meta">{{ number_format($groupStats[$group]['enabled'] ?? 0) }} / {{ number_format($groupStats[$group]['count'] ?? 0) }} enabled</span>
                </div>

                <x-nino.table>
                    <x-slot:head>
                        <th>Event</th>
                        <th>Channel</th>
                        <th>Name</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Logs</th>
                        <th class="text-right">Actions</th>
                    </x-slot:head>

                    <x-slot:body>
                        @foreach($groupTemplates as $template)
                            <tr>
                                <td class="text-sm font-semibold text-[#1E2B27]">{{ $template->event->label() }}</td>
                                <td class="table-muted">{{ $template->channel->icon() }} {{ $template->channel->label() }}</td>
                                <td>
                                    <p class="text-sm font-semibold text-[#1E2B27]">{{ $template->name }}</p>
                                </td>
                                <td class="max-w-[260px]">
                                    <p class="truncate text-sm text-[#61706B]">{{ $template->subject ?: 'No subject' }}</p>
                                </td>
                                <td>
                                    <form action="{{ route('admin.notifications.templates.toggle', $template) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit">
                                            <x-nino.status-badge :tone="$template->is_enabled ? 'success' : 'neutral'" size="sm">
                                                {{ $template->is_enabled ? 'Enabled' : 'Disabled' }}
                                            </x-nino.status-badge>
                                        </button>
                                    </form>
                                </td>
                                <td class="font-mono text-sm text-[#1E2B27]">{{ number_format($template->logs_count) }}</td>
                                <td class="text-right">
                                    <div class="table-actions">
                                        <a href="{{ route('admin.notifications.templates.edit', $template) }}" class="table-action-link">Edit</a>
                                        <form action="{{ route('admin.notifications.templates.destroy', $template) }}" method="POST" class="inline" onsubmit="return confirm('Delete this template?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="table-action-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </x-slot:body>
                </x-nino.table>
            </div>
        @empty
            <x-nino.empty-state
                title="No templates created yet"
                description="Create the first notification template to start covering outbound events across email, SMS, and WhatsApp."
                icon="description" />
        @endforelse
    </div>
@endsection
