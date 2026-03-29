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
@endphp

{{-- Admin Topbar --}}
<header class="fixed left-0 right-0 top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200/80 bg-white/80 px-4 backdrop-blur-md transition-colors sm:px-6 lg:left-72 lg:px-8">
    {{-- Left: Breadcrumb / Page Title --}}
    <div class="flex min-w-0 flex-1 items-center gap-3">
        <button type="button" class="rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900 lg:hidden" x-on:click="sidebarOpen = true" aria-label="Open sidebar">
            <span class="material-symbols-outlined">menu</span>
        </button>
        <div class="min-w-0">
            <div class="flex items-center gap-2 text-sm">
                <span class="font-semibold text-slate-500">Admin</span>
            @hasSection('breadcrumb')
                <span class="material-symbols-outlined text-slate-400 text-sm">chevron_right</span>
                <span class="truncate font-bold text-slate-900">@yield('breadcrumb')</span>
            @else
                <span class="material-symbols-outlined text-slate-300 text-sm">chevron_right</span>
                <span class="truncate font-bold text-slate-900">{{ $topbarTitle }}</span>
            @endif
            </div>
        </div>
    </div>

    {{-- Right: Actions --}}
    <div class="flex items-center gap-4 sm:gap-6">
        {{-- Notifications Bell --}}
        <button class="relative rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-100 hover:text-indigo-600" aria-label="Notifications">
            <span class="material-symbols-outlined">notifications</span>
            <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full ring-2 ring-white"></span>
        </button>

        <div class="flex items-center gap-3 border-l border-slate-200 pl-4 sm:pl-6">
            @auth
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-slate-900">{{ auth()->user()->name }}</p>
                    <p class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold">
                        @if(auth()->user()->hasRole('Super Admin'))
                            Super Admin
                        @else
                            Staff Access
                        @endif
                    </p>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="ml-2">
                    @csrf
                    <button type="submit" class="block rounded-lg p-2 text-slate-400 transition-colors hover:bg-red-50 hover:text-red-600" title="Logout">
                        <span class="material-symbols-outlined">logout</span>
                    </button>
                </form>
            @endauth
        </div>
    </div>
</header>
