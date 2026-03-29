@extends('admin.layouts.app')

@section('title', 'Notification Templates')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Notification Templates</h1>
        <p class="text-sm text-slate-500 mt-1">Manage message templates for each event and channel.</p>
    </div>
    <a href="{{ route('admin.notifications.templates.create') }}" class="btn-primary">
        + New Template
    </a>
</div>

@if(session('success'))
    <div class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm border border-green-200">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm border border-red-200">{{ session('error') }}</div>
@endif

@forelse($grouped as $group => $groupTemplates)
<div class="mb-6">
    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-3 px-1">{{ $group }}</h2>
    <div class="bg-white border border-slate-200 rounded-md shadow-sm overflow-hidden">
        <table class="nino-table">
            <thead class="bg-white-dim border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 font-semibold text-slate-900">Event</th>
                    <th class="px-4 py-3 font-semibold text-slate-900">Channel</th>
                    <th class="px-4 py-3 font-semibold text-slate-900">Name</th>
                    <th class="px-4 py-3 font-semibold text-slate-900">Subject</th>
                    <th class="px-4 py-3 font-semibold text-slate-900 text-center">Status</th>
                    <th class="px-4 py-3 font-semibold text-slate-900 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                @foreach($groupTemplates as $template)
                <tr class="hover:bg-white-dim/50 transition-colors">
                    <td class="px-4 py-3 text-slate-900">{{ $template->event->label() }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1 text-slate-500">
                            {{ $template->channel->icon() }} {{ $template->channel->label() }}
                        </span>
                    </td>
                    <td class="px-4 py-3 font-medium text-slate-900">{{ $template->name }}</td>
                    <td class="px-4 py-3 text-slate-500 text-xs max-w-xs truncate">{{ $template->subject ?? '—' }}</td>
                    <td class="px-4 py-3 text-center">
                        <form action="{{ route('admin.notifications.templates.toggle', $template) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium cursor-pointer {{ $template->is_enabled ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }} transition-colors">
                                {{ $template->is_enabled ? 'Active' : 'Disabled' }}
                            </button>
                        </form>
                    </td>
                    <td class="px-4 py-3 text-right space-x-2">
                        <a href="{{ route('admin.notifications.templates.edit', $template) }}" class="text-slate-900 hover:text-slate-900-dim text-sm font-medium">Edit</a>
                        <form action="{{ route('admin.notifications.templates.destroy', $template) }}" method="POST" class="inline" onsubmit="return confirm('Delete this template?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-700 text-sm font-medium">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@empty
<div class="bg-white border border-slate-200 rounded-md shadow-sm p-8 text-center">
    <p class="text-slate-500">No templates yet. Create your first notification template to get started.</p>
</div>
@endforelse
@endsection
