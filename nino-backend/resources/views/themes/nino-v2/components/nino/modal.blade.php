@props([
    'name',              // Modal identifier for Alpine
    'title' => '',       // Header title
    'maxWidth' => '2xl', // sm, md, lg, xl, 2xl
    'slideOver' => false // false = center modal, true = right slide-over
])

@php
$maxWidthClass = match($maxWidth) {
    'sm' => 'max-w-sm',
    'md' => 'max-w-md',
    'lg' => 'max-w-lg',
    'xl' => 'max-w-xl',
    '2xl' => 'max-w-2xl',
    '3xl' => 'max-w-3xl',
    '4xl' => 'max-w-4xl',
    '5xl' => 'max-w-5xl',
    default => 'max-w-2xl',
};
@endphp

<div
    x-data="{ show: false }"
    x-show="show"
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="show = false"
    x-on:keydown.escape.window="show = false"
    style="display: none;"
    class="fixed inset-0 z-50 flex {{ $slideOver ? 'justify-end' : 'items-center justify-center' }}"
    x-transition:enter="ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    <div x-show="show" class="fixed inset-0 bg-[#17302A]/45 backdrop-blur-sm" x-on:click="show = false"></div>

    <div
        x-show="show"
        @if($slideOver)
            class="relative flex h-full w-full {{ $maxWidthClass }} flex-col border-l border-[rgba(145,133,109,0.18)] bg-[rgba(255,252,246,0.94)] shadow-[0_32px_90px_-48px_rgba(23,48,42,0.6)] backdrop-blur"
            x-transition:enter="transform transition ease-in-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
        @else
            class="relative m-4 flex max-h-[90vh] w-full {{ $maxWidthClass }} flex-col rounded-[1.25rem] border border-[rgba(145,133,109,0.18)] bg-[rgba(255,252,246,0.94)] shadow-[0_24px_64px_-40px_rgba(23,48,42,0.48)] backdrop-blur"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        @endif
    >
        <div class="flex items-center justify-between border-b border-[rgba(145,133,109,0.16)] px-5 py-4">
            <h3 class="text-lg font-bold text-[#17302A]">{{ $title }}</h3>
            <button x-on:click="show = false" class="icon-button-surface border-transparent p-1">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div class="p-5 flex-1 overflow-y-auto">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="border-t border-[rgba(145,133,109,0.16)] bg-[#F7F2E8]/80 px-5 py-4">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
