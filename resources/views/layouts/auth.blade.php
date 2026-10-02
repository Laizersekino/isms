<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ trim($__env->yieldContent('title', 'Sign in').' - '.config('app.name', 'ISMS')) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10 font-sans text-slate-900">
    <main class="w-full max-w-md">
        <div class="mb-6 text-center">
            <a href="{{ route('home') }}" class="text-2xl font-bold tracking-tight text-primary-700">
                {{ config('app.name', 'Integrated School Management System') }}
            </a>
        </div>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-900/5 sm:p-8">
            <x-flash-messages />
            @yield('content')
        </section>
    </main>
</body>
</html>
