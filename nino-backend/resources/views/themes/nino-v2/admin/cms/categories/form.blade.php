@extends('admin.layouts.app')

@php $editing = isset($category); @endphp

@section('title', $editing ? 'Edit Category' : 'New Category')

@section('header')
    <x-nino.page-header
        title="{{ $editing ? 'Edit '.$category->name : 'Create Blog Category' }}"
        subtitle="Maintain category hierarchy, descriptions, and search metadata for editorial topic pages.">
        <x-slot:actions>
            <x-nino.button href="{{ route('admin.cms.categories.index') }}" variant="secondary" icon="arrow_back">Back to Categories</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    @if($errors->any())
        <x-nino.inline-alert tone="danger" title="Please fix the highlighted fields" class="mb-6">
            {{ collect($errors->all())->join(' ') }}
        </x-nino.inline-alert>
    @endif

    <form action="{{ $editing ? route('admin.cms.categories.update', $category) : route('admin.cms.categories.store') }}" method="POST" class="form-layout">
        @csrf
        @if($editing)
            @method('PUT')
        @endif

        <div class="form-main">
            <x-nino.entity-form-section title="Category Profile" subtitle="Define the category name, hierarchy placement, and descriptive context.">
                <div class="space-y-4">
                    <div class="entity-section-grid">
                        <div>
                            <label class="filter-label" for="category-name">Name</label>
                            <input id="category-name" type="text" name="name" value="{{ old('name', $category->name ?? '') }}" required class="input-field">
                        </div>
                        <div>
                            <label class="filter-label" for="category-slug">Slug</label>
                            <input id="category-slug" type="text" name="slug" value="{{ old('slug', $category->slug ?? '') }}" class="input-field" placeholder="Auto-generated if left blank">
                        </div>
                        <div>
                            <label class="filter-label" for="category-parent">Parent Category</label>
                            <select id="category-parent" name="parent_id" class="input-field">
                                <option value="">Root category</option>
                                @foreach($parents as $p)
                                    <option value="{{ $p->id }}" {{ old('parent_id', $category->parent_id ?? '') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="filter-label" for="category-sort-order">Sort Order</label>
                            <input id="category-sort-order" type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="input-field">
                        </div>
                        <div class="md:col-span-2">
                            <label class="filter-label" for="category-description">Description</label>
                            <textarea id="category-description" name="description" rows="4" class="input-field">{{ old('description', $category->description ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </x-nino.entity-form-section>

            <x-nino.entity-form-section title="SEO" subtitle="Search metadata used for the category landing page.">
                <div class="entity-section-grid">
                    <div>
                        <label class="filter-label" for="category-meta-title">Meta Title</label>
                        <input id="category-meta-title" type="text" name="meta_title" value="{{ old('meta_title', $category->meta_title ?? '') }}" class="input-field">
                    </div>
                    <div>
                        <label class="filter-label" for="category-canonical-url">Canonical URL</label>
                        <input id="category-canonical-url" type="url" name="canonical_url" value="{{ old('canonical_url', $category->canonical_url ?? '') }}" class="input-field">
                    </div>
                    <div class="md:col-span-2">
                        <label class="filter-label" for="category-meta-description">Meta Description</label>
                        <textarea id="category-meta-description" name="meta_description" rows="3" class="input-field">{{ old('meta_description', $category->meta_description ?? '') }}</textarea>
                    </div>
                </div>
            </x-nino.entity-form-section>
        </div>

        <div class="form-sidebar">
            <x-nino.entity-form-section title="Category Controls" subtitle="Set category availability and review structural notes before saving.">
                <div class="space-y-4">
                    <label for="category-active" class="toggle-row">
                        <input type="checkbox" id="category-active" name="is_active" value="1" {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }} class="h-4 w-4 rounded border-[rgba(120,112,95,0.28)] text-[#245848] focus:ring-[#245848]/25">
                        Category is visible for editorial use and storefront linking
                    </label>

                    <div class="form-note">
                        Parent-child structure is best kept shallow. Use sort order for navigation placement instead of over-nesting the taxonomy.
                    </div>
                </div>

                <x-slot:footer>
                    <div class="form-actions">
                        <x-nino.button href="{{ route('admin.cms.categories.index') }}" variant="outline">Cancel</x-nino.button>
                        <x-nino.button type="submit" variant="primary">{{ $editing ? 'Update Category' : 'Create Category' }}</x-nino.button>
                    </div>
                </x-slot:footer>
            </x-nino.entity-form-section>
        </div>
    </form>
@endsection
