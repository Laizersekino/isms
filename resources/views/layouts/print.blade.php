<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'ISMS'))</title>
    <style>
        body { color: #1f2937; font: 12px Arial, sans-serif; margin: 24px auto; max-width: 1000px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #cbd5e1; padding: 7px; text-align: left; }
        @media print { body { margin: 0; max-width: none; } .no-print { display: none !important; } }
    </style>
    @stack('styles')
</head>
<body>
    <main>@yield('content')</main>
    @stack('scripts')
</body>
</html>
