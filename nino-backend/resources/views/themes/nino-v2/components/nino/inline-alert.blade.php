@props([
    'tone' => 'info',
    'title' => null,
    'icon' => null,
])

@php
    $icons = [
        'info' => 'info',
        'success' => 'check_circle',
        'warning' => 'warning',
        'danger' => 'error',
        'neutral' => 'info',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'inline-alert inline-alert-'.$tone]) }}>
    <span class="material-symbols-outlined inline-alert-icon">{{ $icon ?: ($icons[$tone] ?? 'info') }}</span>
    <div class="min-w-0">
        @if($title)
            <p class="inline-alert-title">{{ $title }}</p>
        @endif

        <div class="{{ $title ? 'inline-alert-copy' : '' }}">
            {{ $slot }}
        </div>
    </div>
</div>
