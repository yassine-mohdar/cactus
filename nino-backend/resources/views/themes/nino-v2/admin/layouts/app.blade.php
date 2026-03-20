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
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
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
<body data-theme="{{ $adminTheme['key'] ?? 'nino-v2' }}" class="theme-nino-v2 min-h-screen bg-canvas text-ink font-body antialiased selection:bg-[#DDEAE2] selection:text-[#17302A]">
    @impersonating
        <div class="fixed inset-x-0 top-0 z-[100] flex items-center justify-between border-b border-white/10 bg-[#17302A] px-4 py-2 text-[10px] font-black uppercase tracking-[0.22em] text-white shadow-[0_8px_18px_-12px_rgba(23,48,42,0.72)]">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">person_search</span>
                    <span>Impersonating <strong class="text-[#D7E5DB]">{{ auth()->user()->name }}</strong></span>
                </div>
                <div class="h-3 w-px bg-[#FCFBF8]/20"></div>
                <a href="{{ route('impersonate.leave') }}" class="group flex items-center gap-1.5 transition-colors hover:text-[#D7E5DB]">
                    <span class="font-bold">Leave Session</span>
                    <span class="material-symbols-outlined text-sm group-hover:translate-x-0.5 transition-transform">logout</span>
                </a>
            </div>
            <div class="hidden text-[9px] font-bold opacity-50 sm:block">Admin Privileges Active</div>
        </div>
    @endImpersonating

    <div
        x-data="{
            sidebarOpen: false,
            isDesktop: window.matchMedia('(min-width: 1024px)').matches,
            sidebarMediaQuery: null,
            init() {
                this.sidebarMediaQuery = window.matchMedia('(min-width: 1024px)');

                const syncDesktopState = (event) => {
                    this.isDesktop = event.matches;

                    if (event.matches) {
                        this.sidebarOpen = false;
                    }
                };

                syncDesktopState(this.sidebarMediaQuery);

                if (typeof this.sidebarMediaQuery.addEventListener === 'function') {
                    this.sidebarMediaQuery.addEventListener('change', syncDesktopState);
                } else {
                    this.sidebarMediaQuery.addListener(syncDesktopState);
                }
            },
            sidebarVisible() {
                return this.isDesktop || this.sidebarOpen;
            },
            openSidebar() {
                if (! this.isDesktop) {
                    this.sidebarOpen = true;
                }
            },
            closeSidebar() {
                if (! this.isDesktop) {
                    this.sidebarOpen = false;
                }
            },
        }"
        x-bind:class="{ 'overflow-hidden': ! isDesktop && sidebarOpen }"
        x-on:keydown.escape.window="closeSidebar()"
        class="relative min-h-screen overflow-x-hidden"
    >
        <div x-cloak x-show="! isDesktop && sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-[#17302A]/20 lg:hidden" x-on:click="closeSidebar()"></div>

        @include('admin.partials.sidebar')

        <div class="relative min-h-screen lg:pl-[17rem]">
            @include('admin.partials.topbar')

            <main class="px-4 pb-10 pt-20 sm:px-6 lg:px-8">
                <div class="mx-auto w-full max-w-[1600px]">
                    @if(session('success'))
                        <x-nino.inline-alert tone="success" title="Saved" class="mb-6">
                            {{ session('success') }}
                        </x-nino.inline-alert>
                    @endif

                    @if(trim($__env->yieldContent('header')) !== '' || trim($__env->yieldContent('subheader')) !== '')
                        <section class="mb-6 sm:mb-8">
                            @hasSection('header')
                                @yield('header')
                            @endif

                            @if(! $__env->hasSection('header') && $__env->hasSection('subheader'))
                                <p class="mt-3 max-w-3xl text-sm leading-6 text-[#61706B]">
                                    @yield('subheader')
                                </p>
                            @endif
                        </section>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
