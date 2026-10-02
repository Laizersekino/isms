<article class="class-performance-report">
    <header class="school-header">
        @if(!empty($school['logo_path']) && is_file(public_path($school['logo_path'])))
            <img src="{{ public_path($school['logo_path']) }}" alt="{{ $school['name'] ?? 'School' }} logo" class="school-logo">
        @endif
        <h1>{{ $school['name'] ?? config('app.name') }}</h1>
        @if(!empty($school['address']))<p>{{ $school['address'] }}</p>@endif
        @if(!empty($school['phone']))<p>{{ $school['phone'] }}</p>@endif
        @if(!empty($school['email']))<p>{{ $school['email'] }}</p>@endif
        <h2>Class Performance Report</h2>
    </header>

    <section class="report-context">
        <p><strong>Class:</strong> {{ $classRoom->name }}</p>
        <p><strong>Academic year:</strong> {{ $academicYear->name }}</p>
        <p><strong>Term:</strong> {{ $term->name }}</p>
        <p><strong>Total students:</strong> {{ $totalStudents }}</p>
        <p><strong>Students with approved marks:</strong> {{ $studentsWithMarks }}</p>
        <p><strong>Class average:</strong> {{ number_format($classAverage, 2) }}%</p>
        <p><strong>Pass rate:</strong> {{ number_format($passRate, 2) }}% (threshold: {{ number_format($passingThreshold, 2) }}%)</p>
    </section>

    @if($totalStudents === 0)
        <p class="empty-state">No students enrolled in this class for the selected academic year.</p>
    @elseif($studentsWithMarks === 0)
        <p class="empty-state">No results available for this class in the selected term.</p>
    @else
        <section>
            <h3>Subject breakdown</h3>
            <table>
                <thead>
                    <tr><th>Subject</th><th>Class average</th><th>Highest</th><th>Lowest</th><th>Pass rate</th><th>Students</th></tr>
                </thead>
                <tbody>
                    @forelse($subjects as $subject)
                        <tr>
                            <td>{{ $subject['name'] }}</td>
                            <td>{{ number_format($subject['average'], 2) }}%</td>
                            <td>{{ number_format($subject['highest'], 2) }}%</td>
                            <td>{{ number_format($subject['lowest'], 2) }}%</td>
                            <td>{{ number_format($subject['pass_rate'], 2) }}%</td>
                            <td>{{ $subject['student_count'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No subject results available.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section>
            <h3>Top performers</h3>
            @if($topPerformers->isNotEmpty())
                <table>
                    <thead><tr><th>Rank</th><th>Student</th><th>Admission number</th><th>Average</th><th>Grade</th></tr></thead>
                    <tbody>
                        @foreach($topPerformers as $student)
                            <tr>
                                <td>{{ $student['rank'] }}</td>
                                <td>{{ $student['name'] }}</td>
                                <td>{{ $student['admission_number'] }}</td>
                                <td>{{ number_format($student['average'], 2) }}%</td>
                                <td>{{ $student['grade'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p>No results available.</p>
            @endif
        </section>

        <section>
            <h3>Weak areas</h3>
            @if($weakAreas->isNotEmpty())
                <ul>
                    @foreach($weakAreas as $subject)
                        <li>{{ $subject['name'] }} — {{ number_format($subject['average'], 2) }}% average</li>
                    @endforeach
                </ul>
            @else
                <p>No subject results available.</p>
            @endif
            <h4>Students needing support (below {{ number_format($passingThreshold, 2) }}%)</h4>
            @if($studentsNeedingSupport->isNotEmpty())
                <ul>
                    @foreach($studentsNeedingSupport as $student)
                        <li>{{ $student['name'] }} ({{ $student['admission_number'] }}) — {{ number_format($student['average'], 2) }}%</li>
                    @endforeach
                </ul>
            @else
                <p>No students are below the passing threshold.</p>
            @endif
        </section>

        <section>
            <h3>Grade distribution</h3>
            <table>
                <thead><tr><th>Grade</th><th>Students</th><th>Percentage</th></tr></thead>
                <tbody>
                    @foreach($gradeDistribution as $grade)
                        <tr>
                            <td>{{ $grade['code'] }}</td>
                            <td>{{ $grade['count'] }}</td>
                            <td>{{ number_format($grade['percentage'], 2) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

    <footer><p>Generated {{ now()->format('F j, Y') }}</p></footer>
</article>
