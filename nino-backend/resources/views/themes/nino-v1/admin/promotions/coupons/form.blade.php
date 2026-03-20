@extends('admin.layouts.app')

@section('title', $coupon->exists ? 'Edit Coupon' : 'New Coupon')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.promotions.coupons.index') }}" class="text-sm text-slate-900 hover:text-slate-900-dim">← Back to Coupons</a>
    <h1 class="text-2xl font-bold text-slate-900 mt-2">{{ $coupon->exists ? 'Edit: ' . $coupon->code : 'Create New Coupon' }}</h1>
</div>

@if($errors->any())
    <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm border border-red-200">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
@endif

<form action="{{ $coupon->exists ? route('admin.promotions.coupons.update', $coupon) : route('admin.promotions.coupons.store') }}" method="POST" class="space-y-6">
    @csrf
    @if($coupon->exists) @method('PUT') @endif

    {{-- Basic Info --}}
    <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Basic Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Coupon Code <span class="text-red-600">*</span></label>
                <input type="text" name="code" value="{{ old('code', $coupon->code ?? '') }}" {{ $coupon->exists ? 'readonly' : 'required' }} class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20 font-mono uppercase {{ $coupon->exists ? 'opacity-60' : '' }}" placeholder="SAVE20">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Name <span class="text-red-600">*</span></label>
                <input type="text" name="name" value="{{ old('name', $coupon->name ?? '') }}" required class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Summer Sale 20%">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Description</label>
                <input type="text" name="description" value="{{ old('description', $coupon->description ?? '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
            </div>
        </div>
    </div>

    {{-- Discount --}}
    <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Discount</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Type <span class="text-red-600">*</span></label>
                <select name="type" required class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
                    @foreach($types as $t)
                        <option value="{{ $t->value }}" {{ old('type', $coupon->type->value ?? 'percentage') === $t->value ? 'selected' : '' }}>{{ $t->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Value <span class="text-red-600">*</span></label>
                <input type="number" step="0.01" min="0.01" name="value" value="{{ old('value', $coupon->value ?? '') }}" required class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="20">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Max Discount (cap)</label>
                <input type="number" step="0.01" min="0" name="max_discount" value="{{ old('max_discount', $coupon->max_discount ?? '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="For % type only">
            </div>
        </div>
    </div>

    {{-- Schedule & Limits --}}
    <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Schedule & Limits</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Starts At</label>
                <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i') ?? '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Ends At</label>
                <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $coupon->ends_at?->format('Y-m-d\TH:i') ?? '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Total Usage Limit</label>
                <input type="number" min="1" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit ?? '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Unlimited">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Per User Limit</label>
                <input type="number" min="1" name="per_user_limit" value="{{ old('per_user_limit', $coupon->per_user_limit ?? '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="Unlimited">
            </div>
        </div>
    </div>

    {{-- Cart Requirements --}}
    <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Cart Requirements</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Minimum Cart Total</label>
                <input type="number" step="0.01" min="0" name="minimum_cart_total" value="{{ old('minimum_cart_total', $coupon->minimum_cart_total ?? '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="0.00">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Maximum Cart Total</label>
                <input type="number" step="0.01" min="0" name="maximum_cart_total" value="{{ old('maximum_cart_total', $coupon->maximum_cart_total ?? '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="No max">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Minimum Items</label>
                <input type="number" min="1" name="minimum_items" value="{{ old('minimum_items', $coupon->minimum_items ?? '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="No min">
            </div>
        </div>
    </div>

    {{-- Targeting --}}
    <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Product & Category Targeting</h2>
        <p class="text-xs text-slate-500 mb-3">Enter comma-separated IDs. Leave empty to apply to all.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Apply to Product IDs</label>
                <input type="text" name="product_ids_input" value="{{ old('product_ids_input', $coupon->exists && $coupon->product_ids ? implode(',', $coupon->product_ids) : '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="1,5,8">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Apply to Category IDs</label>
                <input type="text" name="category_ids_input" value="{{ old('category_ids_input', $coupon->exists && $coupon->category_ids ? implode(',', $coupon->category_ids) : '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="2,3">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Exclude Product IDs</label>
                <input type="text" name="excluded_product_ids_input" value="{{ old('excluded_product_ids_input', $coupon->exists && $coupon->excluded_product_ids ? implode(',', $coupon->excluded_product_ids) : '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="10,12">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Exclude Category IDs</label>
                <input type="text" name="excluded_category_ids_input" value="{{ old('excluded_category_ids_input', $coupon->exists && $coupon->excluded_category_ids ? implode(',', $coupon->excluded_category_ids) : '') }}" class="w-full rounded-lg border-slate-200 bg-background text-sm text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900/20" placeholder="5">
            </div>
        </div>
    </div>

    {{-- Options --}}
    <div class="bg-white border border-slate-200 rounded-md shadow-sm p-6">
        <div class="flex flex-wrap gap-6">
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $coupon->is_active ?? true) ? 'checked' : '' }} class="rounded border-slate-200 text-slate-900 focus:ring-slate-900/20">
                <label for="is_active" class="text-sm font-medium text-slate-900">Active</label>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_stackable" value="1" id="is_stackable" {{ old('is_stackable', $coupon->is_stackable ?? false) ? 'checked' : '' }} class="rounded border-slate-200 text-slate-900 focus:ring-slate-900/20">
                <label for="is_stackable" class="text-sm font-medium text-slate-900">Stackable with other coupons</label>
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="px-6 py-2.5 text-sm font-medium bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition-colors shadow-sm">
            {{ $coupon->exists ? 'Update Coupon' : 'Create Coupon' }}
        </button>
    </div>
</form>
@endsection
