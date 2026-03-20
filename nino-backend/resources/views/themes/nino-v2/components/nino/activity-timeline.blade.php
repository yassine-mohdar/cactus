@props([
    'emptyTitle' => 'No activity yet',
    'emptyDescription' => 'Timeline activity will appear here as soon as the first action is recorded.',
    'icon' => 'timeline',
])

@php
    $slotContent = trim((string) $slot);
@endphp

@if($slotContent === '')
    <x-nino.empty-state :title="$emptyTitle" :description="$emptyDescription" :icon="$icon" />
@else
    <div {{ $attributes->merge(['class' => 'timeline-list']) }}>
        {{ $slot }}
    </div>
@endif
