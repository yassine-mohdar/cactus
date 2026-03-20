@props([
    'title' => null,
    'subtitle' => null,
])

<section {{ $attributes->merge(['class' => 'page-section overflow-hidden']) }}>
    @if($title || isset($header))
        <div class="flex flex-col gap-4 border-b border-[rgba(120,112,95,0.14)] px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
            @if($title)
                <div class="min-w-0">
                    <h2 class="text-base font-bold tracking-tight text-[#1E2B27]">{{ $title }}</h2>
                    @if($subtitle)
                        <p class="mt-1 text-sm text-[#61706B]">{{ $subtitle }}</p>
                    @endif
                </div>
            @endif

            {{ $header ?? '' }}
        </div>
    @endif

    <div class="px-5 py-5">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="border-t border-[rgba(120,112,95,0.14)] bg-[#FAF8F4] px-5 py-4">
            {{ $footer }}
        </div>
    @endisset
</section>
