@props(['disabled' => false, 'label' => null, 'error' => null, 'id' => null])

@php
    $id = $id ?? Str::random(8);
@endphp

<div>
    @if($label)
        <label for="{{ $id }}" class="mb-2 block text-sm font-semibold text-[#17302A]">{{ $label }}</label>
    @endif

    <select {{ $disabled ? 'disabled' : '' }} id="{{ $id }}" {!! $attributes->merge(['class' => 'input-field']) !!}>
        {{ $slot }}
    </select>
    
    @if($error)
        <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
    @endif
</div>
