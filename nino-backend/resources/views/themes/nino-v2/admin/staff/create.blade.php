@extends('admin.layouts.app')

@section('title', 'Create Staff Member')

@section('header')
    <div class="page-header">
        <div>
            <a href="{{ route('admin.staff.index') }}" class="inline-flex items-center gap-1 text-xs font-bold uppercase tracking-[0.18em] text-[#617169] transition-colors hover:text-[#17302A]">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                Back to Staff
            </a>
            <h1 class="page-title mt-3">Create Staff Member</h1>
            <p class="page-subtitle">Provision a new operations teammate with the right roles, scope, and account security from the start.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="datatable-meta">Operations Access</span>
            <span class="datatable-meta">Role Driven</span>
        </div>
    </div>
@endsection

@section('content')
    <form action="{{ route('admin.staff.store') }}" method="POST">
        @csrf

        @include('admin.staff.form')
    </form>
@endsection
