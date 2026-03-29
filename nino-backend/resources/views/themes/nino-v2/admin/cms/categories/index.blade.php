@extends('admin.layouts.app')

@section('title', 'Blog Categories')

@section('header')
    <x-nino.page-header
        title="Blog Categories"
        subtitle="Organize editorial content into navigable sections, parent hierarchies, and SEO-aware landing pages.">
        <x-slot:actions>
            <x-nino.button href="{{ route('admin.cms.categories.create') }}" variant="primary" icon="add">New Category</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    @if(session('success'))
        <x-nino.inline-alert tone="success" title="Category updated" class="mb-6">
            {{ session('success') }}
        </x-nino.inline-alert>
    @endif

    @if(session('error'))
        <x-nino.inline-alert tone="danger" title="Category action failed" class="mb-6">
            {{ session('error') }}
        </x-nino.inline-alert>
    @endif

    <div class="stats-grid mb-6">
        <div class="stat-card">
            <p class="stat-label">Categories</p>
            <p class="stat-value">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Active</p>
            <p class="stat-value text-[#1F7A4E]">{{ number_format($stats['active']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Child Categories</p>
            <p class="stat-value text-[#3B6F95]">{{ number_format($stats['child_categories']) }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Assigned Posts</p>
            <p class="stat-value text-[#61706B]">{{ number_format($stats['assigned_posts']) }}</p>
        </div>
    </div>

    <div class="filter-toolbar mb-6">
        <x-nino.tab-strip label="Editorial workspace">
            <a href="{{ route('admin.cms.posts.index') }}" class="tab-pill">Posts</a>
            <a href="{{ route('admin.cms.categories.index') }}" class="tab-pill tab-pill-active">Categories</a>
            <a href="{{ route('admin.cms.redirects.index') }}" class="tab-pill">Redirects</a>
            <a href="{{ route('admin.cms.tags.index') }}" class="tab-pill">Tags</a>
        </x-nino.tab-strip>
    </div>

    <div class="datatable-shell">
        <div class="datatable-header">
            <div>
                <h2 class="datatable-title">Category Structure</h2>
                <p class="datatable-subtitle">Parent-child hierarchy, article counts, and live status for every editorial category.</p>
            </div>
            <span class="datatable-meta">{{ number_format(count($categories)) }} root categories</span>
        </div>

        <x-nino.table>
            <x-slot:head>
                <th>Name</th>
                <th>Slug</th>
                <th>Posts</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </x-slot:head>

            <x-slot:body>
                @forelse($categories as $cat)
                    <tr>
                        <td>
                            <p class="text-sm font-semibold text-[#1E2B27]">{{ $cat->name }}</p>
                            @if($cat->description)
                                <p class="mt-1 text-xs text-[#7A8681]">{{ \Illuminate\Support\Str::limit($cat->description, 80) }}</p>
                            @endif
                        </td>
                        <td class="font-mono text-xs text-[#61706B]">{{ $cat->slug }}</td>
                        <td class="font-mono text-sm text-[#1E2B27]">{{ number_format($cat->posts_count) }}</td>
                        <td>
                            <x-nino.status-badge :tone="$cat->is_active ? 'success' : 'neutral'" size="sm">
                                {{ $cat->is_active ? 'Active' : 'Inactive' }}
                            </x-nino.status-badge>
                        </td>
                        <td class="text-right">
                            <div class="table-actions">
                                <a href="{{ route('admin.cms.categories.edit', $cat) }}" class="table-action-link">Edit</a>
                                <form action="{{ route('admin.cms.categories.destroy', $cat) }}" method="POST" class="inline" onsubmit="return confirm('Delete this category?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="table-action-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @foreach($cat->children as $child)
                        <tr class="bg-[#FBFAF7]">
                            <td>
                                <div class="flex items-start gap-2">
                                    <span class="mt-0.5 text-xs text-[#7A8681]">↳</span>
                                    <div>
                                        <p class="text-sm font-semibold text-[#1E2B27]">{{ $child->name }}</p>
                                        @if($child->description)
                                            <p class="mt-1 text-xs text-[#7A8681]">{{ \Illuminate\Support\Str::limit($child->description, 80) }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="font-mono text-xs text-[#61706B]">{{ $child->slug }}</td>
                            <td class="font-mono text-sm text-[#1E2B27]">—</td>
                            <td>
                                <x-nino.status-badge :tone="$child->is_active ? 'success' : 'neutral'" size="sm">
                                    {{ $child->is_active ? 'Active' : 'Inactive' }}
                                </x-nino.status-badge>
                            </td>
                            <td class="text-right">
                                <div class="table-actions">
                                    <a href="{{ route('admin.cms.categories.edit', $child) }}" class="table-action-link">Edit</a>
                                    <form action="{{ route('admin.cms.categories.destroy', $child) }}" method="POST" class="inline" onsubmit="return confirm('Delete this category?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="table-action-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="5" class="datatable-empty">
                            <x-nino.empty-state
                                title="No categories created yet"
                                description="Create editorial categories to organize posts and build structured topic hubs."
                                icon="category" />
                        </td>
                    </tr>
                @endforelse
            </x-slot:body>
        </x-nino.table>
    </div>
@endsection
