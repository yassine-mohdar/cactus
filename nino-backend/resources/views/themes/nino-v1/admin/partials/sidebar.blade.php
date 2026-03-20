{{-- Admin Sidebar --}}
<nav x-cloak x-bind:class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-slate-200 bg-white/95 px-4 py-5 text-sm shadow-xl shadow-slate-900/10 backdrop-blur transition-transform duration-300 ease-out lg:translate-x-0">
    {{-- Brand --}}
    <div class="mb-8 flex items-center justify-between px-2">
        <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl border border-indigo-100 bg-indigo-50 text-lg font-black tracking-widest text-indigo-700 shadow-sm shadow-indigo-500/10">NW</div>
            <div>
                <div class="text-lg font-bold tracking-tight text-slate-900">NinoWorld</div>
                <div class="text-[10px] font-bold uppercase tracking-[0.24em] text-slate-400">Admin Panel</div>
            </div>
        </div>
        <button type="button" class="rounded-lg p-2 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-900 lg:hidden" x-on:click="sidebarOpen = false" aria-label="Close sidebar">
            <span class="material-symbols-outlined text-base">close</span>
        </button>
    </div>

    {{-- Navigation --}}
    <div class="flex-1 space-y-1 overflow-y-auto pr-1">
        {{-- Dashboard --}}
        <a href="{{ route('admin.dashboard') }}" wire:navigate
           class="flex items-center gap-3 rounded-xl px-4 py-3 font-semibold transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100 shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}">
            <span class="material-symbols-outlined text-base">dashboard</span>
            <span>Dashboard</span>
        </a>

        {{-- Dynamic Modules --}}
        @if(isset($adminMenu))
            @foreach($adminMenu as $item)
                @if($item->isHeader)
                    <div class="pt-6 pb-2">
                        <p class="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ $item->label }}</p>
                    </div>
                    @if(!empty($item->children))
                        @foreach($item->children as $childItem)
                            @include('admin.partials.sidebar-item', ['item' => $childItem])
                        @endforeach
                    @endif
                @else
                    @include('admin.partials.sidebar-item', ['item' => $item])
                @endif
            @endforeach
        @endif
    </div>

    {{-- User Info (Bottom Section) --}}
    <div class="mt-auto border-t border-slate-200 pt-5">
        <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-slate-400 shadow-sm">
                <span class="material-symbols-outlined text-lg">person</span>
            </div>
            <div class="flex-1 truncate">
                <p class="truncate text-sm font-bold text-slate-900">{{ auth()->check() ? auth()->user()->name : 'Guest' }}</p>
                <p class="truncate text-[11px] text-slate-500">{{ auth()->check() ? auth()->user()->email : '' }}</p>
            </div>
        </div>
    </div>
</nav>
