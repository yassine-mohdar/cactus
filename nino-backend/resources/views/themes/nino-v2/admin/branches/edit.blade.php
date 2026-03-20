@extends('admin.layouts.app')

@section('title', 'Edit Branch: ' . $branch->name)

@section('header')
    <x-nino.page-header
        title="Edit {{ $branch->name }}"
        subtitle="Update branch contact details, operational status, and physical location without changing downstream routes.">
        <x-slot:actions>
            <x-nino.status-badge :tone="$branch->status === 'active' ? 'success' : 'neutral'">{{ ucfirst($branch->status) }}</x-nino.status-badge>
            <x-nino.button href="{{ route('admin.inventory.branches.show', $branch) }}" variant="secondary" icon="arrow_back">Back to Branch</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    <form action="{{ route('admin.inventory.branches.update', $branch) }}" method="POST">
        @csrf
        @method('PUT')
        @include('admin.branches.form', [
            'submitLabel' => 'Update Branch',
            'cancelUrl' => route('admin.inventory.branches.show', $branch),
        ])
    </form>
@endsection
