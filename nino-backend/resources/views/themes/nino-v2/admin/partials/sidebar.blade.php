<nav
    id="admin-sidebar"
    x-cloak
    x-bind:class="sidebarVisible() ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    x-bind:aria-hidden="sidebarVisible() ? 'false' : 'true'"
    x-bind:inert="sidebarVisible() ? null : 'inert'"
    class="fixed inset-y-0 left-0 z-50 flex w-[17rem] flex-col border-r border-[rgba(120,112,95,0.14)] bg-[#FBFAF7] px-3 py-4 text-sm shadow-[0_8px_24px_-18px_rgba(17,30,26,0.18)] transition-transform duration-200 ease-out lg:translate-x-0"
>
    <div class="rounded-xl border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] p-3 shadow-[0_1px_2px_rgba(17,24,39,0.05)]">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-md border border-[#D7E7DA] bg-[#ECF4EE] text-sm font-black tracking-[0.24em] text-[#245848]">NW</div>
                <div>
                    <div class="text-base font-extrabold tracking-tight text-[#1E2B27]">NinoWorld</div>
                    <div class="mt-1 text-[11px] font-medium text-[#61706B]">{{ $adminTheme['label'] ?? 'Nino v2' }} workspace</div>
                </div>
            </div>
            <button type="button" class="rounded-md p-2 text-[#728278] transition-colors hover:bg-[#F6F2EC] hover:text-[#1E2B27] lg:hidden" x-on:click="closeSidebar()" aria-label="Close sidebar">
                <span class="material-symbols-outlined text-base">close</span>
            </button>
        </div>
    </div>

    <div class="mt-5 flex-1 space-y-1 overflow-y-auto pr-1">
        <a href="{{ route('admin.dashboard') }}" wire:navigate class="flex items-center gap-3 rounded-md border px-3 py-2.5 font-semibold transition-colors {{ request()->routeIs('admin.dashboard') ? 'border-[#D7E7DA] bg-[#ECF4EE] text-[#1E2B27]' : 'border-transparent text-[#5F6F67] hover:border-[rgba(120,112,95,0.14)] hover:bg-[#FCFBF8] hover:text-[#1E2B27]' }}">
            <span class="flex h-8 w-8 items-center justify-center rounded-md border border-transparent bg-[#F6F2EC] text-[#245848]">
                <span class="material-symbols-outlined text-[1.15rem]">dashboard</span>
            </span>
            <span>Dashboard</span>
        </a>

        @if(isset($adminMenu))
            @foreach($adminMenu as $item)
                @if($item->isHeader)
                    <div class="pb-2 pt-6">
                        <p class="px-3 text-[10px] font-bold uppercase tracking-[0.28em] text-[#7B8B82]">{{ $item->label }}</p>
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

    <div class="mt-5 rounded-xl border border-[rgba(120,112,95,0.14)] bg-[#FCFBF8] p-4 shadow-[0_1px_2px_rgba(17,24,39,0.05)]">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-md border border-[#D8E7DD] bg-[#EDF4EF] text-[#245848]">
                <span class="material-symbols-outlined text-lg">person</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-bold text-[#1E2B27]">{{ auth()->check() ? auth()->user()->name : 'Guest' }}</p>
                <p class="truncate text-[11px] text-[#65756D]">{{ auth()->check() ? auth()->user()->email : '' }}</p>
            </div>
        </div>
        <div class="mt-4 flex items-center justify-between rounded-md border border-[rgba(120,112,95,0.12)] bg-[#FAF8F4] px-3 py-2.5 text-[11px] font-semibold uppercase tracking-[0.22em] text-[#6D7A72]">
            <span>Live Session</span>
            <span class="inline-flex items-center gap-1.5 text-[#245848]">
                <span class="h-2 w-2 rounded-full bg-[#245848]"></span>
                Active
            </span>
        </div>
    </div>
</nav>
