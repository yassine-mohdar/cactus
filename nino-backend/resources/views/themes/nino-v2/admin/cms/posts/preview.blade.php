@extends('admin.layouts.app')

@php
    $postTone = match ($post->status->value) {
        'published' => 'success',
        'scheduled' => 'info',
        'archived' => 'neutral',
        default => 'warning',
    };
@endphp

@section('title', 'Preview: ' . $post->title)

@section('header')
    <x-nino.page-header
        title="Preview: {{ $post->title }}"
        subtitle="Editorial preview inside the admin shell, including metadata, body content, and search appearance.">
        <x-slot:actions>
            <x-nino.status-badge :tone="$postTone">{{ $post->status->label() }}</x-nino.status-badge>
            <x-nino.button href="{{ route('admin.cms.posts.edit', $post) }}" variant="secondary" icon="edit">Back to Editor</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    <div class="detail-grid">
        <div class="detail-main">
            <section class="page-section overflow-hidden">
                @if($post->featured_image)
                    <div class="h-72 w-full bg-[#FBFAF7]">
                        <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" class="h-full w-full object-cover">
                    </div>
                @endif

                <div class="px-8 py-8">
                    <div class="flex flex-wrap items-center gap-3 text-xs text-[#61706B]">
                        @if($post->category)
                            <span class="rounded-full border border-[rgba(120,112,95,0.14)] bg-[#FBFAF7] px-3 py-1 font-semibold text-[#1E2B27]">{{ $post->category->name }}</span>
                        @endif
                        @if($post->author)
                            <span>By {{ $post->author->first_name }} {{ $post->author->last_name }}</span>
                        @endif
                        @if($post->published_at)
                            <span>{{ $post->published_at->format('M d, Y') }}</span>
                        @endif
                        <span>{{ $post->readingTime() }} min read</span>
                    </div>

                    <h1 class="mt-5 text-4xl font-bold tracking-tight text-[#1E2B27]">{{ $post->title }}</h1>

                    @if($post->excerpt)
                        <p class="mt-4 text-lg leading-8 text-[#61706B]">{{ $post->excerpt }}</p>
                    @endif

                    @if($post->tags->count())
                        <div class="mt-6 flex flex-wrap gap-2">
                            @foreach($post->tags as $tag)
                                <span class="rounded-full border border-[rgba(120,112,95,0.14)] bg-[#FBFAF7] px-3 py-1 text-xs font-semibold text-[#61706B]">#{{ $tag->name }}</span>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-8 prose prose-sm max-w-none text-[#1E2B27]">
                        {!! nl2br(e($post->body)) !!}
                    </div>
                </div>
            </section>
        </div>

        <div class="detail-sidebar">
            <x-nino.detail-section title="SEO Preview" subtitle="How the article is likely to appear in search and social surfaces.">
                <div class="space-y-4">
                    <div class="surface-panel">
                        <p class="text-base font-semibold text-[#235B8C]">{{ $post->resolvedMetaTitle() }}</p>
                        <p class="mt-1 font-mono text-xs text-[#1F7A4E]">{{ url('/blog/' . $post->slug) }}</p>
                        <p class="mt-2 text-sm text-[#61706B]">{{ $post->resolvedMetaDescription() }}</p>
                    </div>

                    <x-nino.entity-detail-grid>
                        <div>
                            <p class="detail-kicker">Meta Title</p>
                            <p class="detail-value">{{ $post->meta_title ?: 'Fallback to title' }}</p>
                        </div>
                        <div>
                            <p class="detail-kicker">Canonical URL</p>
                            <p class="detail-value">{{ $post->canonical_url ?: 'Self-referencing' }}</p>
                        </div>
                        <div>
                            <p class="detail-kicker">Views</p>
                            <p class="detail-value-mono">{{ number_format((int) $post->views_count) }}</p>
                        </div>
                        <div>
                            <p class="detail-kicker">Indexing</p>
                            <p class="detail-value">{{ $post->noindex ? 'Noindex' : 'Indexable' }}</p>
                        </div>
                    </x-nino.entity-detail-grid>
                </div>
            </x-nino.detail-section>
        </div>
    </div>
@endsection
