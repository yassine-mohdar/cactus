@extends('admin.layouts.app')
@section('title', 'URL Redirects')
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">URL Redirects</h1>
    <p class="text-sm text-slate-500 mt-1">Manage 301/302 redirects for SEO and broken links.</p>
</div>
@if(session('success'))<div class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm border border-green-200">{{ session('success') }}</div>@endif

{{-- Create Redirect --}}
<form method="POST" action="{{ route('admin.cms.redirects.store') }}" class="bg-white border border-slate-200 rounded-md shadow-sm p-6 mb-6">
    @csrf
    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">New Redirect</h2>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <div><label class="block text-xs font-semibold text-slate-500 mb-1">Source Path</label><input type="text" name="source_path" required class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20 font-mono" placeholder="old-page/url"></div>
        <div><label class="block text-xs font-semibold text-slate-500 mb-1">Target Path</label><input type="text" name="target_path" required class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20 font-mono" placeholder="new-page/url"></div>
        <div><label class="block text-xs font-semibold text-slate-500 mb-1">Type</label>
            <select name="status_code" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                <option value="301">301 Permanent</option>
                <option value="302">302 Temporary</option>
            </select>
        </div>
        <div class="flex items-center gap-3">
            <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-200 text-slate-900 focus:ring-slate-900/20"><span class="text-sm text-slate-900">Active</span></label>
            <button type="submit" class="btn-primary">Add</button>
        </div>
    </div>
</form>

{{-- Search --}}
<form method="GET" class="flex gap-3 mb-4 items-end">
    <div><label class="block text-xs font-semibold text-slate-500 mb-1">Search</label><input type="text" name="search" value="{{ request('search') }}" placeholder="Source or target..." class="rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20 w-52"></div>
    <button type="submit" class="btn-primary">Search</button>
</form>

{{-- Redirects Table --}}
<div class="bg-white border border-slate-200 rounded-md shadow-sm overflow-hidden">
    <table class="nino-table">
        <thead class="bg-white-dim border-b border-slate-200">
            <tr>
                <th class="px-4 py-3 font-semibold text-slate-900">Source</th>
                <th class="px-4 py-3 font-semibold text-slate-900">→ Target</th>
                <th class="px-4 py-3 font-semibold text-slate-900 text-center">Code</th>
                <th class="px-4 py-3 font-semibold text-slate-900 text-center">Hits</th>
                <th class="px-4 py-3 font-semibold text-slate-900 text-center">Status</th>
                <th class="px-4 py-3 font-semibold text-slate-900 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant">
            @forelse($redirects as $r)
            <tr class="hover:bg-white-dim/50 transition-colors">
                <td class="px-4 py-3 font-mono text-xs text-slate-900">/{{ $r->source_path }}</td>
                <td class="px-4 py-3 font-mono text-xs text-slate-900">/{{ $r->target_path }}</td>
                <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded text-xs font-medium {{ $r->status_code === 301 ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800' }}">{{ $r->status_code }}</span></td>
                <td class="px-4 py-3 text-center text-slate-500 text-xs">{{ number_format($r->hit_count) }}</td>
                <td class="px-4 py-3 text-center"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $r->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">{{ $r->is_active ? 'Active' : 'Off' }}</span></td>
                <td class="px-4 py-3 text-right">
                    <form action="{{ route('admin.cms.redirects.destroy', $r) }}" method="POST" class="inline" onsubmit="return confirm('Delete this redirect?')">@csrf @method('DELETE')<button type="submit" class="text-red-600 hover:text-red-700 text-sm font-medium">Delete</button></form>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="p-8 text-center text-slate-500">
                        <div class="bg-slate-50 border border-dashed border-slate-300 rounded-md py-6 text-[11px] uppercase tracking-widest font-bold">
                            No redirects yet.
                        </div>
                    </td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $redirects->links() }}</div>
@endsection
