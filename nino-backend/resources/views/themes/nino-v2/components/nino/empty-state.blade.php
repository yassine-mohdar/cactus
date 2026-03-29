@props([
    'title',
    'description' => null,
    'icon' => 'deployed_code',
])

<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    <div class="empty-icon">
        <span class="material-symbols-outlined text-[1.25rem]">{{ $icon }}</span>
    </div>
    <h3 class="text-base font-semibold text-[#1E2B27]">{{ $title }}</h3>
    @if($description)
        <p class="mx-auto mt-1 max-w-md text-sm text-[#61706B]">{{ $description }}</p>
    @endif
    @if(trim((string) $slot) !== '')
        <div class="mt-4">
            {{ $slot }}
        </div>
    @endif
</div>
