@props(['disabled' => false, 'label' => null, 'error' => null, 'id' => null])

@php
    $id = $id ?? Str::random(8);
@endphp

<div>
    @if($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-ink mb-1">{{ $label }}</label>
    @endif

    <input {{ $disabled ? 'disabled' : '' }} id="{{ $id }}" {!! $attributes->merge(['class' => 'w-full rounded-lg border border-border bg-white px-4 py-2 text-sm focus:border-sage focus:outline-none focus:ring-1 focus:ring-sage transition duration-150 disabled:bg-gray-50 disabled:text-gray-500']) !!}>
    
    @if($error)
        <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
    @endif
</div>
