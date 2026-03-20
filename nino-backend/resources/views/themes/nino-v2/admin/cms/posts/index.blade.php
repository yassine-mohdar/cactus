@extends('admin.layouts.app')

@php
    $postTone = static function ($status): string {
        $value = $status?->value ?? (string) $status;

        return match ($value) {
            'published' => 'success',
            'scheduled' => 'info',
            'archived' => 'neutral',
            default => 'warning',
        };
    };
@endphp

@section('title', 'Blog Posts')

@section('header')
    <x-nino.page-header
        title="Blog Posts"
        subtitle="Editorial planning, publishing control, and SEO readiness for every article in the content pipeline.">
        <x-slot:actions>
            <x-nino.button href="{{ route('admin.cms.posts.create') }}" variant="primary" icon="edit_square">New Post</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    @if(session('success'))
        <x-nino.inline-alert tone="success" title="Post updated" class="mb-6">
            {{ session('success') }}
        </x-nino.inline-alert>
    @endif

    <div class="stats-grid mb-6">
        <div class="stat-card">
            <p class="stat-label">Total Posts</p>
            <p class="stat-value">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Published</p>
            <p class="stat-value text-[#1F7A4E]">{{ number_format($stats['published']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Drafts</p>
            <p class="stat-value text-[#B8802F]">{{ number_format($stats['draft']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Scheduled</p>
            <p class="stat-value text-[#3B6F95]">{{ number_format($stats['scheduled']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">SEO Ready</p>
            <p class="stat-value text-[#245848]">{{ number_format($stats['seo_ready']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Views</p>
            <p class="stat-value text-[#61706B]">{{ number_format($stats['views']) }}</p>
        </div>
    </div>

    <div class="filter-toolbar mb-6">
        <div class="space-y-4">
            <x-nino.tab-strip label="Editorial workspace">
                <a href="{{ route('admin.cms.posts.index') }}" class="tab-pill tab-pill-active">Posts</a>
                <a href="{{ route('admin.cms.categories.index') }}" class="tab-pill">Categories</a>
                <a href="{{ route('admin.cms.redirects.index') }}" class="tab-pill">Redirects</a>
                <a href="{{ route('admin.cms.tags.index') }}" class="tab-pill">Tags</a>
            </x-nino.tab-strip>

            <form method="GET">
                <div class="filter-grid xl:grid-cols-5">
                    <div class="filter-field xl:col-span-2">
                        <label class="filter-label" for="post-search">Search</label>
                        <input id="post-search" type="text" name="search" value="{{ request('search') }}" placeholder="Title or slug..." class="input-field">
                    </div>
                    <div class="filter-field">
                        <label class="filter-label" for="post-status">Status</label>
                        <select id="post-status" name="status" class="input-field">
                            <option value="">All</option>
                            @foreach(\App\Modules\Cms\Enums\PostStatus::cases() as $s)
                                <option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-field">
                        <label class="filter-label" for="post-category">Category</label>
                        <select id="post-category" name="category" class="input-field">
                            <option value="">All</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}" {{ request('category') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-field xl:items-end">
                        <div class="flex flex-wrap gap-3">
                            <x-nino.button type="submit" variant="primary">Apply Filters</x-nino.button>
                            <x-nino.button href="{{ route('admin.cms.posts.index') }}" variant="outline">Reset</x-nino.button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="datatable-shell">
        <div class="datatable-header">
            <div>
                <h2 class="datatable-title">Editorial Queue</h2>
                <p class="datatable-subtitle">Current article backlog with publication state, category placement, and SEO readiness.</p>
            </div>
            <span class="datatable-meta">{{ number_format($posts->total()) }} posts</span>
        </div>

        <x-nino.table>
            <x-slot:head>
                <th>Title</th>
                <th>Category</th>
                <th>Status</th>
                <th>SEO</th>
                <th>Readership</th>
                <th>Published</th>
                <th class="text-right">Actions</th>
            </x-slot:head>

            <x-slot:body>
                @forelse($posts as $post)
                    @php $seoReady = filled($post->meta_title) || filled($post->meta_description); @endphp
                    <tr>
                        <td>
                            <p class="text-sm font-semibold text-[#1E2B27]">{{ $post->title }}</p>
                            <p class="mt-1 font-mono text-xs text-[#7A8681]">/{{ $post->slug }}</p>
                        </td>
                        <td>
                            <p class="text-sm text-[#1E2B27]">{{ $post->category?->name ?? 'Uncategorized' }}</p>
                            <p class="mt-1 text-xs text-[#7A8681]">{{ $post->author?->first_name ?? 'Unknown author' }}</p>
                        </td>
                        <td>
                            <x-nino.status-badge :tone="$postTone($post->status)" size="sm">{{ $post->status->label() }}</x-nino.status-badge>
                        </td>
                        <td>
                            <x-nino.status-badge :tone="$seoReady ? 'success' : 'warning'" size="sm">
                                {{ $seoReady ? 'Ready' : 'Needs SEO' }}
                            </x-nino.status-badge>
                        </td>
                        <td class="font-mono text-sm text-[#1E2B27]">{{ number_format((int) $post->views_count) }}</td>
                        <td>
                            <p class="text-sm text-[#1E2B27]">
                                @if($post->published_at)
                                    {{ $post->published_at->format('M d, Y') }}
                                @elseif($post->scheduled_at)
                                    Scheduled {{ $post->scheduled_at->format('M d, Y H:i') }}
                                @else
                                    {{ $post->created_at->format('M d, Y') }}
                                @endif
                            </p>
                        </td>
                        <td class="text-right">
                            <div class="table-actions">
                                @if($post->exists)
                                    <a href="{{ route('admin.cms.posts.preview', $post) }}" class="table-action-link">Preview</a>
                                @endif
                                <a href="{{ route('admin.cms.posts.edit', $post) }}" class="table-action-link">Edit</a>
                                <form action="{{ route('admin.cms.posts.toggle', $post) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="table-action-link">
                                        {{ $post->status === \App\Modules\Cms\Enums\PostStatus::PUBLISHED ? 'Unpublish' : 'Publish' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="datatable-empty">
                            <x-nino.empty-state
                                title="No posts match the current filters"
                                description="Create the first blog post or widen the filters to see more editorial work."
                                icon="article" />
                        </td>
                    </tr>
                @endforelse
            </x-slot:body>
        </x-nino.table>

        <div class="datatable-footer">
            {{ $posts->links() }}
        </div>
    </div>
@endsection
