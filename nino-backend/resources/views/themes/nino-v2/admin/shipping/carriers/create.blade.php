@extends('admin.layouts.app')

@section('title', 'New Carrier')

@section('header')
    <x-nino.page-header
        title="New Carrier"
        subtitle="Add a carrier registry record for manual routing or Sendit API operations.">
        <x-slot:actions>
            <x-nino.button href="{{ route('admin.shipping.carriers.index') }}" variant="secondary" icon="arrow_back">Back to Carriers</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
    @include('admin.shipping.carriers.partials.form')
@endsection
