@props([
    'title' => null,
    'provider' => null,
    'enabled' => false,
    'subtitle' => null,
])

<section {{ $attributes->merge(['class' => 'integration-card']) }}>
    <div class="integration-card-header">
        <div class="min-w-0">
            @if($title)
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="integration-card-title">{{ $title }}</h2>
                    <x-nino.status-badge :tone="$enabled ? 'success' : 'neutral'" size="sm">
                        {{ $enabled ? 'Enabled' : 'Disabled' }}
                    </x-nino.status-badge>
                </div>
            @endif

            @if($subtitle || $provider)
                <p class="integration-card-subtitle">
                    @if($subtitle)
                        {{ $subtitle }}
                    @elseif($provider)
                        Provider key: <span class="font-mono text-[11px] text-[#1E2B27]">{{ $provider }}</span>
                    @endif
                </p>
            @endif
        </div>

        {{ $header ?? '' }}
    </div>

    <div class="integration-card-body">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="integration-card-footer">
            {{ $footer }}
        </div>
    @endisset
</section>
