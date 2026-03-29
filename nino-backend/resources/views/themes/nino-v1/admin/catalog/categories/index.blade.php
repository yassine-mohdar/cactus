@extends('admin.layouts.app')

@section('title', 'Categories')
@section('header')
    <div class="page-header">
        <div>
            <h1 class="page-title">Categories</h1>
            <p class="page-subtitle">Manage taxonomy, hierarchy, and publishing state for your product catalog.</p>
        </div>
        @can('catalog.categories.create')
            <x-admin.button href="{{ route('admin.catalog.categories.create') }}" variant="primary" class="flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">add</span> Add Category
            </x-admin.button>
        @endcan
    </div>
@endsection

@section('content')
    <x-admin.card noPadding>
        <div class="datatable-header">
            <div>
                <h2 class="datatable-title">Category Directory</h2>
                <p class="datatable-subtitle">Hierarchy and status are easier to scan, with cleaner actions and empty-state handling.</p>
            </div>
            <span class="datatable-meta">{{ number_format($categories->total()) }} categories</span>
        </div>

        <div class="datatable-scroll">
            <table class="nino-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Parent</th>
                        <th class="text-center">Order</th>
                        <th class="text-center">Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td class="px-6 py-4 font-medium text-slate-900">
                                <div class="flex items-center gap-3">
                                    @if($category->image_path)
                                        <img src="{{ Storage::url($category->image_path) }}" class="w-8 h-8 rounded-lg object-cover ring-1 ring-outline/20">
                                    @else
                                        <div class="w-8 h-8 rounded-lg bg-white-container flex items-center justify-center text-[10px] font-bold text-outline">IMG</div>
                                    @endif
                                    <a href="{{ route('admin.catalog.categories.edit', $category) }}" class="table-link text-sm">{{ $category->name }}</a>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500 font-medium">{{ $category->slug }}</td>
                            <td class="px-6 py-4">
                                @if($category->parent)
                                    <span class="inline-flex items-center px-2 py-1 rounded text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-300">
                                        {{ $category->parent->name }}
                                    </span>
                                @else
                                    <span class="text-outline text-xs italic">None</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center font-mono text-xs text-slate-500">{{ $category->sort_order }}</td>
                            <td class="px-6 py-4 text-center">
                                @if($category->is_active)
                                    <span class="inline-flex items-center px-2 py-1 rounded text-[10px] font-bold bg-slate-100 text-slate-900">ACTIVE</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded text-[10px] font-bold bg-white-container-highest text-outline">INACTIVE</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
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
                                <div class="datatable-empty-panel">
                                    <p class="text-sm font-semibold text-slate-900">No categories found.</p>
                                    <p class="text-sm text-slate-500">Create categories to structure product discovery and merchandising.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($categories->hasPages())
            <div class="datatable-footer">
                {{ $categories->links() }}
            </div>
        @endif
    </x-admin.card>
@endsection
