@props([
    'title' => null,
    'subtitle' => null,
    'gridClass' => null,
])

<section {{ $attributes->merge(['class' => 'dashboard-section']) }}>
    @if($title || $subtitle || isset($actions))
        <div class="dashboard-section-header">
            <div class="min-w-0">
                @if($title)
                    <h2 class="dashboard-section-title">{{ $title }}</h2>
                @endif

                @if($subtitle)
                    <p class="dashboard-section-subtitle">{{ $subtitle }}</p>
                @endif
            </div>

            @isset($actions)
                <div class="dashboard-section-actions">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    @endif

    <div @class([$gridClass])>
        {{ $slot }}
    </div>
</section>
