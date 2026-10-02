<article class="report-card">
    <header class="school-header">
        @if(!empty($school['logo_path']) && is_file(public_path($school['logo_path'])))
            <img src="{{ public_path($school['logo_path']) }}" alt="{{ $school['name'] ?? 'School' }} logo" class="school-logo">
        @endif
        <h1>{{ $school['name'] ?? config('app.name') }}</h1>
        @if(!empty($school['address']))<p>{{ $school['address'] }}</p>@endif
        @if(!empty($school['phone']))<p>{{ $school['phone'] }}</p>@endif
        @if(!empty($school['email']))<p>{{ $school['email'] }}</p>@endif
        <h2>Student Academic Report Card</h2>
    </header>

    <section class="student-details">
        <p><strong>Student:</strong> {{ implode(' ', array_filter([$student->first_name, $student->middle_name, $student->last_name])) }}</p>
        <p><strong>Admission number:</strong> {{ $student->admission_number }}</p>
        <p><strong>Class:</strong> {{ $enrollment->classRoom->name }}</p>
        <p><strong>Stream:</strong> {{ $enrollment->stream?->name ?? 'N/A' }}</p>
        <p><strong>Academic year:</strong> {{ $academicYear->name }}</p>
        <p><strong>Term:</strong> {{ $term->name }}</p>
    </section>

    <h3>Subject results</h3>
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
        <h3>Academic summary</h3>
        <p><strong>Total:</strong> {{ number_format($totalMarks, 2) }} / {{ number_format($maximumMarks, 2) }}</p>
        <p><strong>Average:</strong> {{ number_format($percentage, 2) }}%</p>
        <p><strong>Overall grade:</strong> {{ $overallGrade }}</p>
        <p><strong>Class rank:</strong> {{ $rank ? $rank.' / '.$classSize : 'N/A' }}</p>
    </section>

    <section class="attendance">
        <h3>Attendance summary</h3>
        <p><strong>Recorded days:</strong> {{ $attendance['total'] }}</p>
        <p><strong>Present:</strong> {{ $attendance['present'] }}</p>
        <p><strong>Late:</strong> {{ $attendance['late'] }}</p>
        <p><strong>Absent:</strong> {{ $attendance['absent'] }}</p>
        <p><strong>Excused:</strong> {{ $attendance['excused'] }}</p>
        <p><strong>Attendance rate:</strong> {{ number_format($attendance['percentage'], 2) }}% (present and late count as attended)</p>
    </section>

    <footer>
        <p>Generated {{ now()->format('F j, Y') }}</p>
        <p class="signature">Class teacher: ____________________ &nbsp;&nbsp; School stamp: ____________________</p>
    </footer>
</article>
