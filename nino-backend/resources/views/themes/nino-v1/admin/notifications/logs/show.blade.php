@extends('admin.layouts.app')

@section('title', 'Notification Log #' . $log->id)

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.notifications.logs.index') }}" class="text-sm text-slate-900 hover:text-slate-900-dim">← Back to Logs</a>
    <div class="flex items-center gap-3 mt-2">
        <h1 class="text-2xl font-bold text-slate-900">Log #{{ $log->id }}</h1>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $log->status->badgeColor() }}">{{ $log->status->label() }}</span>
    </div>
</div>

@if(session('success'))
    <div class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm border border-green-200">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        {{-- Metadata --}}
        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-5">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-3">Details</h2>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div><span class="text-slate-500">Event:</span> <span class="text-slate-900 ml-1">{{ $log->event->label() }}</span></div>
                <div><span class="text-slate-500">Channel:</span> <span class="text-slate-900 ml-1">{{ $log->channel->icon() }} {{ $log->channel->label() }}</span></div>
                <div><span class="text-slate-500">Recipient:</span> <span class="text-slate-900 ml-1">{{ $log->recipient }}</span></div>
                <div><span class="text-slate-500">Order:</span> <span class="text-slate-900 ml-1">{{ $log->order_reference ?? '—' }}</span></div>
                <div><span class="text-slate-500">Template:</span> <span class="text-slate-900 ml-1">{{ $log->template?->name ?? '—' }}</span></div>
                <div><span class="text-slate-500">External ID:</span> <span class="text-slate-900 font-mono text-xs ml-1">{{ $log->external_id ?? '—' }}</span></div>
                <div><span class="text-slate-500">Attempts:</span> <span class="text-slate-900 ml-1">{{ $log->attempts }}/{{ $log->max_attempts }}</span></div>
                <div><span class="text-slate-500">Sent At:</span> <span class="text-slate-900 ml-1">{{ $log->sent_at?->format('M d, Y H:i:s') ?? '—' }}</span></div>
            </div>
        </div>

        {{-- Subject --}}
        @if($log->subject)
        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-5">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-2">Subject</h2>
            <p class="text-sm text-slate-900">{{ $log->subject }}</p>
        </div>
        @endif

        {{-- Body --}}
        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-5">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-2">Message Body</h2>
            <div class="bg-background rounded-lg p-4 text-sm text-slate-900 whitespace-pre-wrap max-h-96 overflow-y-auto">{{ $log->body }}</div>
        </div>

        {{-- Error --}}
        @if($log->error_message)
        <div class="bg-red-50 border border-red-200 rounded-md shadow-sm p-5">
            <h2 class="text-sm font-bold text-red-800 uppercase tracking-wider mb-2">Error</h2>
            <p class="text-sm text-red-700 font-mono">{{ $log->error_message }}</p>
        </div>
        @endif
    </div>

    {{-- Right sidebar: Variables + Actions --}}
    <div class="space-y-6">
        @if($log->canRetry())
        <form action="{{ route('admin.notifications.logs.retry', $log) }}" method="POST" class="bg-white border border-slate-200 rounded-md shadow-sm p-5">
            @csrf
            <button type="submit" class="w-full px-5 py-2 text-sm font-medium bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors shadow-sm">Retry Notification</button>
        </form>
        @endif

        @if($log->variables)
        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-5">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-3">Template Variables</h2>
            <div class="space-y-1 max-h-60 overflow-y-auto">
                @foreach($log->variables as $key => $value)
                <div class="flex justify-between text-xs py-1 border-b border-slate-200/50">
                    <span class="font-mono text-slate-500">{{ $key }}</span>
                    <span class="text-slate-900 max-w-[120px] truncate">{{ $value }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-5">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-3">Timestamps</h2>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between"><span class="text-slate-500">Created:</span><span class="text-slate-900">{{ $log->created_at->format('M d, H:i:s') }}</span></div>
                @if($log->sent_at)<div class="flex justify-between"><span class="text-slate-500">Sent:</span><span class="text-slate-900 text-green-700">{{ $log->sent_at->format('M d, H:i:s') }}</span></div>@endif
                @if($log->next_retry_at)<div class="flex justify-between"><span class="text-slate-500">Next Retry:</span><span class="text-slate-900 text-orange-700">{{ $log->next_retry_at->format('M d, H:i:s') }}</span></div>@endif
            </div>
        </div>
    </div>
</div>
@endsection
