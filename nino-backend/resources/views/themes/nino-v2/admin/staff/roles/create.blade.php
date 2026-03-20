@extends('admin.layouts.app')

@section('title', 'Create Custom Role')
@section('header')
    <x-nino.page-header
        title="Create Custom Role"
        subtitle="Build a reusable permission profile for a specific operational responsibility.">
        <x-slot:actions>
            <x-nino.button href="{{ route('admin.staff.roles.index') }}" variant="secondary" icon="arrow_back">Back to Roles</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    <form action="{{ route('admin.staff.roles.store') }}" method="POST">
        @csrf

        @include('admin.staff.roles.form')
    </form>
@endsection
