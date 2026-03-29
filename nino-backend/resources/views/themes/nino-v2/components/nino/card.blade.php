@props([
    'title' => null,
    'subtitle' => null,
    'noPadding' => false,
])

<div {{ $attributes->merge(['class' => 'page-section relative flex flex-col']) }}>
    @if($title || isset($header))
        <div class="flex items-start justify-between gap-4 border-b border-[rgba(120,112,95,0.14)] px-5 py-4">
            @if($title)
                <div>
                    <h3 class="text-base font-bold tracking-tight text-[#1E2B27]">{{ $title }}</h3>
                    @if($subtitle)
                        <p class="mt-1 text-xs text-[#7A8681]">{{ $subtitle }}</p>
                    @endif
                </div>
            @endif
            {{ $header ?? '' }}
        </div>
    @endif

    <div class="{{ $noPadding ? '' : 'p-5' }} flex-1">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="border-t border-[rgba(120,112,95,0.14)] bg-[#FAF8F4] px-5 py-4">
            {{ $footer }}
        </div>
    @endisset
</div>
