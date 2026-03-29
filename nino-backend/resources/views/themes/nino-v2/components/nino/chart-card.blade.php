@props([
    'title',
    'subtitle' => null,
    'type' => 'line',
    'data' => [],
    'options' => [],
    'height' => 220,
    'chartId' => null,
    'emptyTitle' => 'No chart data yet',
    'emptyDescription' => 'This widget will populate as soon as activity is recorded for the selected window.',
    'loadingLabel' => 'Loading chart',
])

@php
    $chartId = $chartId ?: 'chart-'.\Illuminate\Support\Str::uuid();
    $labels = collect(data_get($data, 'labels', []));
    $datasets = collect(data_get($data, 'datasets', []));
    $hasData = $labels->isNotEmpty()
        && $datasets->contains(fn ($dataset) => collect(data_get($dataset, 'data', []))->isNotEmpty());
    $chartConfig = [
        'type' => $type,
        'data' => $data,
        'options' => $options,
    ];
@endphp

<section {{ $attributes->merge(['class' => 'chart-card']) }}>
    <div class="chart-card-header">
        <div class="min-w-0">
            <h3 class="chart-card-title">{{ $title }}</h3>
            @if($subtitle)
                <p class="chart-card-subtitle">{{ $subtitle }}</p>
            @endif
        </div>

        @isset($actions)
            <div class="shrink-0">
                {{ $actions }}
            </div>
        @endisset
    </div>

    <div class="chart-card-body">
        @if($hasData)
            <div class="chart-card-shell" data-chart-shell data-chart-state="loading">
                <div class="chart-card-loading" data-chart-loading>
                    <span class="chart-card-loading-label">{{ $loadingLabel }}</span>
                    <div class="chart-card-loading-bars" aria-hidden="true">
                        <span class="chart-card-loading-bar"></span>
                        <span class="chart-card-loading-bar"></span>
                        <span class="chart-card-loading-bar"></span>
                    </div>
                </div>

                <canvas
                    id="{{ $chartId }}"
                    class="chart-canvas"
                    data-chart-height="{{ $height }}"
                    data-chart-config='@json($chartConfig)'></canvas>
            </div>
        @else
            <div class="chart-card-empty">
                <x-nino.empty-state
                    :title="$emptyTitle"
                    :description="$emptyDescription"
                    icon="insights" />
            </div>
        @endif
    </div>
</section>
