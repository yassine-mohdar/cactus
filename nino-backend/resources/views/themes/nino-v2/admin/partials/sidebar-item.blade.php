@if(!empty($item->children))
    <div x-data="{ open: {{ $item->_isActive ? 'true' : 'false' }} }" class="space-y-1">
        <button @click="open = !open" x-bind:aria-expanded="open.toString()"
           class="flex w-full items-center justify-between gap-3 rounded-md border px-3 py-2.5 font-semibold transition-colors {{ $item->_isActive ? 'border-[#D7E7DA] bg-[#ECF4EE] text-[#1E2B27]' : 'border-transparent text-[#5F6F67] hover:border-[rgba(120,112,95,0.14)] hover:bg-[#FCFBF8] hover:text-[#1E2B27]' }}">
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-md border border-transparent bg-[#F6F2EC] text-[#245848]">
                    {!! $item->icon !!}
                </span>
                <span>{{ $item->label }}</span>
            </div>
            <span class="material-symbols-outlined text-sm text-[#85958C] transition-transform duration-300" :class="open ? 'rotate-180' : ''">expand_more</span>
        </button>

        <div x-show="open" x-cloak class="ml-4 mt-1 space-y-1 border-l border-[rgba(120,112,95,0.14)] pl-3"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0">
            @foreach($item->children as $childItem)
                <a href="{{ $childItem->routeName ? route($childItem->routeName, $childItem->routeParams) : '#' }}" wire:navigate
                   class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors {{ $childItem->_isActive ? 'bg-[#FCFBF8] text-[#1E2B27] shadow-[0_1px_2px_rgba(17,24,39,0.06)]' : 'text-[#617169] hover:bg-[#FCFBF8] hover:text-[#1E2B27]' }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $childItem->_isActive ? 'bg-[#245848]' : 'bg-[#B8C3BC]' }}"></span>
                    <span class="text-sm">{{ $childItem->label }}</span>
                </a>
            @endforeach
        </div>
    </div>
@else
    <a href="{{ $item->routeName ? route($item->routeName, $item->routeParams) : '#' }}" wire:navigate
       class="flex items-center gap-3 rounded-md border px-3 py-2.5 font-semibold transition-colors {{ $item->_isActive ? 'border-[#D7E7DA] bg-[#ECF4EE] text-[#1E2B27]' : 'border-transparent text-[#5F6F67] hover:border-[rgba(120,112,95,0.14)] hover:bg-[#FCFBF8] hover:text-[#1E2B27]' }}">
        <span class="flex h-8 w-8 items-center justify-center rounded-md border border-transparent bg-[#F6F2EC] text-[#245848]">
            {!! $item->icon !!}
        </span>
        <span>{{ $item->label }}</span>
    </a>
@endif
