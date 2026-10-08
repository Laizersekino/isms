<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('school.name', config('app.name', 'ISMS')))</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset(config('school.favicon', 'favicon.svg')) }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] {
            display: none !important;
        }

        html,
        body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        body {
            min-height: 0;
        }

        /* Main application container */
        .app-container {
            display: flex;
            width: 100%;
            height: 100vh;
            height: 100dvh;
            overflow: hidden;
        }

        /* Sidebar scrolls independently */
        .sidebar-wrapper {
            height: 100%;
            min-height: 0;
            flex-shrink: 0;
            overflow-x: hidden;
            overflow-y: auto;
            overscroll-behavior-y: contain;
            scrollbar-gutter: stable;
        }

        /* Main page area */
        .main-wrapper {
            display: flex;
            flex: 1;
            flex-direction: column;
            min-width: 0;
            min-height: 0;
            height: 100%;
            overflow: hidden;
        }

        /* Only the main content area scrolls */
        .main-content {
            flex: 1;
            min-width: 0;
            min-height: 0;
            overflow-x: auto;
            overflow-y: auto;
            padding: 24px;
            overscroll-behavior-y: contain;
            scrollbar-gutter: stable;
        }

        /* Keep footer below the scrollable content */
        .app-footer {
            flex-shrink: 0;
        }

        /* Small screens */
        @media (max-width: 1023px) {
            .sidebar-wrapper {
                position: fixed;
                inset: 0 auto 0 0;
                z-index: 50;
                height: 100vh;
                height: 100dvh;
                max-width: 85vw;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }

            .main-content {
                padding: 16px;
            }
        }
    </style>
</head>

<body>
    <div class="app-container" x-data="{ sidebarOpen: false }">

        {{-- Mobile sidebar backdrop --}}
        <div
            x-cloak
            x-show="sidebarOpen"
            x-transition.opacity
            class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden"
            @click="sidebarOpen = false"
            @keydown.escape.window="sidebarOpen = false"
            aria-hidden="true"
        ></div>

        {{-- Sidebar: independent scrolling --}}
        <div
            class="sidebar-wrapper"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        >
            <x-sidebar />
        </div>

        {{-- Main application area --}}
        <div class="main-wrapper">

            {{-- Header stays outside the scrolling content --}}
            <x-header />

            {{-- Main page: independent scrolling --}}
            <main class="main-content">
                <div class="mx-auto max-w-7xl">
                    <x-flash-messages />
                    @yield('content')
                </div>
            </main>

            {{-- Footer stays below main content --}}
            <footer class="app-footer border-t border-slate-200 bg-white px-6 py-4 text-center text-sm text-slate-500">
                &copy; {{ now()->year }}
                {{ config('school.name', config('app.name', 'ISMS')) }}
            </footer>

        </div>
    </div>
</body>
</html>