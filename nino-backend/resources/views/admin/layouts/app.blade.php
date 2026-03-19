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
<body class="bg-surface font-sans text-ink antialiased flex h-screen overflow-hidden">
    
    @impersonating
    <div class="fixed top-0 left-0 right-0 z-50 bg-ink text-white px-4 py-2 flex items-center justify-between text-sm shadow-md">
        <div class="flex items-center space-x-2">
            <svg class="w-5 h-5 text-sage" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
            <span class="font-medium">You are currently impersonating <strong>{{ auth()->user()->name }}</strong>.</span>
        </div>
        <a href="{{ route('impersonate.leave') }}" class="px-3 py-1 bg-white text-ink rounded hover:bg-cream transition font-bold shadow-sm">
            Leave Impersonation
        </a>
    </div>
    @endImpersonating

    <div class="flex h-screen w-full @impersonating pt-10 @endImpersonating">
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
