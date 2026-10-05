<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ trim($__env->yieldContent('title', 'Sign in').' - '.config('school.name', config('app.name', 'ISMS'))) }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset(config('school.favicon', 'favicon.svg')) }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10 font-sans text-slate-900">
    <main class="w-full max-w-md">
        <div class="mb-6 text-center">
            <a href="{{ route('home') }}" class="inline-flex items-center justify-center gap-3 text-2xl font-bold tracking-tight text-primary-700">
                @if(config('school.logo'))
                    <img src="{{ asset(config('school.logo')) }}" alt="{{ config('school.name', config('app.name', 'ISMS')) }}" class="size-10 rounded-lg object-cover">
                @else
                    <x-icon name="academic-cap" size="lg" />
                @endif
                <span>{{ config('school.name', config('app.name', 'Integrated School Management System')) }}</span>
            </a>
        </div>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-900/5 sm:p-8">
            <x-flash-messages />
            @yield('content')
        </section>
    </main>
</body>
</html>
