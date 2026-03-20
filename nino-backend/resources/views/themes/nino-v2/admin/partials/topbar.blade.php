@php
    $routeName = request()->route()?->getName();
    $derivePageTitle = static function (?string $route): string {
        if (! is_string($route) || $route === '') {
            return 'Dashboard';
        }

        $segments = array_values(array_filter(
            explode('.', $route),
            static fn (string $segment): bool => $segment !== 'admin'
        ));

        if ($segments === []) {
            return 'Dashboard';
        }

        $action = end($segments) ?: 'dashboard';
        $resourceSegments = $segments;

        if (in_array($action, ['index', 'create', 'edit', 'show'], true)) {
            array_pop($resourceSegments);
        }

        $resource = end($resourceSegments) ?: $action;
        $resourceLabel = \Illuminate\Support\Str::headline(str_replace(['-', '_'], ' ', $resource));

        return match ($action) {
            'create' => 'Create '.\Illuminate\Support\Str::singular($resourceLabel),
            'edit' => 'Edit '.\Illuminate\Support\Str::singular($resourceLabel),
            'show' => \Illuminate\Support\Str::singular($resourceLabel),
            'index' => $resourceLabel,
            default => \Illuminate\Support\Str::headline(str_replace(['-', '_'], ' ', $action)),
        };
    };

    $topbarTitle = htmlspecialchars_decode(trim($__env->yieldContent('title')), ENT_QUOTES);

    if ($topbarTitle === '') {
        $topbarTitle = $derivePageTitle($routeName);
    }

    $topbarCrumb = trim($__env->yieldContent('breadcrumb'));
@endphp

<header class="fixed left-0 right-0 top-0 z-30 border-b border-[rgba(120,112,95,0.14)] bg-[rgba(251,250,247,0.92)] backdrop-blur lg:left-[17rem]">
    <div class="mx-auto flex h-14 max-w-[1600px] items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <div class="flex min-w-0 flex-1 items-center gap-3">
            <button type="button" class="rounded-md border border-[rgba(120,112,95,0.16)] bg-[#FCFBF8] p-2 text-[#617169] transition-colors hover:text-[#1E2B27] lg:hidden" x-on:click="openSidebar()" x-bind:aria-expanded="sidebarOpen ? 'true' : 'false'" aria-controls="admin-sidebar" aria-label="Open sidebar">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <div class="min-w-0">
                <div class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.22em] text-[#7A8A82]">
                    <span>Admin</span>
                    <span class="material-symbols-outlined text-xs text-[#9CACA3]">chevron_right</span>
                    <span class="truncate text-[#245848]">{{ $topbarCrumb !== '' ? $topbarCrumb : $topbarTitle }}</span>
                </div>
                <p class="truncate text-sm font-semibold tracking-tight text-[#1E2B27]">{{ $topbarTitle }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.support.lookup') }}" wire:navigate class="hidden items-center gap-2 rounded-md border border-[rgba(120,112,95,0.16)] bg-[#FCFBF8] px-3 py-1.5 text-xs text-[#61706B] shadow-[0_1px_2px_rgba(17,24,39,0.04)] md:inline-flex">
                <span class="material-symbols-outlined text-sm">search</span>
                <span>Search orders, customers, tickets</span>
            </a>

            <div class="hidden rounded-md border border-[rgba(120,112,95,0.16)] bg-[#FCFBF8] px-2.5 py-1.5 text-[10px] font-semibold uppercase tracking-[0.2em] text-[#6E7D75] lg:inline-flex">
                {{ now()->format('D, M j') }}
            </div>

            <button class="relative rounded-md border border-[rgba(120,112,95,0.16)] bg-[#FCFBF8] p-2 text-[#617169] transition-colors hover:text-[#245848]" aria-label="Notifications">
                <span class="material-symbols-outlined">notifications</span>
                <span class="absolute right-2 top-2 h-2.5 w-2.5 rounded-full border-2 border-white bg-[#C94B3C]"></span>
            </button>

            @auth
                <div class="hidden min-w-0 rounded-md border border-[rgba(120,112,95,0.16)] bg-[#FCFBF8] px-3 py-2 text-right shadow-[0_1px_2px_rgba(17,24,39,0.04)] sm:block">
                    <p class="truncate text-sm font-bold text-[#1E2B27]">{{ auth()->user()->name }}</p>
                    <p class="truncate text-[10px] font-semibold uppercase tracking-[0.22em] text-[#6C7B73]">
                        @if(auth()->user()->hasRole('Super Admin'))
                            Super Admin
                        @else
                            Staff Access
                        @endif
                    </p>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-md border border-[rgba(120,112,95,0.16)] bg-[#FCFBF8] px-3 py-2 text-sm font-semibold text-[#1E2B27] transition-colors hover:bg-[#F7F4EF]" title="Logout">
                        <span class="material-symbols-outlined text-[1.1rem]">logout</span>
                        <span class="hidden md:inline">Logout</span>
                    </button>
                </form>
            @endauth
        </div>
    </div>
</header>
