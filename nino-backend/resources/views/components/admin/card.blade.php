@props(['title' => ''])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl shadow-sm border border-border overflow-hidden']) }}>
    @if ($title || isset($header))
        <div class="px-6 py-4 border-b border-border bg-gray-50/50 flex items-center justify-between">
            @if ($title)
                <h3 class="text-lg font-semibold text-ink">{{ $title }}</h3>
            @endif
            @isset($header)
                <div>{{ $header }}</div>
            @endisset
        </div>
    @endif
    
    <div class="p-6">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="px-6 py-4 border-t border-border bg-gray-50/50">
            {{ $footer }}
        </div>
    @endisset
</div>
