@extends('admin.layouts.app')

@section('header')
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.staff.roles.index') }}" class="text-sm text-gray-500 hover:text-sage mb-1 block">&larr; Back to Roles</a>
            <h1 class="text-2xl font-bold text-ink">Create Custom Role</h1>
        </div>
    </div>
@endsection

@section('content')
    <div class="bg-white rounded-md border border-gray-200 shadow-sm max-w-4xl">
        <form action="{{ route('admin.staff.roles.store') }}" method="POST" class="p-6 md:p-8">
            @csrf

            @include('admin.staff.roles.form')
        </form>
    </div>
@endsection
