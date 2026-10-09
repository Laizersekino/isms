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
        <h2 class="text-lg font-semibold text-primary-700 mt-4">Class Performance Report</h2>
    </header>

    {{-- Report Context --}}
    <section class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="space-y-2">
            <p class="text-sm"><strong class="text-slate-700">Class:</strong> <span class="text-slate-900">{{ $classRoom->name }}</span></p>
            <p class="text-sm"><strong class="text-slate-700">Academic Year:</strong> <span class="text-slate-900">{{ $academicYear->name }}</span></p>
            <p class="text-sm"><strong class="text-slate-700">Term:</strong> <span class="text-slate-900">{{ $term->name }}</span></p>
            <p class="text-sm"><strong class="text-slate-700">Total Students:</strong> <span class="text-slate-900">{{ $totalStudents }}</span></p>
        </div>
        <div class="space-y-2">
            <p class="text-sm"><strong class="text-slate-700">Students with Approved Marks:</strong> <span class="text-slate-900">{{ $studentsWithMarks }}</span></p>
            <p class="text-sm"><strong class="text-slate-700">Class Average:</strong> <span class="text-slate-900">{{ number_format($classAverage, 2) }}%</span></p>
            <p class="text-sm"><strong class="text-slate-700">Pass Rate:</strong> <span class="text-slate-900">{{ number_format($passRate, 2) }}%</span></p>
            <p class="text-sm"><strong class="text-slate-700">Passing Threshold:</strong> <span class="text-slate-900">{{ number_format($passingThreshold, 2) }}%</span></p>
        </div>
    </section>

    @if($totalStudents === 0)
        <div class="text-center py-8 bg-slate-50 rounded-xl border border-slate-200">
            <p class="text-sm text-slate-500">No students enrolled in this class for the selected academic year.</p>
        </div>
    @elseif($studentsWithMarks === 0)
        <div class="text-center py-8 bg-slate-50 rounded-xl border border-slate-200">
            <p class="text-sm text-slate-500">No results available for this class in the selected term.</p>
        </div>
    @else
        {{-- Subject Breakdown --}}
        <section class="mb-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Subject Breakdown</h3>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th scope="col" class="px-4 py-3">Subject</th>
                            <th scope="col" class="px-4 py-3">Class Average</th>
                            <th scope="col" class="px-4 py-3">Highest</th>
                            <th scope="col" class="px-4 py-3">Lowest</th>
                            <th scope="col" class="px-4 py-3">Pass Rate</th>
                            <th scope="col" class="px-4 py-3">Students</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($subjects as $subject)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">{{ $subject['name'] }}</td>
                                <td class="px-4 py-3">{{ number_format($subject['average'], 2) }}%</td>
                                <td class="px-4 py-3">{{ number_format($subject['highest'], 2) }}%</td>
                                <td class="px-4 py-3">{{ number_format($subject['lowest'], 2) }}%</td>
                                <td class="px-4 py-3">{{ number_format($subject['pass_rate'], 2) }}%</td>
                                <td class="px-4 py-3">{{ $subject['student_count'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No subject results available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Top Performers --}}
        <section class="mb-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Top Performers</h3>
            @if($topPerformers->isNotEmpty())
                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                            <tr>
                                <th scope="col" class="px-4 py-3">Rank</th>
                                <th scope="col" class="px-4 py-3">Student</th>
                                <th scope="col" class="px-4 py-3">Admission Number</th>
                                <th scope="col" class="px-4 py-3">Average</th>
                                <th scope="col" class="px-4 py-3">Grade</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach($topPerformers as $student)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3">{{ $student['rank'] }}</td>
                                    <td class="px-4 py-3">{{ $student['name'] }}</td>
                                    <td class="px-4 py-3">{{ $student['admission_number'] }}</td>
                                    <td class="px-4 py-3">{{ number_format($student['average'], 2) }}%</td>
                                    <td class="px-4 py-3">
                                        <x-badge variant="success">{{ $student['grade'] }}</x-badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8 bg-slate-50 rounded-xl border border-slate-200">
                    <p class="text-sm text-slate-500">No results available.</p>
                </div>
            @endif
        </section>

        {{-- Weak Areas --}}
        <section class="mb-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Weak Areas</h3>
            @if($weakAreas->isNotEmpty())
                <ul class="space-y-2">
                    @foreach($weakAreas as $subject)
                        <li class="text-sm text-slate-700">
                            <x-badge variant="warning">{{ $subject['name'] }}</x-badge>
                            <span class="ml-2">{{ number_format($subject['average'], 2) }}% average</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="text-center py-8 bg-slate-50 rounded-xl border border-slate-200">
                    <p class="text-sm text-slate-500">No subject results available.</p>
                </div>
            @endif

            <h4 class="text-md font-semibold text-slate-900 mt-4 mb-2">Students Needing Support (below {{ number_format($passingThreshold, 2) }}%)</h4>
            @if($studentsNeedingSupport->isNotEmpty())
                <ul class="space-y-2">
                    @foreach($studentsNeedingSupport as $student)
                        <li class="text-sm text-slate-700">
                            <x-badge variant="danger">{{ $student['name'] }}</x-badge>
                            <span class="ml-2">{{ $student['admission_number'] }} — {{ number_format($student['average'], 2) }}%</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="text-center py-8 bg-slate-50 rounded-xl border border-slate-200">
                    <p class="text-sm text-slate-500">No students are below the passing threshold.</p>
                </div>
            @endif
        </section>

        {{-- Grade distribution --}}
        <section class="mb-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Grade Distribution</h3>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th scope="col" class="px-4 py-3">Grade</th>
                            <th scope="col" class="px-4 py-3">Students</th>
                            <th scope="col" class="px-4 py-3">Percentage</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($gradeDistribution as $grade)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <x-badge variant="info">{{ $grade['code'] }}</x-badge>
                                </td>
                                <td class="px-4 py-3">{{ $grade['count'] }}</td>
                                <td class="px-4 py-3">{{ number_format($grade['percentage'], 2) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <footer class="border-t border-slate-200 pt-6 mt-6">
        <p class="text-sm text-slate-500">Generated {{ now()->format('F j, Y') }}</p>
    </footer>
</article>