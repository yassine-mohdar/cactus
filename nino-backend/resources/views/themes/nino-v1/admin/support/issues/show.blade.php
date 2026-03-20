@extends('admin.layouts.app')
@section('title', 'Issue ' . $issue->reference)
@section('content')
<div class="mb-6">
    <a href="{{ route('admin.support.issues.index') }}" class="text-sm text-slate-900 hover:text-slate-900-dim">← Back to Issues</a>
    <div class="flex items-center gap-3 mt-2">
        <h1 class="text-2xl font-bold text-slate-900 font-mono">{{ $issue->reference }}</h1>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $issue->status->badgeColor() }}">{{ $issue->status->label() }}</span>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $issue->priority->badgeColor() }}">{{ $issue->priority->label() }}</span>
    </div>
</div>
@if(session('success'))<div class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm border border-green-200">{{ session('success') }}</div>@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Left: Issue Details + Timeline --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- Issue Info --}}
        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
            <h2 class="text-lg font-bold text-slate-900">{{ $issue->subject }}</h2>
            @if($issue->description)<p class="text-sm text-slate-500 mt-2 whitespace-pre-line">{{ $issue->description }}</p>@endif
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mt-4 pt-4 border-t border-slate-200">
                <div><p class="text-xs text-slate-500">Type</p><p class="mt-0.5">{{ $issue->type->icon() }} {{ $issue->type->label() }}</p></div>
                <div><p class="text-xs text-slate-500">Customer</p><p class="mt-0.5">{{ $issue->customer_name ?? '—' }}</p></div>
                <div><p class="text-xs text-slate-500">Email</p><p class="mt-0.5 text-xs">{{ $issue->customer_email ?? '—' }}</p></div>
                <div><p class="text-xs text-slate-500">Order</p><p class="mt-0.5 font-mono text-xs">{{ $issue->order?->reference_number ?? '—' }}</p></div>
            </div>
        </div>

        {{-- Status Actions --}}
        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Actions</h2>
            <div class="flex flex-wrap gap-2">
                @if($issue->status === \App\Modules\Support\Enums\IssueStatus::OPEN)
                    <form action="{{ route('admin.support.issues.progress', $issue) }}" method="POST">@csrf<button type="submit" class="px-4 py-2 text-sm font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Start Working</button></form>
                    <form action="{{ route('admin.support.issues.resolve', $issue) }}" method="POST">@csrf<button type="submit" class="px-4 py-2 text-sm font-medium bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">Resolve</button></form>
                @elseif($issue->status === \App\Modules\Support\Enums\IssueStatus::IN_PROGRESS || $issue->status === \App\Modules\Support\Enums\IssueStatus::WAITING_CUSTOMER)
                    <form action="{{ route('admin.support.issues.resolve', $issue) }}" method="POST">@csrf<button type="submit" class="px-4 py-2 text-sm font-medium bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">Resolve</button></form>
                @elseif($issue->status === \App\Modules\Support\Enums\IssueStatus::RESOLVED)
                    <form action="{{ route('admin.support.issues.close', $issue) }}" method="POST">@csrf<button type="submit" class="px-4 py-2 text-sm font-medium bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">Close</button></form>
                    <form action="{{ route('admin.support.issues.reopen', $issue) }}" method="POST">@csrf<button type="submit" class="px-4 py-2 text-sm font-medium bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">Reopen</button></form>
                @elseif($issue->status === \App\Modules\Support\Enums\IssueStatus::CLOSED)
                    <form action="{{ route('admin.support.issues.reopen', $issue) }}" method="POST">@csrf<button type="submit" class="px-4 py-2 text-sm font-medium bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">Reopen</button></form>
                @endif
            </div>
        </div>

        {{-- Activity Timeline --}}
        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Timeline</h2>
            @if($timeline->count())
            <div class="space-y-4">
                @foreach($timeline as $event)
                <div class="flex gap-3">
                    <div class="flex-shrink-0 mt-1 w-2 h-2 rounded-full {{ $event->action === 'status_changed' ? 'bg-blue-500' : ($event->action === 'note_added' ? 'bg-green-500' : 'bg-slate-900') }}"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-slate-900">{{ $event->description }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $event->performer?->first_name ?? 'System' }} · {{ $event->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-slate-500">No activity yet.</p>
            @endif
        </div>
    </div>

    {{-- Right: Assignment + Notes --}}
    <div class="space-y-6">
        {{-- Info Card --}}
        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Details</h2>
            <div class="space-y-3 text-sm">
                <div><p class="text-xs text-slate-500">Assignee</p><p class="text-slate-900 mt-0.5">{{ $issue->assignee?->first_name ?? 'Unassigned' }}</p></div>
                <div><p class="text-xs text-slate-500">Created By</p><p class="text-slate-900 mt-0.5">{{ $issue->creator?->first_name ?? '—' }}</p></div>
                <div><p class="text-xs text-slate-500">Created</p><p class="text-slate-900 mt-0.5">{{ $issue->created_at->format('M d, Y H:i') }}</p></div>
                @if($issue->resolved_at)<div><p class="text-xs text-slate-500">Resolved</p><p class="text-green-600 mt-0.5">{{ $issue->resolved_at->format('M d, Y H:i') }}</p></div>@endif
                @if($issue->closed_at)<div><p class="text-xs text-slate-500">Closed</p><p class="text-slate-900 mt-0.5">{{ $issue->closed_at->format('M d, Y H:i') }}</p></div>@endif
            </div>
        </div>

        {{-- Add Note --}}
        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Add Note</h2>
            <form action="{{ route('admin.support.issues.note', $issue) }}" method="POST">
                @csrf
                <textarea name="content" rows="3" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Add internal note..." required></textarea>
                <button type="submit" class="mt-2 w-full px-4 py-2 text-sm font-medium bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition-colors">Add Note</button>
            </form>
        </div>

        {{-- Notes List --}}
        @if($notes->count())
        <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Notes ({{ $notes->count() }})</h2>
            <div class="space-y-3">
                @foreach($notes as $note)
                <div class="p-3 bg-white-dim rounded-lg text-sm {{ $note->is_pinned ? 'border-l-4 border-l-slate-800' : '' }}">
                    <p class="text-slate-900 whitespace-pre-line">{{ $note->content }}</p>
                    <p class="text-xs text-slate-500 mt-2">{{ $note->author?->first_name ?? 'Unknown' }} · {{ $note->created_at->diffForHumans() }}</p>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
