@extends('admin.layouts.app')

@section('title', 'New Supplier')

@section('header')
    <x-nino.page-header
        title="New Supplier"
        subtitle="Register a new product supplier with the vendor contact details operations will actually use.">
        <x-slot:actions>
            <x-nino.button href="{{ route('admin.inventory.suppliers.index') }}" variant="secondary" icon="arrow_back">Back to Suppliers</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    <form action="{{ route('admin.inventory.suppliers.store') }}" method="POST">
        @csrf
        @include('admin.suppliers.form', [
            'submitLabel' => 'Create Supplier',
            'cancelUrl' => route('admin.inventory.suppliers.index'),
        ])
    </form>
@endsection
