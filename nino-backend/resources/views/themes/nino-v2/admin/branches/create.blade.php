@extends('admin.layouts.app')

@section('title', 'New Branch')

@section('header')
    <x-nino.page-header
        title="New Branch"
        subtitle="Register a new physical branch or warehouse location for stock routing and fulfillment.">
        <x-slot:actions>
            <x-nino.button href="{{ route('admin.inventory.branches.index') }}" variant="secondary" icon="arrow_back">Back to Branches</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    <form action="{{ route('admin.inventory.branches.store') }}" method="POST">
        @csrf
        @include('admin.branches.form', [
            'submitLabel' => 'Create Branch',
            'cancelUrl' => route('admin.inventory.branches.index'),
        ])
    </form>
@endsection
