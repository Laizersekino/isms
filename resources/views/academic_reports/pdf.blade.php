<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Academic report card — {{ $student->admission_number }}</title>
    <style>
        body { color: #222; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .school-header { text-align: center; margin-bottom: 18px; }
        .school-header p { margin: 2px; }
        .school-logo { max-height: 60px; max-width: 100px; }
        .student-details { width: 100%; }
        .student-details p { display: inline-block; width: 48%; margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0 18px; }
        th, td { border: 1px solid #999; padding: 5px; text-align: left; }
        th { background: #eee; }
        .summary, .attendance { display: inline-block; vertical-align: top; width: 48%; }
        footer { margin-top: 24px; }
    </style>
</head>
<body>
    @include('academic_reports._report_card')
</body>
</html>
