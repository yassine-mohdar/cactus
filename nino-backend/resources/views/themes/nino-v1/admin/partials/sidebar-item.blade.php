@if(!empty($item->children))
    {{-- Dropdown Parent --}}
    <div x-data="{ open: {{ $item->_isActive ? 'true' : 'false' }} }">
        <button @click="open = !open" x-bind:aria-expanded="open.toString()"
           class="flex w-full items-center justify-between gap-3 rounded-xl px-4 py-3 font-semibold transition-colors {{ $item->_isActive ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100 shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}">
            <div class="flex items-center gap-3">
                {!! $item->icon !!}
                <span>{{ $item->label }}</span>
            </div>
            <span class="material-symbols-outlined text-sm text-slate-400 transition-transform duration-300" :class="open ? 'rotate-180' : ''">expand_more</span>
        </button>
        
        {{-- Children --}}
        <div x-show="open" x-cloak class="mt-1 space-y-1 pl-6" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0">
            @foreach($item->children as $childItem)
                <a href="{{ $childItem->routeName ? route($childItem->routeName, $childItem->routeParams) : '#' }}" wire:navigate
                   class="flex items-center gap-3 rounded-xl px-4 py-2.5 font-medium transition-colors {{ $childItem->_isActive ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800' }}">
                    {!! $childItem->icon !!}
                    <span class="text-sm">{{ $childItem->label }}</span>
                </a>
            @endforeach
        </div>
    </div>
@else
    {{-- Direct Link --}}
    <a href="{{ $item->routeName ? route($item->routeName, $item->routeParams) : '#' }}" wire:navigate
       class="flex items-center gap-3 rounded-xl px-4 py-3 font-semibold transition-colors {{ $item->_isActive ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100 shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}">
        {!! $item->icon !!}
        <span>{{ $item->label }}</span>
    </a>
@endif
