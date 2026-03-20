@props([
    'title' => null,
    'subtitle' => null,
    'gridClass' => null,
])

<section {{ $attributes->merge(['class' => 'space-y-4']) }}>
    @if($title || $subtitle || isset($actions))
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                @if($title)
                    <h2 class="text-lg font-bold tracking-tight text-slate-900">{{ $title }}</h2>
                @endif

                @if($subtitle)
                    <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
                @endif
            </div>

            @isset($actions)
                <div class="flex flex-wrap items-center gap-2">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    @endif

    <div @class([$gridClass])>
        {{ $slot }}
    </div>
</section>
