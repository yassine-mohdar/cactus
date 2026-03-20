@extends('admin.layouts.app')

@section('title', 'Create Product')

@section('header')
    <div class="page-header">
        <div>
            <a href="{{ route('admin.catalog.products.index') }}" class="inline-flex items-center gap-1 text-xs font-bold uppercase tracking-[0.18em] text-[#617169] transition-colors hover:text-[#17302A]">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                Back to Products
            </a>
            <h1 class="page-title mt-3">Create Product</h1>
            <p class="page-subtitle">Launch a new catalog record with pricing, stock logic, and merchandising metadata aligned to the v2 storefront.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="datatable-meta">Catalog Setup</span>
            <span class="datatable-meta">Draft by Default</span>
        </div>
    </div>
@endsection

@section('content')
    <form action="{{ route('admin.catalog.products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @include('admin.catalog.products.form')
    </form>
@endsection
