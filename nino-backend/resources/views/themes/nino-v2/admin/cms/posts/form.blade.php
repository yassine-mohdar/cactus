@extends('admin.layouts.app')

@php
    $editing = isset($post);
    $selectedTags = old('tag_ids', $editing ? $post->tags->pluck('id')->toArray() : []);
    $mediaOptions = $mediaOptions ?? collect();
@endphp

@section('title', $editing ? 'Edit Post' : 'New Post')

@section('header')
    <x-nino.page-header
        title="{{ $editing ? 'Edit '.$post->title : 'Create New Post' }}"
        subtitle="Write, schedule, and optimize editorial content without leaving the CMS workspace.">
        <x-slot:actions>
            @if($editing)
                <x-nino.button href="{{ route('admin.cms.posts.preview', $post) }}" variant="secondary" icon="visibility">Preview</x-nino.button>
            @endif
            <x-nino.button href="{{ route('admin.cms.posts.index') }}" variant="secondary" icon="arrow_back">Back to Posts</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    @if($errors->any())
        <x-nino.inline-alert tone="danger" title="Please fix the highlighted fields" class="mb-6">
            {{ collect($errors->all())->join(' ') }}
        </x-nino.inline-alert>
    @endif

    <form action="{{ $editing ? route('admin.cms.posts.update', $post) : route('admin.cms.posts.store') }}" method="POST" class="form-layout">
        @csrf
        @if($editing)
            @method('PUT')
        @endif

        <div class="form-main">
            <x-nino.entity-form-section title="Content" subtitle="Define the article headline, excerpt, body, and featured asset.">
                <div class="space-y-4">
                    <div class="entity-section-grid">
                        <div>
                            <label class="filter-label" for="post-title">Title</label>
                            <input id="post-title" type="text" name="title" value="{{ old('title', $post->title ?? '') }}" required class="input-field">
                        </div>
                        <div>
                            <label class="filter-label" for="post-slug">Slug</label>
                            <input id="post-slug" type="text" name="slug" value="{{ old('slug', $post->slug ?? '') }}" class="input-field" placeholder="Auto-generated if left blank">
                        </div>
                    </div>
                    <div>
                        <label class="filter-label" for="post-excerpt">Excerpt</label>
                        <textarea id="post-excerpt" name="excerpt" rows="3" maxlength="500" class="input-field">{{ old('excerpt', $post->excerpt ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="filter-label" for="post-body">Body</label>
                        <textarea id="post-body" name="body" rows="16" required class="input-field font-mono text-xs">{{ old('body', $post->body ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="filter-label" for="post-featured-image">Featured Image URL</label>
                        <input id="post-featured-image" type="text" name="featured_image" value="{{ old('featured_image', $post->featured_image ?? '') }}" class="input-field" placeholder="/storage/blog/hero.jpg" list="cms-media-library">
                    </div>
                    <datalist id="cms-media-library">
                        @foreach($mediaOptions as $option)
                            <option value="{{ $option['url'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </datalist>
                </div>
            </x-nino.entity-form-section>

            <x-nino.entity-form-section title="Publishing" subtitle="Control status, publication timing, category, and tag placement.">
                <div class="space-y-4">
                    <div class="entity-section-grid">
                        <div>
                            <label class="filter-label" for="post-status">Status</label>
                            <select id="post-status" name="status" required class="input-field">
                                @foreach(\App\Modules\Cms\Enums\PostStatus::cases() as $s)
                                    <option value="{{ $s->value }}" {{ old('status', $post->status->value ?? 'draft') === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="filter-label" for="post-category">Category</label>
                            <select id="post-category" name="blog_category_id" class="input-field">
                                <option value="">Uncategorized</option>
                                @foreach($categories as $c)
                                    <option value="{{ $c->id }}" {{ old('blog_category_id', $post->blog_category_id ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="filter-label" for="post-scheduled-at">Schedule For</label>
                            <input id="post-scheduled-at" type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', isset($post) && $post->scheduled_at ? $post->scheduled_at->format('Y-m-d\\TH:i') : '') }}" class="input-field">
                        </div>
                    </div>

                    <div>
                        <p class="filter-label">Tags</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($tags as $tag)
                                <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-[rgba(120,112,95,0.14)] bg-[#FBFAF7] px-3 py-2 text-xs font-semibold text-[#1E2B27] transition hover:bg-[#FCFBF8] has-[:checked]:border-[#245848]/25 has-[:checked]:bg-[#ECF4EE]">
                                    <input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}" {{ in_array($tag->id, $selectedTags) ? 'checked' : '' }} class="sr-only">
                                    {{ $tag->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </x-nino.entity-form-section>

            <x-nino.entity-form-section title="SEO and Open Graph" subtitle="Set the search and social metadata used for this article.">
                <div class="space-y-4">
                    <div class="entity-section-grid">
                        <div>
                            <label class="filter-label" for="post-meta-title">Meta Title</label>
                            <input id="post-meta-title" type="text" name="meta_title" value="{{ old('meta_title', $post->meta_title ?? '') }}" class="input-field">
                        </div>
                        <div>
                            <label class="filter-label" for="post-canonical-url">Canonical URL</label>
                            <input id="post-canonical-url" type="url" name="canonical_url" value="{{ old('canonical_url', $post->canonical_url ?? '') }}" class="input-field">
                        </div>
                        <div class="md:col-span-2">
                            <label class="filter-label" for="post-meta-description">Meta Description</label>
                            <textarea id="post-meta-description" name="meta_description" rows="3" maxlength="300" class="input-field">{{ old('meta_description', $post->meta_description ?? '') }}</textarea>
                        </div>
                        <div>
                            <label class="filter-label" for="post-og-title">OG Title</label>
                            <input id="post-og-title" type="text" name="og_title" value="{{ old('og_title', $post->og_title ?? '') }}" class="input-field">
                        </div>
                        <div>
                            <label class="filter-label" for="post-og-image">OG Image URL</label>
                            <input id="post-og-image" type="text" name="og_image" value="{{ old('og_image', $post->og_image ?? '') }}" class="input-field" list="cms-media-library">
                        </div>
                        <div class="md:col-span-2">
                            <label class="filter-label" for="post-og-description">OG Description</label>
                            <textarea id="post-og-description" name="og_description" rows="3" class="input-field">{{ old('og_description', $post->og_description ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </x-nino.entity-form-section>
        </div>

        <div class="form-sidebar">
            <x-nino.entity-form-section title="Publication Controls" subtitle="Final checks before saving the article to the editorial pipeline.">
                <div class="space-y-4">
                    <label for="post-noindex" class="toggle-row">
                        <input type="checkbox" id="post-noindex" name="noindex" value="1" {{ old('noindex', $post->noindex ?? false) ? 'checked' : '' }} class="h-4 w-4 rounded border-[rgba(120,112,95,0.28)] text-[#245848] focus:ring-[#245848]/25">
                        Hide this article from search engines
                    </label>

                    @if($editing)
                        <x-nino.entity-detail-grid>
                            <div>
                                <p class="detail-kicker">Current Status</p>
                                <p class="detail-value">{{ $post->status->label() }}</p>
                            </div>
                            <div>
                                <p class="detail-kicker">Views</p>
                                <p class="detail-value-mono">{{ number_format((int) $post->views_count) }}</p>
                            </div>
                        </x-nino.entity-detail-grid>
                    @endif

                    <div class="form-note">
                        Use the preview button before publishing scheduled or customer-facing editorial changes. Search metadata falls back to the title and excerpt when left blank.
                    </div>
                </div>

                <x-slot:footer>
                    <div class="form-actions">
                        <x-nino.button href="{{ route('admin.cms.posts.index') }}" variant="outline">Cancel</x-nino.button>
                        <x-nino.button type="submit" variant="primary">{{ $editing ? 'Update Post' : 'Create Post' }}</x-nino.button>
                    </div>
                </x-slot:footer>
            </x-nino.entity-form-section>
        </div>
    </form>
@endsection
