@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'noPadding' => false,
])

<section {{ $attributes->merge(['class' => 'page-section overflow-hidden']) }}>
    @if($title || isset($header))
        <div class="flex items-start justify-between gap-4 border-b border-[rgba(120,112,95,0.14)] px-5 py-4">
            @if($title)
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        @if($icon)
                            <span class="material-symbols-outlined text-base text-[#61706B]">{{ $icon }}</span>
                        @endif
                        <h2 class="text-base font-bold tracking-tight text-[#1E2B27]">{{ $title }}</h2>
                    </div>
                    @if($subtitle)
                        <p class="mt-1 text-sm text-[#61706B]">{{ $subtitle }}</p>
                    @endif
                </div>
            @endif

            {{ $header ?? '' }}
        </div>
    @endif

    <div class="{{ $noPadding ? '' : 'px-5 py-5' }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="border-t border-[rgba(120,112,95,0.14)] bg-[#FAF8F4] px-5 py-4">
            {{ $footer }}
        </div>
    @endisset
</section>
