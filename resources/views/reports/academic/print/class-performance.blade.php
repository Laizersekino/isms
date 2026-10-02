<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class performance — {{ $classRoom->name }}</title>
    <style>
        body { color: #222; font-family: Arial, sans-serif; margin: 28px auto; max-width: 1000px; }
        .school-header { text-align: center; margin-bottom: 20px; }
        .school-header p { margin: 3px; }
        .school-logo { max-height: 70px; max-width: 120px; }
        .report-context { display: grid; grid-template-columns: 1fr 1fr; gap: 0 24px; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0 20px; }
        th, td { border: 1px solid #999; padding: 7px; text-align: left; }
        th { background: #eee; }
        .empty-state { border: 1px solid #999; padding: 12px; }
        @media print { body { margin: 0; max-width: none; } }
    </style>
</head>
<body>
    @include('reports.academic.partials.class-performance-content')
    <script>window.addEventListener('load', () => window.print());</script>
</body>
</html>
