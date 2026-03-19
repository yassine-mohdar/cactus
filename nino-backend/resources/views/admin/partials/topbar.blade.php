{{-- Admin Topbar --}}
<header class="flex h-14 items-center justify-between border-b border-border bg-surface px-6">
    {{-- Left: Breadcrumb / Page Title --}}
    <div class="flex items-center gap-2 text-sm">
        <span class="font-medium text-ink-muted">Admin</span>
        @hasSection('breadcrumb')
            <svg class="h-3 w-3 text-ink-muted" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            <span class="font-medium text-ink">@yield('breadcrumb')</span>
        @endif
    </div>

    {{-- Right: Actions --}}
    <div class="flex items-center gap-4">
        {{-- Notifications Bell --}}
        <button class="relative rounded-lg p-1.5 text-ink-muted transition-colors hover:bg-cream hover:text-ink">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
        </button>

        {{-- Logout --}}
        @auth
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-lg px-3 py-1.5 text-xs font-medium text-ink-muted transition-colors hover:bg-danger-light hover:text-danger">
                    Logout
                </button>
            </form>
        @endauth
    </div>
</header>
