@props([
    'title',
    'value',
    'icon',
    'subtitle' => null,
    'color' => 'sage',
    'change' => null,
    'changeType' => 'neutral',
])

@php
    $colorOptions = [
        'sage' => [
            'bg' => 'bg-[#ECF4EE]',
            'text' => 'text-[#245848]',
            'border' => 'border-[#D7E7DA]'
        ],
        'error' => [
            'bg' => 'bg-[#FCEDEA]',
            'text' => 'text-[#C45143]',
            'border' => 'border-[#EFC8C1]'
        ],
        'warning' => [
            'bg' => 'bg-[#FBF2E2]',
            'text' => 'text-[#B8802F]',
            'border' => 'border-[#EAD9B6]',
        ],
    ];

    $selectedColor = $colorOptions[$color] ?? $colorOptions['sage'];
    $changeClass = match ($changeType) {
        'positive' => 'metric-change metric-change-positive',
        'negative' => 'metric-change metric-change-negative',
        default => 'metric-change metric-change-neutral',
    };
@endphp

<x-nino.card class="group">
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="metric-label">{{ $title }}</p>
            <h3 class="metric-value">
                {{ $value }}
            </h3>
            @if($subtitle)
                <p class="metric-subtitle">{{ $subtitle }}</p>
            @endif
        </div>
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md border {{ $selectedColor['bg'] }} {{ $selectedColor['text'] }} {{ $selectedColor['border'] }}">
            <span class="material-symbols-outlined text-[1.35rem]">{{ $icon }}</span>
        </div>
    </div>

    @if($change)
        <div class="mt-4">
            <span class="{{ $changeClass }}">{{ $change }}</span>
        </div>
    @endif
</x-nino.card>
