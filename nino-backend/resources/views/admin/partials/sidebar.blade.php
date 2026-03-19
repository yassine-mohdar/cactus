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

            {{-- Section: Commerce --}}
            <li class="pt-4">
                <p class="px-3 pb-1 text-[10px] font-bold uppercase tracking-widest text-ink-muted">Commerce</p>
            </li>
            <li>
                <a href="#" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-ink-light hover:bg-cream hover:text-ink transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                    Catalog
                </a>
            </li>
            <li>
                <a href="#" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-ink-light hover:bg-cream hover:text-ink transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                    Orders
                </a>
            </li>
            <li>
                <a href="#" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-ink-light hover:bg-cream hover:text-ink transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L12 12.75 6.429 9.75m11.142 0l4.179 2.25-4.179 2.25m0 0L12 17.25l-5.571-3m11.142 0l4.179 2.25L12 21.75l-9.75-5.25 4.179-2.25"/></svg>
                    Inventory
                </a>
            </li>
            <li>
                <a href="#" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-ink-light hover:bg-cream hover:text-ink transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                    Customers
                </a>
            </li>

            {{-- Section: Operations --}}
            <li class="pt-4">
                <p class="px-3 pb-1 text-[10px] font-bold uppercase tracking-widest text-ink-muted">Operations</p>
            </li>
            <li>
                <a href="#" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-ink-light hover:bg-cream hover:text-ink transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
                    Shipping
                </a>
            </li>
            <li>
                <a href="#" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-ink-light hover:bg-cream hover:text-ink transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
                    Payments
                </a>
            </li>
            <li>
                <a href="#" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-ink-light hover:bg-cream hover:text-ink transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/></svg>
                    Coupons
                </a>
            </li>

            {{-- Section: Content --}}
            <li class="pt-4">
                <p class="px-3 pb-1 text-[10px] font-bold uppercase tracking-widest text-ink-muted">Content</p>
            </li>
            <li>
                <a href="#" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-ink-light hover:bg-cream hover:text-ink transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25H5.625a2.25 2.25 0 01-2.25-2.25V6.375c0-.621.504-1.125 1.125-1.125h3.375"/></svg>
                    Blog / CMS
                </a>
            </li>

            {{-- Section: System --}}
            <li class="pt-4">
                <p class="px-3 pb-1 text-[10px] font-bold uppercase tracking-widest text-ink-muted">System</p>
            </li>
            <li>
                <a href="#" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-ink-light hover:bg-cream hover:text-ink transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                    Staff & Roles
                </a>
            </li>
            <li>
                <a href="#" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-ink-light hover:bg-cream hover:text-ink transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
                    Settings
                </a>
            </li>
            <li>
                <a href="#" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-ink-light hover:bg-cream hover:text-ink transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                    Reports
                </a>
            </li>
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
