{{-- Admin Sidebar --}}
<aside class="flex w-60 flex-col border-r border-border bg-surface">
    {{-- Brand --}}
    <div class="flex h-14 items-center border-b border-border px-5">
        <span class="text-lg font-extrabold tracking-tight text-sage">Nino</span>
        <span class="text-lg font-extrabold tracking-tight text-ink">World</span>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4">
        <ul class="space-y-0.5">
            {{-- Dashboard --}}
            <li>
                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-sage/10 text-sage-dark' : 'text-ink-light hover:bg-cream hover:text-ink' }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z"/></svg>
                    Dashboard
                </a>
            </li>

            {{-- Dynamic Modules --}}
            @if(isset($adminMenu))
                @foreach($adminMenu as $item)
                    @if($item->isHeader)
                        <li class="pt-4">
                            <p class="px-3 pb-1 text-[10px] font-bold uppercase tracking-widest text-ink-muted">{{ $item->label }}</p>
                        </li>
                    @else
                        <li>
                            <a href="{{ $item->routeName ? route($item->routeName, $item->routeParams) : '#' }}" 
                               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ $item->_isActive ? 'bg-sage/10 text-sage-dark' : 'text-ink-light hover:bg-cream hover:text-ink' }}">
                                {!! $item->icon !!}
                                {{ $item->label }}
                            </a>
                        </li>
                    @endif

                    @if(!empty($item->children))
                        @foreach($item->children as $childItem)
                            <li>
                                <a href="{{ $childItem->routeName ? route($childItem->routeName, $childItem->routeParams) : '#' }}" 
                                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ $childItem->_isActive ? 'bg-sage/10 text-sage-dark' : 'text-ink-light hover:bg-cream hover:text-ink' }}">
                                    {!! $childItem->icon !!}
                                    {{ $childItem->label }}
                                </a>
                            </li>
                        @endforeach
                    @endif
                @endforeach
            @endif
        </ul>
    </nav>

    {{-- User Info --}}
    <div class="border-t border-border px-4 py-3">
        <div class="flex items-center gap-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-sage/10 text-xs font-bold text-sage">
                {{ auth()->check() ? strtoupper(substr(auth()->user()->name, 0, 2)) : 'NW' }}
            </div>
            <div class="flex-1 truncate">
                <p class="truncate text-sm font-semibold text-ink">{{ auth()->check() ? auth()->user()->name : 'Guest' }}</p>
                <p class="truncate text-xs text-ink-muted">{{ auth()->check() ? auth()->user()->email : '' }}</p>
            </div>
        </div>
    </div>
</aside>
