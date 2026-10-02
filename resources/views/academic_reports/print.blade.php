<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic report card — {{ $student->admission_number }}</title>
    <style>
        body { color: #222; font-family: Arial, sans-serif; margin: 28px auto; max-width: 900px; }
        .school-header { text-align: center; margin-bottom: 24px; }
        .school-header p { margin: 3px; }
        .school-logo { max-height: 70px; max-width: 120px; }
        .student-details { display: grid; grid-template-columns: 1fr 1fr; gap: 0 24px; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0 24px; }
        th, td { border: 1px solid #999; padding: 8px; text-align: left; }
        th { background: #eee; }
        .summary, .attendance { display: inline-block; vertical-align: top; width: 48%; }
        footer { margin-top: 32px; }
        @media print { body { margin: 0; max-width: none; } }
    </style>
</head>
<body>
    @include('academic_reports._report_card')
    <script>window.addEventListener('load', () => window.print());</script>
</body>
</html>
