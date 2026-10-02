<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Finance Report')</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        h1 { font-size: 20px; margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #bbb; padding: 6px; text-align: left; }
        th { background: #eee; }
        .summary { margin: 8px 0; }
    </style>
</head>
<body>
    @yield('content')
</body>
</html>
