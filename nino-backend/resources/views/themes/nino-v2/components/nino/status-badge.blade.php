@props([
    'tone' => 'neutral',
    'size' => 'md',
])

@php
    $sizeClass = $size === 'sm'
        ? 'px-2 py-0.5 text-[9px] tracking-[0.16em]'
        : 'px-2.5 py-1 text-[10px] tracking-[0.18em]';
@endphp

<span {{ $attributes->merge(['class' => 'status-badge status-badge-'.$tone.' '.$sizeClass]) }}>
    {{ $slot }}
</span>
