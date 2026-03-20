@props([
    'title' => null,
    'subtitle' => null,
    'noPadding' => false,
])

<x-nino.card :title="$title" :subtitle="$subtitle" :no-padding="$noPadding" {{ $attributes }}>
    @isset($header)
        <x-slot:header>
            {{ $header }}
        </x-slot:header>
    @endisset

    {{ $slot }}

    @isset($footer)
        <x-slot:footer>
            {{ $footer }}
        </x-slot:footer>
    @endisset
</x-nino.card>
