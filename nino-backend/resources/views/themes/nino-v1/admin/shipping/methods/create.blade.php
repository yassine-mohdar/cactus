@extends('admin.layouts.app')

@section('title', isset($method) ? 'Edit Shipping Method' : 'New Shipping Method')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.shipping.methods.index') }}" class="text-sm text-slate-900 hover:text-slate-900-dim">← Back to Shipping Methods</a>
    <h1 class="text-2xl font-bold text-slate-900 mt-2">{{ isset($method) ? 'Edit: ' . $method->name : 'New Shipping Method' }}</h1>
</div>

@include('admin.shipping.methods.partials.form')
@endsection
