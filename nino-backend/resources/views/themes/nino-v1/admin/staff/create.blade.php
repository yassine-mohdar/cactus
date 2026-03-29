@extends('admin.layouts.app')

@section('header')
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.staff.index') }}" class="text-sm text-gray-500 hover:text-sage mb-1 block">&larr; Back to Staff</a>
            <h1 class="text-2xl font-bold text-ink">Add Staff Member</h1>
        </div>
        @can('viewAny', \Spatie\Permission\Models\Role::class)
            <a href="{{ route('admin.staff.roles.index') }}" class="btn-secondary">Manage Roles</a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="bg-white rounded-md border border-gray-200 shadow-sm max-w-3xl">
        <form action="{{ route('admin.staff.store') }}" method="POST" class="p-6 md:p-8">
            @csrf

            @include('admin.staff.form')
        </form>
    </div>
@endsection
