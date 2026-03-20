@props([
    'variant' => 'primary', // primary, secondary, danger, ghost, outline
    'size'    => 'md',      // sm, md, lg
    'type'    => 'button',  // button, submit
    'href'    => null,      // if provided, renders as <a>
    'icon'    => null,      // optional material symbol name
])

@php
    $baseClasses = 'group inline-flex items-center justify-center rounded-md font-semibold transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50';
    
    $variants = [
        'primary' => 'border border-transparent bg-[#245848] text-white shadow-[0_1px_2px_rgba(17,24,39,0.08),0_6px_16px_-12px_rgba(36,88,72,0.45)] hover:bg-[#173B31] focus:ring-[#245848]/25',
        'secondary' => 'border border-[rgba(120,112,95,0.18)] bg-[#FCFBF8] text-[#1E2B27] shadow-[0_1px_2px_rgba(17,24,39,0.05)] hover:bg-[#F7F4EF] focus:ring-[#245848]/18',
        'danger' => 'border border-[#EFC8C1] bg-[#FCEDEA] text-[#C45143] shadow-[0_1px_2px_rgba(17,24,39,0.05)] hover:bg-[#F7D8D2] focus:ring-[#C45143]/20',
        'ghost' => 'cursor-pointer border border-transparent text-[#5E6E66] hover:bg-[#F6F2EC] hover:text-[#1E2B27] focus:ring-[#D6D0C2]',
        'outline' => 'border border-[rgba(120,112,95,0.18)] bg-transparent text-[#1E2B27] hover:bg-[#F6F2EC] focus:ring-[#245848]/18',
    ];

    $sizes = [
        'sm' => 'px-3 py-2 text-xs',
        'md' => 'px-4 py-2.5 text-sm',
        'lg' => 'px-6 py-3 text-base',
    ];

    $classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }} wire:navigate>
        @if($icon)
            <span class="material-symbols-outlined mr-1.5 text-[1.1em]">{{ $icon }}</span>
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)
            <span class="material-symbols-outlined mr-1.5 text-[1.1em]">{{ $icon }}</span>
        @endif
        {{ $slot }}
    </button>
@endif
