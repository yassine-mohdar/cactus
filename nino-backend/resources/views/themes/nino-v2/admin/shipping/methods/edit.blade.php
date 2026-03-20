@extends('admin.layouts.app')

@section('title', 'Edit Shipping Method')

@section('header')
    <x-nino.page-header
        title="Edit {{ $method->name }}"
        subtitle="Update pricing, availability, and SLA details without changing existing route contracts.">
        <x-slot:actions>
            <x-nino.status-badge :tone="$method->is_enabled ? 'success' : 'neutral'">{{ $method->is_enabled ? 'Enabled' : 'Disabled' }}</x-nino.status-badge>
            <x-nino.button href="{{ route('admin.shipping.methods.index') }}" variant="secondary" icon="arrow_back">Back to Methods</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    @include('admin.shipping.methods.partials.form')
@endsection
