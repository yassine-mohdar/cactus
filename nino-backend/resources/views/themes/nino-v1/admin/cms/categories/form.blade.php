@extends('admin.layouts.app')
@section('title', isset($category) ? 'Edit Category' : 'New Category')
@section('content')
<div class="mb-6">
    <a href="{{ route('admin.cms.categories.index') }}" class="text-sm text-slate-900 hover:text-slate-900-dim">← Back to Categories</a>
    <h1 class="text-2xl font-bold text-slate-900 mt-2">{{ isset($category) ? 'Edit: ' . $category->name : 'Create Blog Category' }}</h1>
</div>
@if($errors->any())<div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm border border-red-200">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>@endif
<form action="{{ isset($category) ? route('admin.cms.categories.update', $category) : route('admin.cms.categories.store') }}" method="POST" class="space-y-6">
    @csrf @if(isset($category)) @method('PUT') @endif
    <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">General</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block text-xs font-semibold text-slate-500 mb-1">Name <span class="text-red-600">*</span></label><input type="text" name="name" value="{{ old('name', $category->name ?? '') }}" required class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20"></div>
            <div><label class="block text-xs font-semibold text-slate-500 mb-1">Slug</label><input type="text" name="slug" value="{{ old('slug', $category->slug ?? '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Auto-generated"></div>
            <div><label class="block text-xs font-semibold text-slate-500 mb-1">Parent</label>
                <select name="parent_id" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                    <option value="">— None (Root) —</option>
                    @foreach($parents as $p)<option value="{{ $p->id }}" {{ old('parent_id', $category->parent_id ?? '') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>@endforeach
                </select>
            </div>
            <div><label class="block text-xs font-semibold text-slate-500 mb-1">Sort Order</label><input type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" min="0" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20"></div>
            <div class="md:col-span-2"><label class="block text-xs font-semibold text-slate-500 mb-1">Description</label><textarea name="description" rows="2" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">{{ old('description', $category->description ?? '') }}</textarea></div>
            <div class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }} class="rounded border-slate-200 text-slate-900 focus:ring-slate-900/20"><label for="is_active" class="text-sm font-medium text-slate-900">Active</label></div>
        </div>
    </div>
    <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">SEO</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block text-xs font-semibold text-slate-500 mb-1">Meta Title</label><input type="text" name="meta_title" value="{{ old('meta_title', $category->meta_title ?? '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20"></div>
            <div><label class="block text-xs font-semibold text-slate-500 mb-1">Canonical URL</label><input type="url" name="canonical_url" value="{{ old('canonical_url', $category->canonical_url ?? '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20"></div>
            <div class="md:col-span-2"><label class="block text-xs font-semibold text-slate-500 mb-1">Meta Description</label><textarea name="meta_description" rows="2" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">{{ old('meta_description', $category->meta_description ?? '') }}</textarea></div>
        </div>
    </div>
    <div class="flex justify-end"><button type="submit" class="px-6 py-2.5 text-sm font-medium bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition-colors shadow-sm">{{ isset($category) ? 'Update' : 'Create' }}</button></div>
</form>
@endsection
