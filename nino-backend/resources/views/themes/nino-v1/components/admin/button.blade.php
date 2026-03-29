@props(['variant' => 'primary', 'type' => 'button', 'href' => null])

@php
    $baseClass = 'inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-semibold transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50';
    
    $variants = [
        'primary' => 'border border-transparent bg-indigo-600 text-white shadow-sm shadow-indigo-500/20 hover:bg-indigo-700 focus:ring-indigo-600',
        'secondary' => 'border border-slate-300 bg-white text-slate-700 shadow-sm hover:bg-slate-50 focus:ring-slate-900',
        'danger' => 'border border-red-200 bg-red-50 text-red-600 shadow-sm hover:bg-red-600 hover:text-white focus:ring-red-500',
        'outline' => 'border border-slate-300 bg-transparent text-slate-700 shadow-sm hover:bg-slate-50 focus:ring-slate-900',
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
