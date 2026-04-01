@extends('admin.layouts.app')

@section('title', 'Edit Carrier')

@section('header')
    <x-nino.page-header
        title="Edit {{ $carrier->name }}"
        subtitle="Update operational settings, API credentials, and district sync readiness for this carrier.">
        <x-slot:actions>
            <x-nino.status-badge :tone="$carrier->is_enabled ? 'success' : 'neutral'">{{ $carrier->is_enabled ? 'Enabled' : 'Disabled' }}</x-nino.status-badge>
            <x-nino.button href="{{ route('admin.shipping.carriers.index') }}" variant="secondary" icon="arrow_back">Back to Carriers</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    @include('admin.shipping.carriers.partials.form')
@endsection
