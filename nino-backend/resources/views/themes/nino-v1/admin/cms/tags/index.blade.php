@extends('admin.layouts.app')
@section('title', 'Blog Tags')
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Blog Tags</h1>
    <p class="text-sm text-slate-500 mt-1">Manage tags for blog posts.</p>
</div>
@if(session('success'))<div class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm border border-green-200">{{ session('success') }}</div>@endif

{{-- Create Tag --}}
<form method="POST" action="{{ route('admin.cms.tags.store') }}" class="flex gap-3 items-end mb-4">
    @csrf
    <div>
        <label class="block text-xs font-semibold text-slate-500 mb-1">Tag Name</label>
        <input type="text" name="name" required class="rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20 w-52" placeholder="New tag...">
    </div>
    <button type="submit" class="btn-primary">Add Tag</button>
</form>

<div class="bg-white border border-slate-200 rounded-md shadow-sm overflow-hidden">
    <table class="nino-table">
        <thead class="bg-white-dim border-b border-slate-200">
            <tr>
                <th class="px-4 py-3 font-semibold text-slate-900">Name</th>
                <th class="px-4 py-3 font-semibold text-slate-900">Slug</th>
                <th class="px-4 py-3 font-semibold text-slate-900 text-center">Posts</th>
                <th class="px-4 py-3 font-semibold text-slate-900 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant">
            @forelse($tags as $tag)
            <tr class="hover:bg-white-dim/50 transition-colors">
                <td class="px-4 py-3 font-medium text-slate-900">{{ $tag->name }}</td>
                <td class="px-4 py-3 text-slate-500 text-xs font-mono">{{ $tag->slug }}</td>
                <td class="px-4 py-3 text-center text-slate-900">{{ $tag->posts_count }}</td>
                <td class="px-4 py-3 text-right">
                    <form action="{{ route('admin.cms.tags.destroy', $tag) }}" method="POST" class="inline" onsubmit="return confirm('Delete this tag?')">@csrf @method('DELETE')<button type="submit" class="text-red-600 hover:text-red-700 text-sm font-medium">Delete</button></form>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="p-8 text-center text-slate-500">
                        <div class="bg-slate-50 border border-dashed border-slate-300 rounded-md py-6 text-[11px] uppercase tracking-widest font-bold">
                            No tags yet.
                        </div>
                    </td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $tags->links() }}</div>
@endsection
