@extends('admin.layouts.app')

@section('title', isset($method) ? 'Edit Shipping Method' : 'New Shipping Method')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.shipping.methods.index') }}" class="text-sm text-slate-900 hover:text-slate-900-dim">← Back to Shipping Methods</a>
    <h1 class="text-2xl font-bold text-slate-900 mt-2">{{ isset($method) ? 'Edit: ' . $method->name : 'New Shipping Method' }}</h1>
</div>

<form action="{{ isset($method) ? route('admin.shipping.methods.update', $method) : route('admin.shipping.methods.store') }}" method="POST" class="bg-white border border-slate-200 rounded-md shadow-sm p-6 max-w-2xl space-y-5">
    @csrf
    @if(isset($method)) @method('PUT') @endif

    <div>
        <label class="block text-sm font-semibold text-slate-900 mb-1">Name <span class="text-red-600">*</span></label>
        <input type="text" name="name" value="{{ old('name', $method->name ?? '') }}" required class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Standard Delivery">
        @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-900 mb-1">Carrier</label>
        <input type="text" name="carrier" value="{{ old('carrier', $method->carrier ?? '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Amana, DHL, Chronopost...">
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-900 mb-1">Description</label>
        <textarea name="description" rows="2" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Brief description shown to customers">{{ old('description', $method->description ?? '') }}</textarea>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-semibold text-slate-900 mb-1">Base Cost (MAD) <span class="text-red-600">*</span></label>
            <input type="number" step="0.01" name="base_cost" value="{{ old('base_cost', $method->base_cost ?? '0.00') }}" required class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-900 mb-1">Free Shipping Above (MAD)</label>
            <input type="number" step="0.01" name="free_shipping_threshold" value="{{ old('free_shipping_threshold', $method->free_shipping_threshold ?? '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Leave blank for none">
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-semibold text-slate-900 mb-1">Estimated Delivery</label>
            <input type="text" name="estimated_days" value="{{ old('estimated_days', $method->estimated_days ?? '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="2-4 business days">
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-900 mb-1">Sort Order</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $method->sort_order ?? 0) }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
        </div>
    </div>

    <div class="flex items-center gap-3">
        <input type="checkbox" name="is_enabled" value="1" id="is_enabled" {{ old('is_enabled', $method->is_enabled ?? true) ? 'checked' : '' }} class="rounded border-slate-200 text-slate-900 focus:ring-slate-900/20">
        <label for="is_enabled" class="text-sm font-medium text-slate-900">Enabled</label>
    </div>

    <div class="flex justify-end pt-2">
        <button type="submit" class="px-5 py-2 text-sm font-medium bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition-colors shadow-sm">
            {{ isset($method) ? 'Update Method' : 'Create Method' }}
        </button>
    </div>
</form>
@endsection
