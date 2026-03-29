<!DOCTYPE html>
<html lang="en">
<head>
    @php
        $routeName = request()->route()?->getName();
        $derivePageTitle = static function (?string $route): string {
            if (! is_string($route) || $route === '') {
                return 'Admin';
            }

            $segments = array_values(array_filter(
                explode('.', $route),
                static fn (string $segment): bool => $segment !== 'admin'
            ));

            if ($segments === []) {
                return 'Admin';
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

        $pageTitle = htmlspecialchars_decode(trim($__env->yieldContent('title')), ENT_QUOTES);

        if ($pageTitle === '') {
            $pageTitle = $derivePageTitle($routeName);
        }
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }} — NinoWorld</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    @include('admin.partials.theme-assets')
    @stack('styles')
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }
    </style>
</head>
<body data-theme="{{ $adminTheme['key'] ?? 'nino-v1' }}" class="min-h-screen bg-canvas text-slate-900 font-body antialiased selection:bg-indigo-100 selection:text-indigo-950">
    @impersonating
        <div class="fixed top-0 left-0 right-0 z-[100] bg-slate-800 text-white text-[10px] uppercase font-black tracking-[0.2em] py-1.5 px-4 flex justify-between items-center shadow-sm border-b border-white/10">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">person_search</span>
                    <span>Currently impersonating <strong class="text-[#CEDFD0]">{{ auth()->user()->name }}</strong></span>
                </div>
                <div class="h-3 w-px bg-white/20"></div>
                <a href="{{ route('impersonate.leave') }}" class="flex items-center gap-1.5 hover:text-[#CEDFD0] transition-colors group">
                    <span class="font-bold">Leave Session</span>
                    <span class="material-symbols-outlined text-sm group-hover:translate-x-0.5 transition-transform">logout</span>
                </a>
            </div>
            <div class="opacity-50 text-[9px] font-bold">Admin Privileges Active</div>
        </div>
    @endImpersonating

    <div x-data="{ sidebarOpen: false }" class="relative min-h-screen">
        <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-sm lg:hidden" x-on:click="sidebarOpen = false"></div>

        @include('admin.partials.sidebar')

        <div class="min-h-screen lg:pl-72">
            @include('admin.partials.topbar')

            <main class="px-4 pb-8 pt-20 sm:px-6 lg:px-8">
                {{-- Flash Messages --}}
                @if(session('success'))
                    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 shadow-sm">
                        {{ session('success') }}
                    </div>
                @endif

                @if(trim($__env->yieldContent('header')) !== '' || trim($__env->yieldContent('subheader')) !== '')
                    <section class="mb-6 sm:mb-8">
                        @hasSection('header')
                            <div class="text-slate-900">
                                @yield('header')
                            </div>
                        @endif

                        @hasSection('subheader')
                            <p class="mt-2 max-w-3xl text-sm text-slate-500">
                                @yield('subheader')
                            </p>
                        @endif
                    </section>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
