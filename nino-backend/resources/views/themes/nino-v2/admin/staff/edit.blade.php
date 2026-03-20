@extends('admin.layouts.app')

@section('title', 'Edit Staff Member')

@php
    $staffStatus = old('status', $staff->status);
@endphp

@section('header')
    <div class="page-header">
        <div>
            <a href="{{ route('admin.staff.index') }}" class="inline-flex items-center gap-1 text-xs font-bold uppercase tracking-[0.18em] text-[#617169] transition-colors hover:text-[#17302A]">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                Back to Staff
            </a>
            <h1 class="page-title mt-3">Edit Staff Member</h1>
            <p class="page-subtitle">Update access, scope, and security settings for <span class="font-semibold text-[#17302A]">{{ $staff->name }}</span>.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="datatable-meta">Staff ID {{ $staff->id }}</span>
            <span class="inline-flex items-center rounded-full border border-[rgba(36,88,72,0.16)] bg-[#E7F0EA] px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-[#245848]">{{ \Illuminate\Support\Str::headline($staffStatus) }}</span>
        </div>
    </div>
@endsection

@section('content')
    <form action="{{ route('admin.staff.update', $staff) }}" method="POST">
        @csrf
        @method('PUT')

        @include('admin.staff.form')
    </form>
@endsection
