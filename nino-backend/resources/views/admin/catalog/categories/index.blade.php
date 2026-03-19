@extends('admin.layouts.app')

@section('header', 'Categories')

@section('content')
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold tracking-tight text-ink">Categories</h1>
        @can('catalog.categories.create')
            <x-admin.button href="{{ route('admin.catalog.categories.create') }}" variant="primary">
                Add Category
            </x-admin.button>
        @endcan
    </div>

    @if (session('success'))
        <div class="mb-4 text-sm font-medium text-green-600 bg-green-50 p-3 rounded-lg border border-green-200">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 text-sm font-medium text-red-600 bg-red-50 p-3 rounded-lg border border-red-200">
            {{ session('error') }}
        </div>
    @endif

    <x-admin.card>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-ink-muted">
                <thead class="bg-gray-50/50 text-xs uppercase text-ink/70">
                    <tr>
                        <th class="p-4 font-semibold border-b border-border">Name</th>
                        <th class="p-4 font-semibold border-b border-border">Slug</th>
                        <th class="p-4 font-semibold border-b border-border">Parent</th>
                        <th class="p-4 font-semibold border-b border-border text-center">Status</th>
                        <th class="p-4 font-semibold border-b border-border text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($categories as $category)
                        <tr class="hover:bg-gray-50/50 transition duration-150">
                            <td class="p-4 font-medium text-ink">
                                <div class="flex items-center space-x-3">
                                    @if($category->image_path)
                                        <img src="{{ Storage::url($category->image_path) }}" class="w-8 h-8 rounded object-cover border border-border">
                                    @else
                                        <div class="w-8 h-8 rounded bg-gray-100 border border-border flex items-center justify-center text-xs text-gray-400">IMG</div>
                                    @endif
                                    <span>{{ $category->name }}</span>
                                </div>
                            </td>
                            <td class="p-4">{{ $category->slug }}</td>
                            <td class="p-4">
                                @if($category->parent)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ $category->parent->name }}
                                    </span>
                                @else
                                    <span class="text-gray-400 italic">None</span>
                                @endif
                            </td>
                            <td class="p-4 text-center">
                                @if($category->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Active</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">Inactive</span>
                                @endif
                            </td>
                            <td class="p-4 text-right space-x-2">
                                @can('catalog.categories.update')
                                    <a href="{{ route('admin.catalog.categories.edit', $category) }}" class="text-sage hover:text-sage-dark font-medium transition">Edit</a>
                                @endcan
                                
                                @can('catalog.categories.delete')
                                    <form action="{{ route('admin.catalog.categories.destroy', $category) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 font-medium transition">Delete</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-4 text-center text-ink-muted">No categories found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($categories->hasPages())
            <div class="mt-4 border-t border-border pt-4 px-4">
                {{ $categories->links() }}
            </div>
        @endif
    </x-admin.card>
@endsection
