@extends('admin.layouts.app')
@section('title', isset($issue) ? 'Edit Issue' : 'New Issue')
@section('content')
<div class="mb-6">
    <a href="{{ route('admin.support.issues.index') }}" class="text-sm text-slate-900 hover:text-slate-900-dim">← Back to Issues</a>
    <h1 class="text-2xl font-bold text-slate-900 mt-2">{{ isset($issue) ? 'Edit Issue' : 'Create Issue' }}</h1>
</div>

<form action="{{ isset($issue) ? route('admin.support.issues.update', $issue) : route('admin.support.issues.store') }}" method="POST">
    @csrf
    @if(isset($issue)) @method('PUT') @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Issue Details</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-900 mb-1">Subject *</label>
                        <input type="text" name="subject" value="{{ old('subject', $issue->subject ?? '') }}" required class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                        @error('subject')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-900 mb-1">Description</label>
                        <textarea name="description" rows="5" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">{{ old('description', $issue->description ?? '') }}</textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-1">Customer Name</label>
                            <input type="text" name="customer_name" value="{{ old('customer_name', $issue->customer_name ?? '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-1">Customer Email</label>
                            <input type="email" name="customer_email" value="{{ old('customer_email', $issue->customer_email ?? '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Classification</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-900 mb-1">Type *</label>
                        <select name="type" required class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                            @foreach(\App\Modules\Support\Enums\IssueType::cases() as $t)
                            <option value="{{ $t->value }}" {{ old('type', $issue->type->value ?? '') === $t->value ? 'selected' : '' }}>{{ $t->icon() }} {{ $t->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-900 mb-1">Priority *</label>
                        <select name="priority" required class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                            @foreach(\App\Modules\Support\Enums\IssuePriority::cases() as $p)
                            <option value="{{ $p->value }}" {{ old('priority', $issue->priority->value ?? 'medium') === $p->value ? 'selected' : '' }}>{{ $p->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-900 mb-1">Assign To</label>
                        <select name="assigned_to" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                            <option value="">Unassigned</option>
                            @foreach($staff as $u)
                            <option value="{{ $u->id }}" {{ old('assigned_to', $issue->assigned_to ?? '') == $u->id ? 'selected' : '' }}>{{ $u->first_name }} {{ $u->last_name ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full px-4 py-3 text-sm font-medium bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition-colors">
                {{ isset($issue) ? 'Update Issue' : 'Create Issue' }}
            </button>
        </div>
    </div>
</form>
@endsection
