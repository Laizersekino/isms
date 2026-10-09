<article class="bg-white rounded-xl border border-slate-200 shadow-sm p-8">
    {{-- School Header --}}
    <header class="text-center border-b border-slate-200 pb-6 mb-6">
        @if(!empty($school['logo_path']) && is_file(public_path($school['logo_path'])))
            <img src="{{ public_path($school['logo_path']) }}" alt="{{ $school['name'] ?? 'School' }} logo" class="h-16 mx-auto mb-4">
        @endif
        <h1 class="text-2xl font-bold text-slate-900">{{ $school['name'] ?? config('app.name') }}</h1>
        @if(!empty($school['address']))<p class="text-sm text-slate-600 mt-1">{{ $school['address'] }}</p>@endif
        @if(!empty($school['phone']))<p class="text-sm text-slate-600">{{ $school['phone'] }}</p>@endif
        @if(!empty($school['email']))<p class="text-sm text-slate-600">{{ $school['email'] }}</p>@endif
        <h2 class="text-lg font-semibold text-primary-700 mt-4">Student Academic Report Card</h2>
    </header>

    {{-- Student Details --}}
    <section class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="space-y-2">
            <p class="text-sm"><strong class="text-slate-700">Student:</strong> <span class="text-slate-900">{{ implode(' ', array_filter([$student->first_name, $student->middle_name, $student->last_name])) }}</span></p>
            <p class="text-sm"><strong class="text-slate-700">Admission Number:</strong> <span class="text-slate-900">{{ $student->admission_number }}</span></p>
            <p class="text-sm"><strong class="text-slate-700">Class:</strong> <span class="text-slate-900">{{ $enrollment->classRoom->name }}</span></p>
        </div>
        <div class="space-y-2">
            <p class="text-sm"><strong class="text-slate-700">Stream:</strong> <span class="text-slate-900">{{ $enrollment->stream?->name ?? 'N/A' }}</span></p>
            <p class="text-sm"><strong class="text-slate-700">Academic Year:</strong> <span class="text-slate-900">{{ $academicYear->name }}</span></p>
            <p class="text-sm"><strong class="text-slate-700">Term:</strong> <span class="text-slate-900">{{ $term->name }}</span></p>
        </div>
    </section>

    {{-- Subject Results --}}
    <section class="mb-6">
        <h3 class="text-lg font-semibold text-slate-900 mb-4">Subject Results</h3>
        @if($marks->isNotEmpty())
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th scope="col" class="px-4 py-3">Exam</th>
                            <th scope="col" class="px-4 py-3">Subject</th>
                            <th scope="col" class="px-4 py-3">Marks</th>
                            <th scope="col" class="px-4 py-3">Percentage</th>
                            <th scope="col" class="px-4 py-3">Grade</th>
                            <th scope="col" class="px-4 py-3">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($marks as $mark)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">{{ $mark->examSubject->exam->name }}</td>
                                <td class="px-4 py-3">{{ $mark->examSubject->subject->name }}</td>
                                <td class="px-4 py-3">{{ number_format((float) $mark->marks_obtained, 2) }} / {{ number_format((float) $mark->examSubject->max_marks, 2) }}</td>
                                <td class="px-4 py-3">{{ number_format($mark->report_percentage, 2) }}%</td>
                                <td class="px-4 py-3">
                                    <x-badge variant="info">{{ $mark->report_grade }}</x-badge>
                                </td>
                                <td class="px-4 py-3">{{ $mark->remarks ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8 bg-slate-50 rounded-xl border border-slate-200">
                <p class="text-sm text-slate-500">No approved marks are available for this student in the selected term.</p>
            </div>
        @endif
    </section>

    {{-- Academic Summary & Attendance --}}
    <section class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="rounded-xl border border-slate-200 p-4">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Academic Summary</h3>
            <div class="space-y-2">
                <p class="text-sm"><strong class="text-slate-700">Total:</strong> <span class="text-slate-900">{{ number_format($totalMarks, 2) }} / {{ number_format($maximumMarks, 2) }}</span></p>
                <p class="text-sm"><strong class="text-slate-700">Average:</strong> <span class="text-slate-900">{{ number_format($percentage, 2) }}%</span></p>
                <p class="text-sm"><strong class="text-slate-700">Overall Grade:</strong> <x-badge variant="success">{{ $overallGrade }}</x-badge></p>
                <p class="text-sm"><strong class="text-slate-700">Class rank:</strong> <span class="text-slate-900">{{ $rank ? $rank.' / '.$classSize : 'N/A' }}</span></p>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 p-4">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Attendance Summary</h3>
            <div class="space-y-2">
                <p class="text-sm"><strong class="text-slate-700">Recorded Days:</strong> <span class="text-slate-900">{{ $attendance['total'] }}</span></p>
                <p class="text-sm"><strong class="text-slate-700">Present:</strong> <span class="text-success-600">{{ $attendance['present'] }}</span></p>
                <p class="text-sm"><strong class="text-slate-700">Late:</strong> <span class="text-warning-600">{{ $attendance['late'] }}</span></p>
                <p class="text-sm"><strong class="text-slate-700">Absent:</strong> <span class="text-danger-600">{{ $attendance['absent'] }}</span></p>
                <p class="text-sm"><strong class="text-slate-700">Excused:</strong> <span class="text-slate-900">{{ $attendance['excused'] }}</span></p>
                <p class="text-sm"><strong class="text-slate-700">Attendance Rate:</strong> <span class="text-slate-900">{{ number_format($attendance['percentage'], 2) }}%</span></p>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="border-t border-slate-200 pt-6 mt-6">
        <p class="text-sm text-slate-500">Generated {{ now()->format('F j, Y') }}</p>
        <p class="text-sm text-slate-500 mt-2">Class Teacher: ____________________ &nbsp;&nbsp; School Stamp: ____________________</p>
    </footer>
</article>