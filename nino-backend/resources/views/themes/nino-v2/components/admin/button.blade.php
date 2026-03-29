@props(['variant' => 'primary', 'type' => 'button', 'href' => null])

<x-nino.button :variant="$variant" :type="$type" :href="$href" {{ $attributes }}>
    {{ $slot }}
</x-nino.button>
