@extends('admin.layouts.app')

@section('title', 'New Customer')

@section('header')
    <x-nino.page-header
        title="Add New Customer"
        subtitle="Create a manual customer account for support, order management, and fulfillment follow-up.">
        <x-slot:actions>
            <x-nino.button href="{{ route('admin.customers.index') }}" variant="secondary" icon="arrow_back">Back to Customers</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    <form action="{{ route('admin.customers.store') }}" method="POST">
        @csrf
        @include('admin.customers.form', [
            'submitLabel' => 'Create Customer',
            'cancelUrl' => route('admin.customers.index'),
        ])
    </form>
@endsection
