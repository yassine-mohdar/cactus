@props([
    'eyebrow' => 'Operations workspace',
    'title' => null,
    'subtitle' => null,
])

@php
    $slotContent = trim((string) $slot);
@endphp

<div {{ $attributes->merge(['class' => 'page-header']) }}>
    <div class="page-header-copy">
        @if($eyebrow)
            <div class="page-eyebrow">{{ $eyebrow }}</div>
        @endif

        @if($title)
            <h1 class="page-title">{{ $title }}</h1>
        @endif

        @if($subtitle)
            <p class="page-subtitle">{{ $subtitle }}</p>
        @endif

        @if($slotContent !== '')
            <div class="{{ ($title || $subtitle) ? 'mt-4' : '' }}">
                {{ $slot }}
            </div>
        @endif
    </div>

    @isset($actions)
        <div class="page-actions">
            {{ $actions }}
        </div>
    @endisset
</div>
