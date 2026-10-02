@php
    $user = auth()->user();
    $menuGroups = [
        'School' => [
            ['Students', 'students.index', 'students.view'],
            ['Enrollments', 'enrollments.index', 'students.view'],
            ['Teachers', 'teachers.index', 'teachers.view'],
        ],
        'Academics' => [
            ['Classes', 'classes.index', 'academic_structure.update'],
            ['Terms', 'terms.index', 'academic_structure.update'],
            ['Streams', 'streams.index', 'academic_structure.update'],
            ['Attendance', 'attendance.index', 'attendance.view'],
            ['Exams and marks', 'exams.index', 'marks.view'],
            ['Student Results', 'student-results.index', 'marks.view'],
        ],
        'Library' => [
            ['Books', 'books.index', 'books.view'],
            ['Book copies', 'book-copies.index', 'book_copies.view'],
            ['Borrowings', 'book-borrowings.index', 'borrowings.view'],
            ['Fines', 'library-fines.index', 'fines.view'],
        ],
        'Finance' => [
            ['Fee structures', 'fee-structures.index', 'fee_structures.view'],
            ['Student fees', 'student-fees.index', 'student_fees.view'],
            ['Payments', 'payments.index', 'payments.view'],
            ['Finance reports', 'reports.finance.collection-summary', 'reports.finance.view'],
        ],
        'Communication' => [
            ['Announcements', 'announcements.index', 'announcements.view'],
        ],
    ];
    $canSeeResults = $user && (
        $user->hasRole('Student')
        || $user->hasRole('Parent')
        || $user->hasPermission('student.portal')
        || $user->hasPermission('parent.portal')
        || $user->hasPermission('students.view')
        || $user->hasPermission('reports.view')
        || $user->hasPermission('marks.view')
    );
@endphp

<aside
    id="app-sidebar"
    class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-slate-800 bg-slate-950 text-slate-100 transition-transform duration-200 lg:static lg:z-auto lg:translate-x-0"
    :class="{ 'translate-x-0': sidebarOpen }"
    :aria-hidden="!sidebarOpen && window.innerWidth < 1024"
>
    <div class="flex h-16 items-center justify-between border-b border-white/10 px-5">
        <a href="{{ route('dashboard') }}" class="font-semibold tracking-wide text-white">
            {{ config('app.name', 'ISMS') }}
        </a>
        <button type="button" class="rounded-lg p-2 text-slate-300 hover:bg-white/10 lg:hidden" @click="sidebarOpen = false" aria-label="Close navigation">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5" aria-label="Main navigation">
        <a href="{{ route('dashboard') }}" @class([
            'block rounded-lg px-3 py-2.5 text-sm font-medium transition',
            'bg-primary-600 text-white' => request()->routeIs('dashboard'),
            'text-slate-300 hover:bg-white/10 hover:text-white' => !request()->routeIs('dashboard'),
        ])>Dashboard</a>

        @foreach($menuGroups as $group => $items)
            @php
                $visibleItems = collect($items)->filter(fn ($item) => $user
                    && $user->hasPermission($item[2])
                    && \Illuminate\Support\Facades\Route::has($item[1]));
            @endphp
            @if($visibleItems->isNotEmpty())
                <div>
                    <h2 class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $group }}</h2>
                    <ul class="space-y-1">
                        @foreach($visibleItems as [$label, $routeName])
                            <li>
                                <a href="{{ route($routeName) }}"
                                    @class([
                                        'block rounded-lg px-3 py-2 text-sm transition',
                                        'bg-white/10 text-white' => request()->routeIs($routeName),
                                        'text-slate-300 hover:bg-white/10 hover:text-white' => !request()->routeIs($routeName),
                                    ])>
                                    {{ $label }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach

        @if($canSeeResults && \Illuminate\Support\Facades\Route::has('student-results.index') && !$user->hasPermission('marks.view'))
            <div>
                <h2 class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">My portal</h2>
                <a href="{{ route('student-results.index') }}" class="block rounded-lg px-3 py-2 text-sm text-slate-300 hover:bg-white/10 hover:text-white">Student Results</a>
            </div>
        @endif
    </nav>
</aside>
