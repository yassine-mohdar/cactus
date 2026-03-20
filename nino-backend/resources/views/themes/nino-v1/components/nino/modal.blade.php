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
    <!-- Backdrop -->
    <div x-show="show" class="fixed inset-0 bg-ink/40 backdrop-blur-sm" x-on:click="show = false"></div>

    <!-- Panel -->
    <div
        x-show="show"
        @if($slideOver)
            class="relative w-full {{ $maxWidthClass }} h-full bg-surface shadow-xl border-l border-slate-300 flex flex-col"
            x-transition:enter="transform transition ease-in-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
        @else
            class="relative w-full {{ $maxWidthClass }} bg-surface rounded-lg shadow-xl border border-slate-300 max-h-[90vh] flex flex-col m-4"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        @endif
    >
        <!-- Header -->
        <div class="px-5 py-3 border-b border-slate-300 flex items-center justify-between">
            <h3 class="font-bold text-lg text-slate-900">{{ $title }}</h3>
            <button x-on:click="show = false" class="text-slate-500 hover:text-slate-900 transition-colors p-1 rounded hover:bg-slate-50/50">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Body -->
        <div class="p-5 flex-1 overflow-y-auto">
            {{ $slot }}
        </div>

        <!-- Footer -->
        @isset($footer)
            <div class="px-5 py-3 border-t border-slate-300 bg-slate-50">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
