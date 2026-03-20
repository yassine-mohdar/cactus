@extends('admin.layouts.app')
@section('title', isset($post) ? 'Edit Post' : 'New Post')
@section('content')
<div class="mb-6">
    <a href="{{ route('admin.cms.posts.index') }}" class="text-sm text-slate-900 hover:text-slate-900-dim">← Back to Posts</a>
    <h1 class="text-2xl font-bold text-slate-900 mt-2">{{ isset($post) ? 'Edit: ' . $post->title : 'Create New Post' }}</h1>
</div>
@if($errors->any())<div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm border border-red-200">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>@endif

<form action="{{ isset($post) ? route('admin.cms.posts.update', $post) : route('admin.cms.posts.store') }}" method="POST" class="space-y-6">
    @csrf @if(isset($post)) @method('PUT') @endif

    {{-- Content --}}
    <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Content</h2>
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="block text-xs font-semibold text-slate-500 mb-1">Title <span class="text-red-600">*</span></label><input type="text" name="title" value="{{ old('title', $post->title ?? '') }}" required class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20"></div>
                <div><label class="block text-xs font-semibold text-slate-500 mb-1">Slug</label><input type="text" name="slug" value="{{ old('slug', $post->slug ?? '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Auto-generated"></div>
            </div>
            <div><label class="block text-xs font-semibold text-slate-500 mb-1">Excerpt</label><textarea name="excerpt" rows="2" maxlength="500" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">{{ old('excerpt', $post->excerpt ?? '') }}</textarea></div>
            <div><label class="block text-xs font-semibold text-slate-500 mb-1">Body <span class="text-red-600">*</span></label><textarea name="body" rows="12" required class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20 font-mono">{{ old('body', $post->body ?? '') }}</textarea></div>
            <div><label class="block text-xs font-semibold text-slate-500 mb-1">Featured Image URL</label><input type="text" name="featured_image" value="{{ old('featured_image', $post->featured_image ?? '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="/storage/blog/hero.jpg"></div>
        </div>
    </div>

    {{-- Publishing --}}
    <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Publishing</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div><label class="block text-xs font-semibold text-slate-500 mb-1">Status <span class="text-red-600">*</span></label>
                <select name="status" required class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                    @foreach(\App\Modules\Cms\Enums\PostStatus::cases() as $s)<option value="{{ $s->value }}" {{ old('status', $post->status->value ?? 'draft') === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>@endforeach
                </select>
            </div>
            <div><label class="block text-xs font-semibold text-slate-500 mb-1">Category</label>
                <select name="blog_category_id" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                    <option value="">— None —</option>
                    @foreach($categories as $c)<option value="{{ $c->id }}" {{ old('blog_category_id', $post->blog_category_id ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach
                </select>
            </div>
            <div><label class="block text-xs font-semibold text-slate-500 mb-1">Schedule For</label><input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', isset($post) && $post->scheduled_at ? $post->scheduled_at->format('Y-m-d\TH:i') : '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20"></div>
        </div>
        {{-- Tags --}}
        <div class="mt-4">
            <label class="block text-xs font-semibold text-slate-500 mb-2">Tags</label>
            <div class="flex flex-wrap gap-2">
                @php $selectedTags = old('tag_ids', isset($post) ? $post->tags->pluck('id')->toArray() : []); @endphp
                @foreach($tags as $tag)
                    <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-slate-200 text-xs font-medium cursor-pointer hover:bg-slate-100 transition-colors has-[:checked]:bg-slate-200 has-[:checked]:border-slate-900">
                        <input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}" {{ in_array($tag->id, $selectedTags) ? 'checked' : '' }} class="sr-only">
                        {{ $tag->name }}
                    </label>
                @endforeach
            </div>
        </div>
    </div>

    {{-- SEO --}}
    <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">SEO & Open Graph</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block text-xs font-semibold text-slate-500 mb-1">Meta Title</label><input type="text" name="meta_title" value="{{ old('meta_title', $post->meta_title ?? '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Defaults to post title"></div>
            <div><label class="block text-xs font-semibold text-slate-500 mb-1">Canonical URL</label><input type="url" name="canonical_url" value="{{ old('canonical_url', $post->canonical_url ?? '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20"></div>
            <div class="md:col-span-2"><label class="block text-xs font-semibold text-slate-500 mb-1">Meta Description</label><textarea name="meta_description" rows="2" maxlength="300" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">{{ old('meta_description', $post->meta_description ?? '') }}</textarea></div>
            <div><label class="block text-xs font-semibold text-slate-500 mb-1">OG Title</label><input type="text" name="og_title" value="{{ old('og_title', $post->og_title ?? '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20"></div>
            <div><label class="block text-xs font-semibold text-slate-500 mb-1">OG Image URL</label><input type="text" name="og_image" value="{{ old('og_image', $post->og_image ?? '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20"></div>
            <div class="md:col-span-2"><label class="block text-xs font-semibold text-slate-500 mb-1">OG Description</label><textarea name="og_description" rows="2" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">{{ old('og_description', $post->og_description ?? '') }}</textarea></div>
            <div class="flex items-center gap-2"><input type="checkbox" name="noindex" value="1" id="noindex" {{ old('noindex', $post->noindex ?? false) ? 'checked' : '' }} class="rounded border-slate-200 text-slate-900 focus:ring-slate-900/20"><label for="noindex" class="text-sm font-medium text-slate-900">Noindex (hide from search engines)</label></div>
        </div>
    </div>

    <div class="flex justify-end gap-3">
        @if(isset($post))<a href="{{ route('admin.cms.posts.preview', $post) }}" class="px-4 py-2.5 text-sm font-medium border border-slate-200 text-slate-900 rounded-lg hover:bg-white-dim transition-colors" target="_blank">Preview</a>@endif
        <button type="submit" class="px-6 py-2.5 text-sm font-medium bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition-colors shadow-sm">{{ isset($post) ? 'Update Post' : 'Create Post' }}</button>
    </div>
</form>
@endsection
