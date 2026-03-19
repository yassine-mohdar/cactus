<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — NinoWorld</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>
<body class="bg-cream font-sans text-ink antialiased">
    <div class="flex h-screen overflow-hidden">
        {{-- Sidebar --}}
        @include('admin.partials.sidebar')

        {{-- Main Content --}}
        <div class="flex flex-1 flex-col overflow-hidden">
            {{-- Topbar --}}
            @include('admin.partials.topbar')

            {{-- Page Content --}}
            <main class="flex-1 overflow-y-auto p-6">
                {{-- Flash Messages --}}
                @if(session('success'))
                    <div class="mb-4 rounded-lg border border-success/20 bg-success-light px-4 py-3 text-sm text-success">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-4 rounded-lg border border-danger/20 bg-danger-light px-4 py-3 text-sm text-danger">
                        {{ session('error') }}
                    </div>
                @endif

                {{-- Page Header --}}
                @hasSection('header')
                    <div class="mb-6">
                        <h1 class="text-xl font-bold text-ink">@yield('header')</h1>
                        @hasSection('subheader')
                            <p class="mt-1 text-sm text-ink-muted">@yield('subheader')</p>
                        @endif
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
    @livewireScripts
    @stack('scripts')
</body>
</html>
