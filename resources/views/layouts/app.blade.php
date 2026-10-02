<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'ISMS'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-900 antialiased">
    <div class="min-h-screen lg:flex" x-data="{ sidebarOpen: false }">
        <div
            x-cloak
            x-show="sidebarOpen"
            class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden"
            @click="sidebarOpen = false"
            @keydown.escape.window="sidebarOpen = false"
            aria-hidden="true"
        ></div>

        <x-sidebar />

        <div class="flex min-h-screen min-w-0 flex-1 flex-col">
            <x-header />

            <main class="w-full flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <x-flash-messages />
                    @yield('content')
                </div>
            </main>

            <footer class="border-t border-slate-200 bg-white px-6 py-4 text-center text-sm text-slate-500">
                &copy; {{ now()->year }} {{ config('app.name', 'ISMS') }}
            </footer>
        </div>
    </div>
</body>
</html>
