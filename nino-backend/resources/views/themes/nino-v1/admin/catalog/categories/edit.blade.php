@extends('admin.layouts.app')

@section('header', 'Edit Category')

@section('content')
    <div class="mb-6 flex items-center space-x-4">
        <a href="{{ route('admin.catalog.categories.index') }}" class="text-slate-500 hover:text-slate-900 transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Edit Category: {{ $category->name }}</h1>
    </div>

    <form action="{{ route('admin.catalog.categories.update', $category) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <x-admin.card title="Category Details">
                    <div class="space-y-4">
                        <x-admin.input name="name" label="Category Name" :value="old('name', $category->name)" required />
                        
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-500 tracking-widest mb-1">Description</label>
                            <x-admin.textarea name="description" rows="4">{{ old('description', $category->description) }}</x-admin.textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </x-admin.card>

                <x-admin.card title="Search Engine Optimization">
                    <div class="space-y-4">
                        <x-admin.input name="meta_title" label="Meta Title" :value="old('meta_title', $category->meta_title)" />
                        
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-500 tracking-widest mb-1">Meta Description</label>
                            <x-admin.textarea name="meta_description" rows="3">{{ old('meta_description', $category->meta_description) }}</x-admin.textarea>
                            @error('meta_description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </x-admin.card>
            </div>

            <!-- Sidebar Content -->
            <div class="space-y-6">
                <x-admin.card title="Organization">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-500 tracking-widest mb-1">Parent Category</label>
                            <x-admin.select name="parent_id">
                                <option value="">None (Top Level)</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" @selected(old('parent_id', $category->parent_id) == $cat->id)>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </x-admin.select>
                            @error('parent_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <x-admin.input type="number" name="sort_order" label="Sort Order" :value="old('sort_order', $category->sort_order)" />

                        <div class="pt-2">
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active)) class="rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                                <span class="text-sm font-bold text-slate-900">Active</span>
                            </label>
                        </div>
                    </div>
                </x-admin.card>

                <x-admin.card title="Media">
                    <div class="space-y-4">
                        @if($category->image_path)
                            <div class="mb-2">
                                <img src="{{ Storage::url($category->image_path) }}" alt="{{ $category->name }}" class="w-full h-auto rounded-md border border-slate-300 object-cover">
                            </div>
                        @endif
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-500 tracking-widest mb-1">{{ $category->image_path ? 'Replace Image' : 'Upload Image' }}</label>
                            <input type="file" name="image" accept="image/*" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-bold file:bg-slate-100 file:text-slate-900 hover:file:bg-slate-200 transition cursor-pointer border border-slate-300 rounded-md p-1 bg-surface">
                            @error('image')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </x-admin.card>

                <div class="flex justify-end space-x-3">
                    <x-admin.button href="{{ route('admin.catalog.categories.index') }}" variant="secondary">Cancel</x-admin.button>
                    <x-admin.button type="submit" variant="primary">Update Category</x-admin.button>
                </div>
            </div>
        </div>
    </form>
@endsection
