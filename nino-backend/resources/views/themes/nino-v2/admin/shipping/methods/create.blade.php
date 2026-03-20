@extends('admin.layouts.app')

@section('title', 'New Shipping Method')

@section('header')
    <x-nino.page-header
        title="New Shipping Method"
        subtitle="Create a customer-facing delivery option with clear pricing, SLA, and carrier mapping.">
        <x-slot:actions>
            <x-nino.button href="{{ route('admin.shipping.methods.index') }}" variant="secondary" icon="arrow_back">Back to Methods</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    @include('admin.shipping.methods.partials.form')
@endsection
