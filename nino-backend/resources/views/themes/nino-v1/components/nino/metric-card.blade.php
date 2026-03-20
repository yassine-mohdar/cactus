@props([
    'title',
    'value',
    'icon',
    'subtitle' => null,
    'color' => 'sage' // sage, error
])

@php
    $colorOptions = [
        'sage' => [
            'bg' => 'bg-indigo-50',
            'text' => 'text-indigo-600',
            'border' => 'border-indigo-100'
        ],
        'error' => [
            'bg' => 'bg-red-500/10',
            'text' => 'text-red-500',
            'border' => 'border-red-500/20'
        ],
    ];

    $selectedColor = $colorOptions[$color] ?? $colorOptions['sage'];
@endphp

<x-nino.card :title="$title" class="group">
    <div class="flex items-center gap-5 mt-2">
        <div class="w-14 h-14 rounded-md {{ $selectedColor['bg'] }} {{ $selectedColor['text'] }} flex items-center justify-center border {{ $selectedColor['border'] }}">
            <span class="material-symbols-outlined text-3xl">{{ $icon }}</span>
        </div>
        <h3 class="text-4xl font-extrabold text-slate-800 tracking-tight">
            {{ $value }}
            @if($subtitle)
                <span class="text-xs font-bold text-slate-400 tracking-widest uppercase ml-1 align-baseline">{{ $subtitle }}</span>
            @endif
        </h3>
    </div>
</x-nino.card>
