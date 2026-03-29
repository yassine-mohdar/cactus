@extends('admin.layouts.app')
@section('title', 'Blog Posts')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Blog Posts</h1>
        <p class="text-sm text-slate-500 mt-1">Create, edit, and publish articles.</p>
    </div>
    <a href="{{ route('admin.cms.posts.create') }}" class="btn-primary">+ New Post</a>
</div>
@if(session('success'))<div class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm border border-green-200">{{ session('success') }}</div>@endif

{{-- Stats --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white border border-slate-200 rounded-md p-4">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['total'] }}</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-md p-4">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Published</p>
        <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['published'] }}</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-md p-4">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Drafts</p>
        <p class="text-2xl font-bold text-yellow-600 mt-1">{{ $stats['draft'] }}</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-md p-4">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Scheduled</p>
        <p class="text-2xl font-bold text-blue-600 mt-1">{{ $stats['scheduled'] }}</p>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="flex flex-wrap gap-3 mb-4 items-end">
    <div><label class="block text-xs font-semibold text-slate-500 mb-1">Search</label><input type="text" name="search" value="{{ request('search') }}" placeholder="Title or slug..." class="rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20 w-44"></div>
    <div><label class="block text-xs font-semibold text-slate-500 mb-1">Status</label>
        <select name="status" class="rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20 w-32">
            <option value="">All</option>
            @foreach(\App\Modules\Cms\Enums\PostStatus::cases() as $s)<option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>@endforeach
        </select>
    </div>
    <div><label class="block text-xs font-semibold text-slate-500 mb-1">Category</label>
        <select name="category" class="rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20 w-36">
            <option value="">All</option>
            @foreach($categories as $c)<option value="{{ $c->id }}" {{ request('category') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach
        </select>
    </div>
    <button type="submit" class="btn-primary">Filter</button>
    @if(request()->hasAny(['search','status','category']))<a href="{{ route('admin.cms.posts.index') }}" class="px-4 py-2 text-sm text-slate-500 hover:text-slate-900">Clear</a>@endif
</form>

{{-- Posts Table --}}
<div class="bg-white border border-slate-200 rounded-md shadow-sm overflow-hidden">
    <table class="nino-table">
        <thead class="bg-white-dim border-b border-slate-200">
            <tr>
                <th class="px-4 py-3 font-semibold text-slate-900">Title</th>
                <th class="px-4 py-3 font-semibold text-slate-900">Author</th>
                <th class="px-4 py-3 font-semibold text-slate-900">Category</th>
                <th class="px-4 py-3 font-semibold text-slate-900 text-center">Status</th>
                <th class="px-4 py-3 font-semibold text-slate-900">Date</th>
                <th class="px-4 py-3 font-semibold text-slate-900 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant">
            @forelse($posts as $post)
            <tr class="hover:bg-white-dim/50 transition-colors">
                <td class="px-4 py-3">
                    <p class="font-medium text-slate-900">{{ $post->title }}</p>
                    <p class="text-xs text-slate-500 font-mono">/{{ $post->slug }}</p>
                </td>
                <td class="px-4 py-3 text-slate-500 text-xs">{{ $post->author?->first_name ?? 'Unknown' }}</td>
                <td class="px-4 py-3 text-slate-500 text-xs">{{ $post->category?->name ?? '—' }}</td>
                <td class="px-4 py-3 text-center"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $post->status->badgeColor() }}">{{ $post->status->label() }}</span></td>
                <td class="px-4 py-3 text-xs text-slate-500">
                    @if($post->published_at) {{ $post->published_at->format('M d, Y') }} @elseif($post->scheduled_at) Scheduled {{ $post->scheduled_at->format('M d') }} @else {{ $post->created_at->format('M d, Y') }} @endif
                </td>
                <td class="px-4 py-3 text-right space-x-2">
                    <a href="{{ route('admin.cms.posts.preview', $post) }}" class="text-slate-500 hover:text-slate-900 text-sm">Preview</a>
                    <a href="{{ route('admin.cms.posts.edit', $post) }}" class="text-slate-900 hover:text-slate-900-dim text-sm font-medium">Edit</a>
                    <form action="{{ route('admin.cms.posts.toggle', $post) }}" method="POST" class="inline">@csrf
                        <button type="submit" class="text-sm font-medium {{ $post->status === \App\Modules\Cms\Enums\PostStatus::PUBLISHED ? 'text-yellow-600' : 'text-green-600' }}">{{ $post->status === \App\Modules\Cms\Enums\PostStatus::PUBLISHED ? 'Unpublish' : 'Publish' }}</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="p-8 text-center text-slate-500">
                        <div class="bg-slate-50 border border-dashed border-slate-300 rounded-md py-6 text-[11px] uppercase tracking-widest font-bold">
                            No posts yet.
                        </div>
                    </td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $posts->links() }}</div>
@endsection
