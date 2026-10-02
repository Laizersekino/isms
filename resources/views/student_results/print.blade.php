<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Result Slip</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            color: #1f2937;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 10px;
            text-align: left;
        }
        th {
            background: #f3f4f6;
        }
        .header {
            margin-bottom: 20px;
        }
        .meta {
            margin-bottom: 12px;
        }
        @media print {
            body { margin: 0; }
            a { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Academic Result Slip</h1>
        <p class="meta"><strong>Student:</strong> {{ $student->first_name }} {{ $student->last_name }}</p>
        <p class="meta"><strong>Admission Number:</strong> {{ $student->admission_number }}</p>
        <p class="meta"><strong>Exam:</strong> {{ $selectedExam->exam->name ?? 'N/A' }}</p>
        <p class="meta"><strong>Class:</strong> {{ $selectedExam->classRoom->name ?? 'N/A' }}</p>
    </div>

    @if($resultRows->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Marks</th>
                    <th>Grade</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($resultRows as $mark)
                    <tr>
                        <td>{{ $mark->examSubject->subject->name ?? 'Unknown subject' }}</td>
                        <td>{{ $mark->marks_obtained }} / {{ $mark->examSubject->max_marks }}</td>
                        <td>{{ $mark->grade }}</td>
                        <td>{{ $mark->status }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No result records found for this exam.</p>
    @endif

    <p style="margin-top: 20px;">
        <a href="javascript:window.print()">Print</a>
    </p>
</body>
</html>
