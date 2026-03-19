@props(['variant' => 'primary', 'type' => 'button', 'href' => null])

@php
    $baseClass = 'inline-flex items-center justify-center px-4 py-2 font-semibold text-sm rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2';
    
    $variants = [
        'primary' => 'bg-sage text-white hover:bg-sage-dark focus:ring-sage shadow-sm border border-transparent',
        'secondary' => 'bg-white text-ink border border-border hover:bg-gray-50 focus:ring-gray-200 shadow-sm',
        'danger' => 'bg-white text-red-600 border border-red-200 hover:bg-red-50 focus:ring-red-500 shadow-sm',
    ];

    $classes = $baseClass . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
