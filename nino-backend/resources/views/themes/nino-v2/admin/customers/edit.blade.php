@extends('admin.layouts.app')

@section('title', 'Edit Customer: ' . $customer->name)

@section('header')
    <x-nino.page-header
        title="Edit {{ $customer->name }}"
        subtitle="Update personal details, account status, and credentials without losing the linked order history.">
        <x-slot:actions>
            <x-nino.status-badge :tone="$customer->status === 'active' ? 'success' : 'danger'">{{ ucfirst($customer->status) }}</x-nino.status-badge>
            <x-nino.button href="{{ route('admin.customers.show', $customer) }}" variant="secondary" icon="arrow_back">Back to Profile</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    <form action="{{ route('admin.customers.update', $customer) }}" method="POST">
        @csrf
        @method('PUT')
        @include('admin.customers.form', [
            'submitLabel' => 'Update Customer',
            'cancelUrl' => route('admin.customers.show', $customer),
        ])
    </form>
@endsection
