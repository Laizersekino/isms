<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Class performance — {{ $classRoom->name }}</title>
    <style>
        body { color: #222; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .school-header { text-align: center; margin-bottom: 16px; }
        .school-header p { margin: 2px; }
        .school-logo { max-height: 55px; max-width: 100px; }
        .report-context { width: 100%; }
        .report-context p { display: inline-block; width: 48%; margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0 16px; }
        th, td { border: 1px solid #999; padding: 5px; text-align: left; }
        th { background: #eee; }
        h3 { margin: 14px 0 6px; }
        .empty-state { border: 1px solid #999; padding: 10px; }
    </style>
</head>
<body>
    @include('reports.academic.partials.class-performance-content')
</body>
</html>
