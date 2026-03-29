@extends('admin.layouts.app')

@section('title', 'Edit Category')

@php
    $categoryIsActive = old('is_active', $category->is_active);
@endphp

@section('header')
    <div class="page-header">
        <div>
            <a href="{{ route('admin.catalog.categories.index') }}" class="inline-flex items-center gap-1 text-xs font-bold uppercase tracking-[0.18em] text-[#617169] transition-colors hover:text-[#17302A]">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                Back to Categories
            </a>
            <h1 class="page-title mt-3">Edit Category</h1>
            <p class="page-subtitle">Update hierarchy, media, and metadata for <span class="font-semibold text-[#17302A]">{{ $category->name }}</span> without losing taxonomy structure.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="datatable-meta">Category ID {{ $category->id }}</span>
            <span class="inline-flex items-center rounded-full border border-[rgba(36,88,72,0.16)] bg-[#E7F0EA] px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-[#245848]">{{ $categoryIsActive ? 'Active' : 'Hidden' }}</span>
        </div>
    </div>
@endsection

@section('content')
    <form action="{{ route('admin.catalog.categories.update', $category) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.catalog.categories.form')
    </form>
@endsection
