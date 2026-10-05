<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>@yield('title', config('school.name', config('app.name', 'ISMS')))</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset(config('school.favicon', 'favicon.svg')) }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <style>
        body { color: #222; font: 10px DejaVu Sans, sans-serif; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #999; padding: 5px; text-align: left; }
        h1 { font-size: 18px; }
    </style>
    @stack('styles')
</head>
<body>
    <main>@yield('content')</main>
</body>
</html>
