@extends('admin.layouts.app')
@section('title', 'Preview: ' . $post->title)
@section('content')
<div class="mb-6">
    <a href="{{ route('admin.cms.posts.edit', $post) }}" class="text-sm text-slate-900 hover:text-slate-900-dim">← Back to Editor</a>
    <div class="flex items-center gap-3 mt-2">
        <h1 class="text-2xl font-bold text-slate-900">Preview</h1>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $post->status->badgeColor() }}">{{ $post->status->label() }}</span>
    </div>
</div>

<div class="bg-white border border-slate-200 rounded-md shadow-sm overflow-hidden">
    {{-- Featured Image --}}
    @if($post->featured_image)
        <div class="w-full h-64 bg-white-dim">
            <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" class="w-full h-full object-cover">
        </div>
    @endif

    <div class="p-8 max-w-3xl mx-auto">
        {{-- Meta --}}
        <div class="flex items-center gap-3 text-xs text-slate-500 mb-4">
            @if($post->category)<span class="bg-slate-100 text-slate-900 px-2 py-0.5 rounded-full font-medium">{{ $post->category->name }}</span>@endif
            @if($post->author)<span>By {{ $post->author->first_name }} {{ $post->author->last_name }}</span>@endif
            @if($post->published_at)<span>• {{ $post->published_at->format('M d, Y') }}</span>@endif
            <span>• {{ $post->readingTime() }} min read</span>
        </div>

        {{-- Title --}}
        <h1 class="text-3xl font-bold text-slate-900 mb-4 leading-tight">{{ $post->title }}</h1>

        {{-- Excerpt --}}
        @if($post->excerpt)<p class="text-lg text-slate-500 mb-6 italic">{{ $post->excerpt }}</p>@endif

        {{-- Tags --}}
        @if($post->tags->count())
        <div class="flex flex-wrap gap-2 mb-6">
            @foreach($post->tags as $tag)<span class="px-2.5 py-0.5 rounded-full bg-white-dim text-xs font-medium text-slate-500">#{{ $tag->name }}</span>@endforeach
        </div>
        @endif

        {{-- Body --}}
        <div class="prose prose-sm max-w-none text-slate-900">{!! nl2br(e($post->body)) !!}</div>

        {{-- SEO Preview --}}
        <div class="mt-8 pt-6 border-t border-slate-200">
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">SEO Preview</h3>
            <div class="bg-white-dim rounded-lg p-4 space-y-1">
                <p class="text-blue-700 text-base font-medium">{{ $post->resolvedMetaTitle() }}</p>
                <p class="text-green-700 text-xs font-mono">{{ url('/blog/' . $post->slug) }}</p>
                <p class="text-slate-500 text-sm">{{ $post->resolvedMetaDescription() }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
