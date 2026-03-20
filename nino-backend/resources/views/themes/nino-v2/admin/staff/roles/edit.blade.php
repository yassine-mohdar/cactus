@extends('admin.layouts.app')

@section('title', 'Edit Role')
@section('header')
    <x-nino.page-header
        title="Edit Role"
        subtitle="Refine permissions for {{ $role->name }} without over-widening access.">
        <x-slot:actions>
            <x-nino.button href="{{ route('admin.staff.roles.index') }}" variant="secondary" icon="arrow_back">Back to Roles</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    <form action="{{ route('admin.staff.roles.update', $role) }}" method="POST">
        @csrf
        @method('PUT')

        @include('admin.staff.roles.form')
    </form>
@endsection
