@extends('admin.layouts.app')

@section('title', 'Edit Carrier')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.shipping.carriers.index') }}" class="text-sm text-slate-900 hover:text-slate-900-dim">← Back to Carriers</a>
    <h1 class="text-2xl font-bold text-slate-900 mt-2">Edit: {{ $carrier->name }}</h1>
</div>

@include('admin.shipping.carriers.partials.form')
@endsection
