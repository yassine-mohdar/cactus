@extends('admin.layouts.app')

@section('title', 'Edit Supplier: ' . $supplier->name)

@section('header')
    <x-nino.page-header
        title="Edit {{ $supplier->name }}"
        subtitle="Update supplier contact coverage and operational status without changing inventory routing contracts.">
        <x-slot:actions>
            <x-nino.status-badge :tone="$supplier->status === 'active' ? 'success' : 'neutral'">{{ ucfirst($supplier->status) }}</x-nino.status-badge>
            <x-nino.button href="{{ route('admin.inventory.suppliers.show', $supplier) }}" variant="secondary" icon="arrow_back">Back to Supplier</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    <form action="{{ route('admin.inventory.suppliers.update', $supplier) }}" method="POST">
        @csrf
        @method('PUT')
        @include('admin.suppliers.form', [
            'submitLabel' => 'Update Supplier',
            'cancelUrl' => route('admin.inventory.suppliers.show', $supplier),
        ])
    </form>
@endsection
