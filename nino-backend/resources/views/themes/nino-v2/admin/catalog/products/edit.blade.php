@extends('admin.layouts.app')

@section('title', 'Edit Product')

@php
    $productStatus = old('status', $product->status);
@endphp

@section('header')
    <div class="page-header">
        <div>
            <a href="{{ route('admin.catalog.products.index') }}" class="inline-flex items-center gap-1 text-xs font-bold uppercase tracking-[0.18em] text-[#617169] transition-colors hover:text-[#17302A]">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                Back to Products
            </a>
            <h1 class="page-title mt-3">Edit Product</h1>
            <p class="page-subtitle">Update pricing, inventory logic, and merchandising details for <span class="font-semibold text-[#17302A]">{{ $product->name }}</span>.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="datatable-meta">Product ID {{ $product->id }}</span>
            <span class="inline-flex items-center rounded-full border border-[rgba(36,88,72,0.16)] bg-[#E7F0EA] px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-[#245848]">{{ \Illuminate\Support\Str::headline($productStatus) }}</span>
        </div>
    </div>
@endsection

@section('content')
    <form action="{{ route('admin.catalog.products.update', $product) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.catalog.products.form')
    </form>
@endsection
