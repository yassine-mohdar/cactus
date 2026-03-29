@extends('admin.layouts.app')

@section('header')
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.staff.index') }}" class="text-sm text-gray-500 hover:text-sage mb-1 block">&larr; Back to Staff</a>
            <h1 class="text-2xl font-bold text-ink">Edit Staff: {{ $staff->name }}</h1>
        </div>
        <div class="flex items-center gap-3">
            @can('viewAny', \Spatie\Permission\Models\Role::class)
                <a href="{{ route('admin.staff.roles.index') }}" class="btn-secondary">Manage Roles</a>
            @endcan
            @can('update', $staff)
                <form action="{{ route('admin.staff.access-link.store', $staff) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-secondary">Send Access Setup Link</button>
                </form>
                <form action="{{ route('admin.staff.sessions.revoke', $staff) }}" method="POST" onsubmit="return confirm('Revoke all active sessions for this staff member?');">
                    @csrf
                    <button type="submit" class="btn-secondary">Revoke Sessions</button>
                </form>
            @endcan
        </div>
    </div>
@endsection

@section('content')
    <div class="bg-white rounded-md border border-gray-200 shadow-sm max-w-3xl">
        <form action="{{ route('admin.staff.update', $staff) }}" method="POST" class="p-6 md:p-8">
            @csrf
            @method('PUT')

            @include('admin.staff.form')
        </form>
    </div>
@endsection
