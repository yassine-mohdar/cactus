@extends('admin.layouts.app')

@section('title', 'Issue ' . $issue->reference)

@section('header')
    <div class="page-header">
        <div>
            <a href="{{ route('admin.support.issues.index') }}" class="inline-flex items-center gap-1 text-xs font-bold uppercase tracking-[0.18em] text-[#617169] transition-colors hover:text-[#17302A]">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                Back to Issues
            </a>
            <h1 class="page-title mt-3 font-mono">{{ $issue->reference }}</h1>
            <p class="page-subtitle">{{ $issue->subject }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] {{ $issue->status->badgeColor() }}">
                {{ $issue->status->label() }}
            </span>
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] {{ $issue->priority->badgeColor() }}">
                {{ $issue->priority->label() }}
            </span>
        </div>
    </div>
@endsection

@section('content')
    @if(session('success'))
        <div class="mb-6 rounded-[1.35rem] border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm font-medium text-emerald-800 shadow-[0_18px_42px_-30px_rgba(8,127,91,0.45)] backdrop-blur">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.7fr)_minmax(320px,0.9fr)]">
        <div class="space-y-6">
            <x-admin.card title="Issue summary">
                <div class="space-y-5">
                    @if($issue->description)
                        <p class="text-sm leading-7 text-[#5D6F66] whitespace-pre-line">{{ $issue->description }}</p>
                    @endif

                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="metric-tile">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#6E7D75]">Type</p>
                            <p class="mt-3 text-sm font-semibold text-[#17302A]">{{ $issue->type->icon() }} {{ $issue->type->label() }}</p>
                        </div>
                        <div class="metric-tile">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#6E7D75]">Customer</p>
                            <p class="mt-3 text-sm font-semibold text-[#17302A]">{{ $issue->customer_name ?? 'Unknown customer' }}</p>
                        </div>
                        <div class="metric-tile">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#6E7D75]">Email</p>
                            <p class="mt-3 text-sm font-semibold text-[#17302A] break-all">{{ $issue->customer_email ?? 'No email captured' }}</p>
                        </div>
                        <div class="metric-tile">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#6E7D75]">Order</p>
                            <p class="mt-3 text-sm font-semibold text-[#17302A] font-mono">{{ $issue->order?->reference_number ?? 'No linked order' }}</p>
                        </div>
                    </div>
                </div>
            </x-admin.card>

            <x-admin.card title="Workflow actions">
                <div class="space-y-4">
                    <p class="form-copy">Advance the issue through the support workflow while keeping the current status and note history visible to the team.</p>

                    <div class="flex flex-wrap gap-3">
                        @if($issue->status === \App\Modules\Support\Enums\IssueStatus::OPEN)
                            <form action="{{ route('admin.support.issues.progress', $issue) }}" method="POST">
                                @csrf
                                <x-admin.button type="submit" variant="primary">Start Working</x-admin.button>
                            </form>
                            <form action="{{ route('admin.support.issues.resolve', $issue) }}" method="POST">
                                @csrf
                                <x-admin.button type="submit" variant="secondary">Resolve</x-admin.button>
                            </form>
                        @elseif($issue->status === \App\Modules\Support\Enums\IssueStatus::IN_PROGRESS || $issue->status === \App\Modules\Support\Enums\IssueStatus::WAITING_CUSTOMER)
                            <form action="{{ route('admin.support.issues.resolve', $issue) }}" method="POST">
                                @csrf
                                <x-admin.button type="submit" variant="primary">Resolve</x-admin.button>
                            </form>
                        @elseif($issue->status === \App\Modules\Support\Enums\IssueStatus::RESOLVED)
                            <form action="{{ route('admin.support.issues.close', $issue) }}" method="POST">
                                @csrf
                                <x-admin.button type="submit" variant="primary">Close Issue</x-admin.button>
                            </form>
                            <form action="{{ route('admin.support.issues.reopen', $issue) }}" method="POST">
                                @csrf
                                <x-admin.button type="submit" variant="outline">Reopen</x-admin.button>
                            </form>
                        @elseif($issue->status === \App\Modules\Support\Enums\IssueStatus::CLOSED)
                            <form action="{{ route('admin.support.issues.reopen', $issue) }}" method="POST">
                                @csrf
                                <x-admin.button type="submit" variant="primary">Reopen</x-admin.button>
                            </form>
                        @endif
                    </div>
                </div>
            </x-admin.card>

            <x-admin.card title="Timeline">
                <div class="space-y-4">
                    @if($timeline->count())
                        @foreach($timeline as $event)
                            <div class="flex gap-4">
                                <div class="mt-2 h-2.5 w-2.5 rounded-full {{ $event->action === 'status_changed' ? 'bg-[#245848]' : ($event->action === 'note_added' ? 'bg-[#B97A22]' : 'bg-[#17302A]') }}"></div>
                                <div class="min-w-0 flex-1 rounded-[1rem] border border-[rgba(145,133,109,0.18)] bg-[#FCFBF8]/85 px-4 py-3">
                                    <p class="text-sm font-medium text-[#17302A]">{{ $event->description }}</p>
                                    <p class="mt-1 text-xs uppercase tracking-[0.18em] text-[#6E7D75]">{{ $event->performer?->first_name ?? 'System' }} · {{ $event->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-state">No activity has been recorded on this issue yet.</div>
                    @endif
                </div>
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card title="Issue details">
                <div class="meta-list">
                    <div class="meta-row">
                        <span class="meta-label">Assignee</span>
                        <span class="meta-value">{{ $issue->assignee?->first_name ?? 'Unassigned' }}</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">Created by</span>
                        <span class="meta-value">{{ $issue->creator?->first_name ?? 'System' }}</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">Created</span>
                        <span class="meta-value">{{ $issue->created_at->format('M d, Y H:i') }}</span>
                    </div>
                    @if($issue->resolved_at)
                        <div class="meta-row">
                            <span class="meta-label">Resolved</span>
                            <span class="meta-value">{{ $issue->resolved_at->format('M d, Y H:i') }}</span>
                        </div>
                    @endif
                    @if($issue->closed_at)
                        <div class="meta-row">
                            <span class="meta-label">Closed</span>
                            <span class="meta-value">{{ $issue->closed_at->format('M d, Y H:i') }}</span>
                        </div>
                    @endif
                </div>
            </x-admin.card>

            <x-admin.card title="Add internal note">
                <form action="{{ route('admin.support.issues.note', $issue) }}" method="POST" class="space-y-4">
                    @csrf
                    <x-admin.textarea name="content" label="Note" rows="4" placeholder="Add context for the next agent handling this issue." required>{{ old('content') }}</x-admin.textarea>
                    <x-admin.button type="submit" variant="primary" class="w-full justify-center">Add Note</x-admin.button>
                </form>
            </x-admin.card>

            @if($notes->count())
                <x-admin.card title="Internal notes">
                    <x-slot:header>
                        <span class="datatable-meta">{{ $notes->count() }} note{{ $notes->count() === 1 ? '' : 's' }}</span>
                    </x-slot:header>

                    <div class="space-y-3">
                        @foreach($notes as $note)
                            <div class="rounded-[1rem] border border-[rgba(145,133,109,0.18)] bg-[#FCFBF8]/85 px-4 py-3 {{ $note->is_pinned ? 'ring-2 ring-[#245848]/18' : '' }}">
                                <p class="text-sm leading-6 text-[#17302A] whitespace-pre-line">{{ $note->content }}</p>
                                <p class="mt-2 text-xs uppercase tracking-[0.18em] text-[#6E7D75]">{{ $note->author?->first_name ?? 'Unknown' }} · {{ $note->created_at->diffForHumans() }}</p>
                            </div>
                        @endforeach
                    </div>
                </x-admin.card>
            @endif
        </div>
    </div>
@endsection
