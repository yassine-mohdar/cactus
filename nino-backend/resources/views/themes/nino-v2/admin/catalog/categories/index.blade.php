@extends('admin.layouts.app')

@section('title', 'Categories')
@section('header')
    <x-nino.page-header
        title="Categories"
        subtitle="Manage taxonomy, hierarchy, and publishing state for your product catalog.">
        <x-slot:actions>
            @can('catalog.categories.create')
                <x-nino.button href="{{ route('admin.catalog.categories.create') }}" variant="primary" icon="add">Add Category</x-nino.button>
            @endcan
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    <div class="space-y-6">
        <div class="metric-grid">
            <div class="metric-tile">
                <p class="metric-label">Total Categories</p>
                <h3 class="metric-value">{{ number_format($summary['total_categories']) }}</h3>
                <p class="metric-subtitle">All taxonomy records across the catalog</p>
            </div>
            <div class="metric-tile">
                <p class="metric-label">Active</p>
                <h3 class="metric-value">{{ number_format($summary['active']) }}</h3>
                <p class="metric-subtitle">Categories currently available to merchandising</p>
            </div>
            <div class="metric-tile">
                <p class="metric-label">Root Categories</p>
                <h3 class="metric-value">{{ number_format($summary['root_categories']) }}</h3>
                <p class="metric-subtitle">Top-level navigation anchors</p>
            </div>
            <div class="metric-tile">
                <p class="metric-label">Child Categories</p>
                <h3 class="metric-value">{{ number_format($summary['child_categories']) }}</h3>
                <p class="metric-subtitle">Nested categories used for tighter taxonomy</p>
            </div>
        </div>

        <div class="datatable-shell">
            <div class="datatable-header">
                <div>
                    <h2 class="datatable-title">Category Directory</h2>
                    <p class="datatable-subtitle">Hierarchy and status are easier to scan, with cleaner actions and empty-state handling.</p>
                </div>
                <span class="datatable-meta">{{ number_format($categories->total()) }} categories</span>
            </div>

            <x-nino.table>
                <x-slot name="head">
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Parent</th>
                    <th class="text-center">Order</th>
                    <th class="text-center">Status</th>
                    <th class="text-right">Actions</th>
                </x-slot>
                <x-slot name="body">
                    @forelse($categories as $category)
                        <tr>
                            <td class="font-medium text-[#1E2B27]">
                                <div class="flex items-center gap-3">
                                    @if($category->image_path)
                                        <img src="{{ Storage::url($category->image_path) }}" class="h-8 w-8 rounded-md border border-[rgba(120,112,95,0.16)] object-cover">
                                    @else
                                        <div class="flex h-8 w-8 items-center justify-center rounded-md border border-[rgba(120,112,95,0.16)] bg-[#FBFAF7] text-[10px] font-bold tracking-[0.18em] text-[#6A7671]">IMG</div>
                                    @endif
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.catalog.categories.edit', $category) }}" class="table-link text-sm">{{ $category->name }}</a>
                                        @if($category->description)
                                            <p class="mt-1 line-clamp-1 text-xs text-[#61706B]">{{ $category->description }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="font-mono text-xs text-[#61706B]">{{ $category->slug }}</td>
                            <td>
                                @if($category->parent)
                                    <span class="inline-flex items-center rounded-md border border-[rgba(120,112,95,0.14)] bg-[#FBFAF7] px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#61706B]">
                                        {{ $category->parent->name }}
                                    </span>
                                @else
                                    <span class="text-xs italic text-[#7A8681]">None</span>
                                @endif
                            </td>
                            <td class="text-center font-mono text-xs text-[#61706B]">{{ $category->sort_order }}</td>
                            <td class="text-center">
                                <x-nino.status-badge :tone="$category->is_active ? 'success' : 'neutral'" size="sm">{{ $category->is_active ? 'Active' : 'Inactive' }}</x-nino.status-badge>
                                @if($category->meta_title || $category->meta_description || $category->canonical_url || $category->og_title || $category->og_description || $category->og_image || $category->noindex)
                                    <div class="mt-2">
                                        <span class="inline-flex items-center rounded-md border border-[rgba(36,88,72,0.12)] bg-[#EAF3EE] px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.16em] text-[#245848]">
                                            SEO Ready
                                        </span>
                                        @if($category->noindex)
                                            <span class="ml-1 inline-flex items-center rounded-md border border-[rgba(196,81,67,0.14)] bg-[#FCF0ED] px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.16em] text-[#C45143]">
                                                Noindex
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="table-actions">
                                    @can('catalog.categories.update')
                                        <a href="{{ route('admin.catalog.categories.edit', $category) }}" class="table-action-link">Edit</a>
                                    @endcan

                                    @can('catalog.categories.delete')
                                        <form action="{{ route('admin.catalog.categories.destroy', $category) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="table-action-danger">Delete</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="datatable-empty">
                                <x-nino.empty-state
                                    title="No categories found"
                                    description="Create categories to structure product discovery and merchandising."
                                    icon="category" />
                            </td>
                        </tr>
                    @endforelse
                </x-slot>
            </x-nino.table>

            @if($categories->hasPages())
                <div class="datatable-footer">
                    {{ $categories->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
