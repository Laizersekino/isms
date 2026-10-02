<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>@yield('title', config('app.name', 'ISMS'))</title>
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
