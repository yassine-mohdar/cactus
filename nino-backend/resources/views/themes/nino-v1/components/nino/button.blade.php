@props([
    'variant' => 'primary', // primary, secondary, danger, ghost
    'size'    => 'md',      // sm, md, lg
    'type'    => 'button',  // button, submit
    'href'    => null,      // if provided, renders as <a>
    'icon'    => null,      // optional material symbol name
])

@php
    $baseClasses = 'group inline-flex items-center justify-center rounded-lg font-semibold transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50';
    
    $variants = [
        'primary' => 'border border-transparent bg-indigo-600 text-white shadow-sm shadow-indigo-500/20 hover:bg-indigo-700 focus:ring-indigo-600',
        'secondary' => 'border border-slate-300 bg-white text-slate-800 shadow-sm hover:bg-slate-50 focus:ring-slate-900',
        'danger' => 'border border-transparent bg-red-600 text-white shadow-sm hover:bg-red-700 focus:ring-red-500',
        'ghost' => 'cursor-pointer border border-transparent text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:ring-slate-200',
    ];

    $sizes = [
        'sm' => 'px-3.5 py-2 text-xs',
        'md' => 'px-4 py-2.5 text-sm',
        'lg' => 'px-6 py-3 text-base',
    ];

    $classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }} wire:navigate>
        @if($icon)
            <span class="material-symbols-outlined mr-1.5 text-[1.25em]">{{ $icon }}</span>
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)
            <span class="material-symbols-outlined mr-1.5 text-[1.25em]">{{ $icon }}</span>
        @endif
        {{ $slot }}
    </button>
@endif
