@props([
    'title' => null,
    'subtitle' => null,
])

<section {{ $attributes->merge(['class' => 'settings-panel']) }}>
    @if($title || isset($header))
        <div class="settings-panel-header">
            @if($title)
                <div class="min-w-0">
                    <h2 class="settings-panel-title">{{ $title }}</h2>
                    @if($subtitle)
                        <p class="settings-panel-subtitle">{{ $subtitle }}</p>
                    @endif
                </div>
            @endif

            {{ $header ?? '' }}
        </div>
    @endif

    <div class="settings-panel-body">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="settings-panel-footer">
            {{ $footer }}
        </div>
    @endisset
</section>
