@extends('admin.layouts.app')

@section('title', 'Notification Logs')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Notification Logs</h1>
    <p class="text-sm text-slate-500 mt-1">Delivery history, failures, and retry tracking.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm border border-green-200">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm border border-red-200">{{ session('error') }}</div>
@endif

{{-- Stats Cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white border border-slate-200 rounded-md p-4">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Sent</p>
        <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['sent'] }}</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-md p-4">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Failed</p>
        <p class="text-2xl font-bold text-red-600 mt-1">{{ $stats['failed'] }}</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-md p-4">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Queued</p>
        <p class="text-2xl font-bold text-yellow-600 mt-1">{{ $stats['queued'] }}</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-md p-4">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['total'] }}</p>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="flex flex-wrap gap-3 mb-4 items-end">
    <div>
        <label class="block text-xs font-semibold text-slate-500 mb-1">Status</label>
        <select name="status" class="rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20 w-32">
            <option value="">All</option>
            @foreach(\App\Modules\Notifications\Enums\NotificationStatus::cases() as $s)
                <option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-semibold text-slate-500 mb-1">Channel</label>
        <select name="channel" class="rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20 w-32">
            <option value="">All</option>
            @foreach(\App\Modules\Notifications\Enums\NotificationChannel::cases() as $c)
                <option value="{{ $c->value }}" {{ request('channel') === $c->value ? 'selected' : '' }}>{{ $c->icon() }} {{ $c->label() }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-semibold text-slate-500 mb-1">Event</label>
        <select name="event" class="rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20 w-40">
            <option value="">All Events</option>
            @foreach(\App\Modules\Notifications\Enums\NotificationEvent::cases() as $e)
                <option value="{{ $e->value }}" {{ request('event') === $e->value ? 'selected' : '' }}>{{ $e->label() }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-semibold text-slate-500 mb-1">Search</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Recipient, Order, ID..." class="rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20 w-48">
    </div>
    <button type="submit" class="btn-primary">Filter</button>
    @if(request()->hasAny(['status', 'channel', 'event', 'search']))
        <a href="{{ route('admin.notifications.logs.index') }}" class="px-4 py-2 text-sm text-slate-500 hover:text-slate-900">Clear</a>
    @endif
</form>

{{-- Logs Table --}}
<div class="bg-white border border-slate-200 rounded-md shadow-sm overflow-hidden">
    <table class="nino-table">
        <thead class="bg-white-dim border-b border-slate-200">
            <tr>
                <th class="px-4 py-3 font-semibold text-slate-900">Time</th>
                <th class="px-4 py-3 font-semibold text-slate-900">Event</th>
                <th class="px-4 py-3 font-semibold text-slate-900">Channel</th>
                <th class="px-4 py-3 font-semibold text-slate-900">Recipient</th>
                <th class="px-4 py-3 font-semibold text-slate-900 text-center">Status</th>
                <th class="px-4 py-3 font-semibold text-slate-900">Attempts</th>
                <th class="px-4 py-3 font-semibold text-slate-900 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant">
            @forelse($logs as $log)
            <tr class="hover:bg-white-dim/50 transition-colors {{ $log->status === \App\Modules\Notifications\Enums\NotificationStatus::FAILED ? 'bg-red-50/30' : '' }}">
                <td class="px-4 py-3 text-xs text-slate-500">{{ $log->created_at->format('M d, H:i') }}</td>
                <td class="px-4 py-3 text-slate-900 text-xs">{{ $log->event->label() }}</td>
                <td class="px-4 py-3 text-slate-500 text-xs">{{ $log->channel->icon() }} {{ $log->channel->label() }}</td>
                <td class="px-4 py-3 text-slate-900 text-xs max-w-[180px] truncate">{{ $log->recipient }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $log->status->badgeColor() }}">
                        {{ $log->status->label() }}
                    </span>
                </td>
                <td class="px-4 py-3 text-xs text-slate-500">{{ $log->attempts }}/{{ $log->max_attempts }}</td>
                <td class="px-4 py-3 text-right space-x-2">
                    <a href="{{ route('admin.notifications.logs.show', $log) }}" class="text-slate-900 hover:text-slate-900-dim text-sm font-medium">View</a>
                    @if($log->canRetry())
                    <form action="{{ route('admin.notifications.logs.retry', $log) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-orange-600 hover:text-orange-700 text-sm font-medium">Retry</button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="p-8 text-center text-slate-500">
                        <div class="bg-slate-50 border border-dashed border-slate-300 rounded-md py-6 text-[11px] uppercase tracking-widest font-bold">
                            No notification logs yet.
                        </div>
                    </td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $logs->links() }}</div>
@endsection
