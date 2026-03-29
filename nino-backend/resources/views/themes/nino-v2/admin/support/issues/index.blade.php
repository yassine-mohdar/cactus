@extends('admin.layouts.app')
@section('title', 'Support Issues')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Support Issues</h1>
        <p class="page-subtitle">Manage customer issues with clearer filters, clearer ownership, and explicit row actions.</p>
    </div>
    <a href="{{ route('admin.support.issues.create') }}" class="btn-primary gap-2">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path></svg>
        New Issue
    </a>
</div>
@if(session('success'))
    <x-nino.inline-alert tone="success" title="Issue queue updated" class="mb-4">
        {{ session('success') }}
    </x-nino.inline-alert>
@endif

{{-- Stats --}}
<div class="stats-grid mb-6">
    <div class="stat-card">
        <p class="stat-label">Open</p>
        <p class="stat-value text-red-600">{{ $stats['open'] }}</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Active</p>
        <p class="stat-value text-blue-600">{{ $stats['active'] }}</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Resolved Today</p>
        <p class="stat-value text-green-600">{{ $stats['resolved_today'] }}</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Total</p>
        <p class="stat-value">{{ $stats['total'] }}</p>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="filter-toolbar mb-6">
    <div class="filter-grid xl:grid-cols-6">
    <div class="filter-field xl:col-span-2"><label class="filter-label" for="issue-search">Search</label><input id="issue-search" type="text" name="search" value="{{ request('search') }}" placeholder="Reference, subject, customer..." class="input-field"></div>
    <div class="filter-field"><label class="filter-label" for="issue-status">Status</label>
        <select id="issue-status" name="status" class="input-field">
            <option value="">All</option>
            @foreach(\App\Modules\Support\Enums\IssueStatus::cases() as $s)<option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>@endforeach
        </select>
    </div>
    <div class="filter-field"><label class="filter-label" for="issue-type">Type</label>
        <select id="issue-type" name="type" class="input-field">
            <option value="">All</option>
            @foreach(\App\Modules\Support\Enums\IssueType::cases() as $t)<option value="{{ $t->value }}" {{ request('type') === $t->value ? 'selected' : '' }}>{{ $t->label() }}</option>@endforeach
        </select>
    </div>
    <div class="filter-field"><label class="filter-label" for="issue-priority">Priority</label>
        <select id="issue-priority" name="priority" class="input-field">
            <option value="">All</option>
            @foreach(\App\Modules\Support\Enums\IssuePriority::cases() as $p)<option value="{{ $p->value }}" {{ request('priority') === $p->value ? 'selected' : '' }}>{{ $p->label() }}</option>@endforeach
        </select>
    </div>
    <div class="filter-field"><label class="filter-label" for="issue-assignee">Assignee</label>
        <select id="issue-assignee" name="assigned_to" class="input-field">
            <option value="">All</option>
            @foreach($staff as $u)<option value="{{ $u->id }}" {{ request('assigned_to') == $u->id ? 'selected' : '' }}>{{ $u->first_name }}</option>@endforeach
        </select>
    </div>
</div>
<div class="mt-3 flex items-center gap-3">
    <x-admin.button type="submit" variant="primary">Apply Filters</x-admin.button>
    @if(request()->hasAny(['search','status','type','priority','assigned_to']))<a href="{{ route('admin.support.issues.index') }}" class="btn-secondary">Clear</a>@endif
</div>
</form>

{{-- Table --}}
<div class="datatable-shell">
    <div class="datatable-header">
        <div>
            <h2 class="datatable-title">Issue Queue</h2>
            <p class="datatable-subtitle">Issues are now navigated via clear links rather than hidden row clicks.</p>
        </div>
        <span class="datatable-meta">{{ number_format($issues->total()) }} issues</span>
    </div>

    <div class="datatable-scroll">
    <table class="nino-table">
        <thead>
            <tr>
                <th>Reference</th>
                <th>Subject</th>
                <th class="text-center">Type</th>
                <th class="text-center">Priority</th>
                <th class="text-center">Status</th>
                <th>Assignee</th>
                <th>Date</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($issues as $issue)
            <tr>
                <td class="px-4 py-3 font-mono text-xs font-medium text-[#1E2B27]"><a href="{{ route('admin.support.issues.show', $issue) }}" class="table-link table-mono">{{ $issue->reference }}</a></td>
                <td class="px-4 py-3 text-sm text-[#1E2B27]">
                    <a href="{{ route('admin.support.issues.show', $issue) }}" class="table-link">{{ $issue->subject }}</a>
                    @if($issue->customer_name)<span class="block text-xs text-[#61706B]">{{ $issue->customer_name }}</span>@endif
                </td>
                <td class="px-4 py-3 text-center text-xs">{{ $issue->type->icon() }} {{ $issue->type->label() }}</td>
                <td class="px-4 py-3 text-center"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $issue->priority->badgeColor() }}">{{ $issue->priority->label() }}</span></td>
                <td class="px-4 py-3 text-center"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $issue->status->badgeColor() }}">{{ $issue->status->label() }}</span></td>
                <td class="px-4 py-3 text-sm text-[#61706B]">{{ $issue->assignee?->first_name ?? 'Unassigned' }}</td>
                <td class="px-4 py-3 text-xs text-[#61706B]">{{ $issue->created_at->format('M d, H:i') }}</td>
                <td class="px-4 py-3 text-right"><div class="table-actions"><a href="{{ route('admin.support.issues.show', $issue) }}" class="table-action-link">Open</a></div></td>
            </tr>
            @empty
            <tr><td colspan="8" class="datatable-empty">
                        <div class="datatable-empty-panel">
                            <p class="text-sm font-semibold text-[#1E2B27]">No issues found.</p>
                            <p class="text-sm text-[#61706B]">Try adjusting the filters or create a new issue.</p>
                        </div>
                    </td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
<div class="mt-4">{{ $issues->links() }}</div>
@endsection
