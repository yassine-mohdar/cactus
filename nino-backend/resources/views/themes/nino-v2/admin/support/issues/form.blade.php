@extends('admin.layouts.app')
@section('title', isset($issue) ? 'Edit Issue' : 'New Issue')

@php
    $isEditing = isset($issue) && $issue;
@endphp

@section('header')
    <x-nino.page-header
        title="{{ $isEditing ? 'Edit '.$issue->reference : 'Create Support Issue' }}"
        subtitle="{{ $isEditing ? 'Update issue routing, priority, and customer context while preserving the existing activity history.' : 'Capture a new customer issue with clear routing, ownership, and contact context.' }}">
        <x-slot:actions>
            @if($isEditing)
                <x-nino.status-badge :tone="$issue->status->isActive() ? 'warning' : 'neutral'">{{ $issue->status->label() }}</x-nino.status-badge>
            @endif
            <x-nino.button href="{{ route('admin.support.issues.index') }}" variant="secondary" icon="arrow_back">Back to Issues</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
@if($errors->any())
    <x-nino.inline-alert tone="danger" title="Please fix the highlighted issue fields" class="mb-6">
        {{ collect($errors->all())->join(' ') }}
    </x-nino.inline-alert>
@endif

<form action="{{ $isEditing ? route('admin.support.issues.update', $issue) : route('admin.support.issues.store') }}" method="POST" class="form-layout">
    @csrf
    @if($isEditing) @method('PUT') @endif

    <div class="form-main">
        <x-nino.entity-form-section title="Issue Profile" subtitle="Document the customer problem clearly so the next operator can understand the context without opening the full thread.">
            <div class="space-y-4">
                <div>
                    <label class="filter-label" for="issue-subject">Subject</label>
                    <input id="issue-subject" type="text" name="subject" value="{{ old('subject', $issue->subject ?? '') }}" required class="input-field" placeholder="Customer cannot complete checkout">
                    @error('subject')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="filter-label" for="issue-description">Description</label>
                    <textarea id="issue-description" name="description" rows="6" class="input-field" placeholder="Describe the incident, customer expectation, and any manual steps already taken.">{{ old('description', $issue->description ?? '') }}</textarea>
                    @error('description')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </x-nino.entity-form-section>

        <x-nino.entity-form-section title="Customer Context" subtitle="Capture the essential customer details so support, finance, and operations can cross-reference the case quickly.">
            <div class="entity-section-grid">
                <div>
                    <label class="filter-label" for="issue-customer-name">Customer Name</label>
                    <input id="issue-customer-name" type="text" name="customer_name" value="{{ old('customer_name', $issue->customer_name ?? '') }}" class="input-field" placeholder="Customer full name">
                    @error('customer_name')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="filter-label" for="issue-customer-email">Customer Email</label>
                    <input id="issue-customer-email" type="email" name="customer_email" value="{{ old('customer_email', $issue->customer_email ?? '') }}" class="input-field" placeholder="customer@example.com">
                    @error('customer_email')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </x-nino.entity-form-section>
    </div>

    <div class="form-sidebar">
        <x-nino.entity-form-section title="Routing and Ownership" subtitle="Choose the queue, urgency, and assignee that should own the next action.">
            <div class="space-y-4">
                <div>
                    <label class="filter-label" for="issue-type">Type</label>
                    <select id="issue-type" name="type" required class="input-field">
                        @foreach(\App\Modules\Support\Enums\IssueType::cases() as $t)
                            <option value="{{ $t->value }}" {{ old('type', $issue->type->value ?? '') === $t->value ? 'selected' : '' }}>{{ $t->icon() }} {{ $t->label() }}</option>
                        @endforeach
                    </select>
                    @error('type')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="filter-label" for="issue-priority">Priority</label>
                    <select id="issue-priority" name="priority" required class="input-field">
                        @foreach(\App\Modules\Support\Enums\IssuePriority::cases() as $p)
                            <option value="{{ $p->value }}" {{ old('priority', $issue->priority->value ?? 'medium') === $p->value ? 'selected' : '' }}>{{ $p->label() }}</option>
                        @endforeach
                    </select>
                    @error('priority')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="filter-label" for="issue-assigned-to">Assign To</label>
                    <select id="issue-assigned-to" name="assigned_to" class="input-field">
                        <option value="">Unassigned</option>
                        @foreach($staff as $u)
                            <option value="{{ $u->id }}" {{ old('assigned_to', $issue->assigned_to ?? '') == $u->id ? 'selected' : '' }}>{{ trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: $u->name }}</option>
                        @endforeach
                    </select>
                    @error('assigned_to')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>

                @if($isEditing)
                    <x-nino.entity-detail-grid>
                        <div>
                            <p class="detail-kicker">Reference</p>
                            <p class="detail-value-mono">{{ $issue->reference }}</p>
                        </div>
                        <div>
                            <p class="detail-kicker">Current Status</p>
                            <p class="detail-value">{{ $issue->status->label() }}</p>
                        </div>
                    </x-nino.entity-detail-grid>
                @endif

                <div class="form-note">
                    {{ $isEditing ? 'Route changes should keep the issue moving without losing ownership context for the next agent.' : 'A ticket reference is generated automatically after creation. You can add internal notes and workflow actions from the issue detail screen.' }}
                </div>
            </div>

            <x-slot:footer>
                <div class="form-actions">
                    <x-nino.button href="{{ route('admin.support.issues.index') }}" variant="outline">Cancel</x-nino.button>
                    <x-nino.button type="submit" variant="primary">{{ $isEditing ? 'Update Issue' : 'Create Issue' }}</x-nino.button>
                </div>
            </x-slot:footer>
        </x-nino.entity-form-section>
    </div>
</form>
@endsection
