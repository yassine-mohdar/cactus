@props([
    'title' => '',
    'noPadding' => false,
])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/5']) }}>
    @if ($title || isset($header))
        <div class="flex items-center justify-between border-b border-slate-200 bg-white px-6 py-4">
            @if ($title)
                <h3 class="flex items-center gap-2 text-lg font-bold tracking-tight text-slate-900">{{ $title }}</h3>
            @endif
            @isset($header)
                <div>{{ $header }}</div>
            @endisset
        </div>
    @endif
    
    <div class="{{ $noPadding ? '' : 'p-6' }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="border-t border-slate-200 bg-slate-50 px-6 py-4">
            {{ $footer }}
        </div>
    @endisset
</div>
