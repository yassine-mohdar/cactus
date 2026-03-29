@extends('admin.layouts.app')

@section('title', 'Blog Tags')

@section('header')
    <x-nino.page-header
        title="Blog Tags"
        subtitle="Lightweight topic labels that help editorial teams group related posts and campaigns." />
@endsection

@section('content')
    @if(session('success'))
        <x-nino.inline-alert tone="success" title="Tag updated" class="mb-6">
            {{ session('success') }}
        </x-nino.inline-alert>
    @endif

    <div class="stats-grid mb-6">
        <div class="stat-card">
            <p class="stat-label">Tags</p>
            <p class="stat-value">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Used Tags</p>
            <p class="stat-value text-[#1F7A4E]">{{ number_format($stats['used']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Unused Tags</p>
            <p class="stat-value text-[#B8802F]">{{ number_format($stats['unused']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Linked Posts</p>
            <p class="stat-value text-[#61706B]">{{ number_format($stats['linked_posts']) }}</p>
        </div>
    </div>

    <div class="filter-toolbar mb-6">
        <div class="space-y-4">
            <x-nino.tab-strip label="Editorial workspace">
                <a href="{{ route('admin.cms.posts.index') }}" class="tab-pill">Posts</a>
                <a href="{{ route('admin.cms.categories.index') }}" class="tab-pill">Categories</a>
                <a href="{{ route('admin.cms.redirects.index') }}" class="tab-pill">Redirects</a>
                <a href="{{ route('admin.cms.tags.index') }}" class="tab-pill tab-pill-active">Tags</a>
            </x-nino.tab-strip>

            <form method="POST" action="{{ route('admin.cms.tags.store') }}">
                @csrf
                <div class="entity-section-grid xl:grid-cols-4">
                    <div class="xl:col-span-3">
                        <label class="filter-label" for="tag-name">Tag Name</label>
                        <input id="tag-name" type="text" name="name" required class="input-field" placeholder="New tag...">
                    </div>
                    <div class="flex items-end justify-end">
                        <x-nino.button type="submit" variant="primary">Add Tag</x-nino.button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="datatable-shell">
        <div class="datatable-header">
            <div>
                <h2 class="datatable-title">Tag Directory</h2>
                <p class="datatable-subtitle">Simple taxonomy labels with slug, usage count, and lifecycle cleanup controls.</p>
            </div>
            <span class="datatable-meta">{{ number_format($tags->total()) }} tags</span>
        </div>

        <x-nino.table>
            <x-slot:head>
                <th>Name</th>
                <th>Slug</th>
                <th>Posts</th>
                <th class="text-right">Actions</th>
            </x-slot:head>

            <x-slot:body>
                @forelse($tags as $tag)
                    <tr>
                        <td class="text-sm font-semibold text-[#1E2B27]">{{ $tag->name }}</td>
                        <td class="font-mono text-xs text-[#61706B]">{{ $tag->slug }}</td>
                        <td class="font-mono text-sm text-[#1E2B27]">{{ number_format($tag->posts_count) }}</td>
                        <td class="text-right">
                            <div class="table-actions">
                                <form action="{{ route('admin.cms.tags.destroy', $tag) }}" method="POST" class="inline" onsubmit="return confirm('Delete this tag?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="table-action-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="datatable-empty">
                            <x-nino.empty-state
                                title="No tags created yet"
                                description="Add lightweight topical tags to organize related content across campaigns and posts."
                                icon="sell" />
                        </td>
                    </tr>
                @endforelse
            </x-slot:body>
        </x-nino.table>

        <div class="datatable-footer">
            {{ $tags->links() }}
        </div>
    </div>
@endsection
