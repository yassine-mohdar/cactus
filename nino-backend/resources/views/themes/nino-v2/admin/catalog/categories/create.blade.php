@extends('admin.layouts.app')

@section('title', 'Create Category')

@section('header')
    <div class="page-header">
        <div>
            <a href="{{ route('admin.catalog.categories.index') }}" class="inline-flex items-center gap-1 text-xs font-bold uppercase tracking-[0.18em] text-[#617169] transition-colors hover:text-[#17302A]">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                Back to Categories
            </a>
            <h1 class="page-title mt-3">Create Category</h1>
            <p class="page-subtitle">Create a clean taxonomy node with hierarchy, SEO metadata, and visibility controls for the storefront.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="datatable-meta">Taxonomy Setup</span>
            <span class="datatable-meta">Top Level or Nested</span>
        </div>
    </div>
@endsection

@section('content')
    <form action="{{ route('admin.catalog.categories.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @include('admin.catalog.categories.form')
    </form>
@endsection
