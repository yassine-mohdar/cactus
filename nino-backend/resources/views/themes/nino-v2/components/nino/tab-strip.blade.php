@props([
    'label' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between']) }}>
    @if($label)
        <p class="filter-label">{{ $label }}</p>
    @endif

    <div class="tab-strip">
        {{ $slot }}
    </div>
</div>
