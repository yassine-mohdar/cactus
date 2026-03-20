@props([
    'title' => null,
    'subtitle' => null,
    'noPadding' => false,
])

<div {{ $attributes->merge(['class' => 'relative flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/5']) }}>
    @if($title || isset($header))
        <div class="flex items-center justify-between border-b border-slate-200 bg-white px-5 py-4">
            @if($title)
                <div>
                    <h3 class="font-bold text-slate-900 text-lg tracking-tight">{{ $title }}</h3>
                    @if($subtitle)
                        <p class="text-xs text-slate-500 font-medium mt-1 uppercase tracking-widest">{{ $subtitle }}</p>
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
        <div class="rounded-b-2xl border-t border-slate-200 bg-canvas px-5 py-4">
            {{ $footer }}
        </div>
    @endisset
</div>
