@props([
    'title',
    'subtitle' => null,
    'type' => 'line',
    'data' => [],
    'options' => [],
    'height' => 220,
    'chartId' => null,
])

@php
    $chartId = $chartId ?: 'chart-'.\Illuminate\Support\Str::uuid();
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
        <canvas
            id="{{ $chartId }}"
            class="chart-canvas"
            data-chart-height="{{ $height }}"
            data-chart-config='@json($chartConfig)'></canvas>
    </div>
</section>
