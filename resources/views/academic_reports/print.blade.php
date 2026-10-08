@extends('layouts.print')

@section('title', 'Academic Report Card - ' . $student->first_name . ' ' . $student->last_name)

@section('content')
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { font-size: 20px; margin: 0; }
        .header h2 { font-size: 14px; margin: 5px 0 0 0; color: #666; }
        .student-details { margin-bottom: 20px; }
        .student-details p { margin: 3px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f4f4f4; }
        .summary { margin-top: 20px; }
        .summary p { margin: 3px 0; }
        .footer { margin-top: 30px; border-top: 1px solid #333; padding-top: 10px; font-size: 10px; color: #666; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; background: #e0e7ff; color: #3730a3; font-size: 11px; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .text-success { color: #059669; }
        .text-warning { color: #d97706; }
        .text-danger { color: #dc2626; }
    </style>

    <article>
        <header class="header">
            @if(!empty($school['logo_path']) && is_file(public_path($school['logo_path'])))
                <img src="{{ public_path($school['logo_path']) }}" alt="{{ $school['name'] ?? 'School' }} logo" style="height: 60px; margin-bottom: 10px;">
            @endif
            <h1>{{ $school['name'] ?? config('app.name') }}</h1>
            @if(!empty($school['address']))<p>{{ $school['address'] }}</p>@endif
            @if(!empty($school['phone']))<p>{{ $school['phone'] }}</p>@endif
            @if(!empty($school['email']))<p>{{ $school['email'] }}</p>@endif
            <h2>Student Academic Report Card</h2>
        </header>

        <section class="student-details">
            <p><strong>Student:</strong> {{ implode(' ', array_filter([$student->first_name, $student->middle_name, $student->last_name])) }}</p>
            <p><strong>Admission Number:</strong> {{ $student->admission_number }}</p>
            <p><strong>Class:</strong> {{ $enrollment->classRoom->name }}</p>
            <p><strong>Stream:</strong> {{ $enrollment->stream?->name ?? 'N/A' }}</p>
            <p><strong>Academic Year:</strong> {{ $academicYear->name }}</p>
            <p><strong>Term:</strong> {{ $term->name }}</p>
        </section>

        <h3>Subject Results</h3>
        @if($marks->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <th>Exam</th>
                        <th>Subject</th>
                        <th>Marks</th>
                        <th>Percentage</th>
                        <th>Grade</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($marks as $mark)
                        <tr>
                            <td>{{ $mark->examSubject->exam->name }}</td>
                            <td>{{ $mark->examSubject->subject->name }}</td>
                            <td>{{ number_format((float) $mark->marks_obtained, 2) }} / {{ number_format((float) $mark->examSubject->max_marks, 2) }}</td>
                            <td>{{ number_format($mark->report_percentage, 2) }}%</td>
                            <td>{{ $mark->report_grade }}</td>
                            <td>{{ $mark->remarks ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>No approved marks are available for this student in the selected term.</p>
        @endif

        <section class="summary">
            <h3>Academic Summary</h3>
            <p><strong>Total:</strong> {{ number_format($totalMarks, 2) }} / {{ number_format($maximumMarks, 2) }}</p>
            <p><strong>Average:</strong> {{ number_format($percentage, 2) }}%</p>
            <p><strong>Overall Grade:</strong> {{ $overallGrade }}</p>
            <p><strong>Class Rank:</strong> {{ $rank ? $rank.' / '.$classSize : 'N/A' }}</p>
        </section>

        <section class="summary">
            <h3>Attendance Summary</h3>
            <p><strong>Recorded Days:</strong> {{ $attendance['total'] }}</p>
            <p><strong>Present:</strong> {{ $attendance['present'] }}</p>
            <p><strong>Late:</strong> {{ $attendance['late'] }}</p>
            <p><strong>Absent:</strong> {{ $attendance['absent'] }}</p>
            <p><strong>Excused:</strong> {{ $attendance['excused'] }}</p>
            <p><strong>Attendance Rate:</strong> {{ number_format($attendance['percentage'], 2) }}%</p>
        </section>

        <footer class="footer">
            <p>Generated {{ now()->format('F j, Y') }}</p>
            <p>Class Teacher: ____________________ &nbsp;&nbsp; School Stamp: ____________________</p>
        </footer>
    </article>
@endsection