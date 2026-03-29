@props([
    'headers' => [],
    'headerClass' => '',
    'bodyClass' => '',
    'tableClass' => '',
    'scrollClass' => '',
])

<div class="datatable-scroll {{ $scrollClass }}">
    <table class="nino-table {{ $tableClass }}">
        @if(isset($head) || isset($body))
            @if(isset($head))
                <thead class="{{ $headerClass }}">
                    <tr>
                        {{ $head }}
                    </tr>
                </thead>
            @endif
            @if(isset($body))
                <tbody class="{{ $bodyClass }}">
                    {{ $body }}
                </tbody>
            @endif
        @else
            {{ $slot }}
        @endif
    </table>
</div>
