@extends('admin.layouts.app')

@section('title', $coupon->exists ? 'Edit Coupon' : 'New Coupon')

@section('header')
    <x-nino.page-header
        title="{{ $coupon->exists ? 'Edit '.$coupon->code : 'Create New Coupon' }}"
        subtitle="Build discount campaigns with explicit rules for timing, targeting, and order qualification.">
        <x-slot:actions>
            <x-nino.button href="{{ route('admin.promotions.coupons.index') }}" variant="secondary" icon="arrow_back">Back to Coupons</x-nino.button>
        </x-slot:actions>
    </x-nino.page-header>
@endsection

@section('content')
@if($errors->any())
    <x-nino.inline-alert tone="danger" title="Please fix the highlighted fields" class="mb-6">
        {{ collect($errors->all())->join(' ') }}
    </x-nino.inline-alert>
@endif

<form action="{{ $coupon->exists ? route('admin.promotions.coupons.update', $coupon) : route('admin.promotions.coupons.store') }}" method="POST" class="form-layout">
    @csrf
    @if($coupon->exists) @method('PUT') @endif

    <div class="form-main">
    <x-nino.entity-form-section title="Basic Information" subtitle="Set the campaign code, internal name, and discount model for this promotion.">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label class="filter-label" for="coupon-code">Coupon Code</label>
                <input id="coupon-code" type="text" name="code" value="{{ old('code', $coupon->code ?? '') }}" {{ $coupon->exists ? 'readonly' : 'required' }} class="input-field font-mono uppercase {{ $coupon->exists ? 'opacity-60' : '' }}" placeholder="SAVE20">
            </div>
            <div>
                <label class="filter-label" for="coupon-name">Name</label>
                <input id="coupon-name" type="text" name="name" value="{{ old('name', $coupon->name ?? '') }}" required class="input-field" placeholder="Summer Sale 20%">
            </div>
            <div>
                <label class="filter-label" for="coupon-description">Description</label>
                <input id="coupon-description" type="text" name="description" value="{{ old('description', $coupon->description ?? '') }}" class="input-field">
            </div>
        </div>

        <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label class="filter-label" for="coupon-type">Type</label>
                <select id="coupon-type" name="type" required class="input-field">
                    @foreach($types as $t)
                        <option value="{{ $t->value }}" {{ old('type', $coupon->type->value ?? 'percentage') === $t->value ? 'selected' : '' }}>{{ $t->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="filter-label" for="coupon-value">Value</label>
                <input id="coupon-value" type="number" step="0.01" min="0.01" name="value" value="{{ old('value', $coupon->value ?? '') }}" required class="input-field" placeholder="20">
            </div>
            <div>
                <label class="filter-label" for="coupon-max-discount">Max Discount</label>
                <input id="coupon-max-discount" type="number" step="0.01" min="0" name="max_discount" value="{{ old('max_discount', $coupon->max_discount ?? '') }}" class="input-field" placeholder="For % coupons only">
            </div>
        </div>
    </x-nino.entity-form-section>

    <x-nino.entity-form-section title="Schedule and Limits" subtitle="Constrain the lifetime, redemption capacity, and per-customer limits for this coupon.">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="filter-label" for="coupon-starts-at">Starts At</label>
                <input id="coupon-starts-at" type="datetime-local" name="starts_at" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i') ?? '') }}" class="input-field">
            </div>
            <div>
                <label class="filter-label" for="coupon-ends-at">Ends At</label>
                <input id="coupon-ends-at" type="datetime-local" name="ends_at" value="{{ old('ends_at', $coupon->ends_at?->format('Y-m-d\TH:i') ?? '') }}" class="input-field">
            </div>
            <div>
                <label class="filter-label" for="coupon-usage-limit">Total Usage Limit</label>
                <input id="coupon-usage-limit" type="number" min="1" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit ?? '') }}" class="input-field" placeholder="Unlimited">
            </div>
            <div>
                <label class="filter-label" for="coupon-per-user-limit">Per User Limit</label>
                <input id="coupon-per-user-limit" type="number" min="1" name="per_user_limit" value="{{ old('per_user_limit', $coupon->per_user_limit ?? '') }}" class="input-field" placeholder="Unlimited">
            </div>
        </div>
    </x-nino.entity-form-section>

    <x-nino.entity-form-section title="Cart Requirements" subtitle="Restrict coupon eligibility based on order value and item count.">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label class="filter-label" for="coupon-min-total">Minimum Cart Total</label>
                <input id="coupon-min-total" type="number" step="0.01" min="0" name="minimum_cart_total" value="{{ old('minimum_cart_total', $coupon->minimum_cart_total ?? '') }}" class="input-field" placeholder="0.00">
            </div>
            <div>
                <label class="filter-label" for="coupon-max-total">Maximum Cart Total</label>
                <input id="coupon-max-total" type="number" step="0.01" min="0" name="maximum_cart_total" value="{{ old('maximum_cart_total', $coupon->maximum_cart_total ?? '') }}" class="input-field" placeholder="No max">
            </div>
            <div>
                <label class="filter-label" for="coupon-min-items">Minimum Items</label>
                <input id="coupon-min-items" type="number" min="1" name="minimum_items" value="{{ old('minimum_items', $coupon->minimum_items ?? '') }}" class="input-field" placeholder="No min">
            </div>
        </div>
    </x-nino.entity-form-section>

    <x-nino.entity-form-section title="Targeting" subtitle="Optionally constrain coupon eligibility to specific products or categories.">
        <div class="form-note mb-4">
            Enter comma-separated IDs. Leave the targeting fields empty to apply the coupon across the full catalog.
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="filter-label" for="coupon-product-ids">Apply to Product IDs</label>
                <input id="coupon-product-ids" type="text" name="product_ids_input" value="{{ old('product_ids_input', $coupon->exists && $coupon->product_ids ? implode(',', $coupon->product_ids) : '') }}" class="input-field" placeholder="1,5,8">
            </div>
            <div>
                <label class="filter-label" for="coupon-category-ids">Apply to Category IDs</label>
                <input id="coupon-category-ids" type="text" name="category_ids_input" value="{{ old('category_ids_input', $coupon->exists && $coupon->category_ids ? implode(',', $coupon->category_ids) : '') }}" class="input-field" placeholder="2,3">
            </div>
            <div>
                <label class="filter-label" for="coupon-excluded-product-ids">Exclude Product IDs</label>
                <input id="coupon-excluded-product-ids" type="text" name="excluded_product_ids_input" value="{{ old('excluded_product_ids_input', $coupon->exists && $coupon->excluded_product_ids ? implode(',', $coupon->excluded_product_ids) : '') }}" class="input-field" placeholder="10,12">
            </div>
            <div>
                <label class="filter-label" for="coupon-excluded-category-ids">Exclude Category IDs</label>
                <input id="coupon-excluded-category-ids" type="text" name="excluded_category_ids_input" value="{{ old('excluded_category_ids_input', $coupon->exists && $coupon->excluded_category_ids ? implode(',', $coupon->excluded_category_ids) : '') }}" class="input-field" placeholder="5">
            </div>
        </div>
    </x-nino.entity-form-section>
    </div>

    <div class="form-sidebar">
    <x-nino.entity-form-section title="Campaign Controls" subtitle="Enable the promotion, allow stacking, and review operational notes before saving.">
        <div class="space-y-4">
            <label for="coupon-is-active" class="toggle-row">
                <input type="checkbox" name="is_active" value="1" id="coupon-is-active" {{ old('is_active', $coupon->is_active ?? true) ? 'checked' : '' }} class="h-4 w-4 rounded border-[rgba(120,112,95,0.28)] text-[#245848] focus:ring-[#245848]/25">
                Coupon is active and redeemable
            </label>
            <label for="coupon-is-stackable" class="toggle-row">
                <input type="checkbox" name="is_stackable" value="1" id="coupon-is-stackable" {{ old('is_stackable', $coupon->is_stackable ?? false) ? 'checked' : '' }} class="h-4 w-4 rounded border-[rgba(120,112,95,0.28)] text-[#245848] focus:ring-[#245848]/25">
                Coupon can stack with other promotions
            </label>

            <div class="form-note">
                Keep campaign codes short and memorable. If the coupon has already been distributed, avoid changing the code after launch.
            </div>
        </div>

        <x-slot:footer>
            <div class="form-actions">
                <x-nino.button href="{{ route('admin.promotions.coupons.index') }}" variant="outline">Cancel</x-nino.button>
                <x-nino.button type="submit" variant="primary">
                    {{ $coupon->exists ? 'Update Coupon' : 'Create Coupon' }}
                </x-nino.button>
            </div>
        </x-slot:footer>
    </x-nino.entity-form-section>
    </div>
</form>
@endsection
