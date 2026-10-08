@php
    $user = auth()->user();
    $menuGroups = [
        'School' => [
            ['Students', 'students.index', 'students.view', 'users'],
            ['Enrollments', 'enrollments.index', 'students.view', 'user-group'],
            ['Teachers', 'teachers.index', 'teachers.view', 'academic-cap'],
        ],
        'Academics' => [
            ['Classes', 'classes.index', 'academic_structure.update', 'home'],
            ['Terms', 'terms.index', 'academic_structure.update', 'calendar-days'],
            ['Streams', 'streams.index', 'academic_structure.update', 'user-group'],
            ['Attendance', 'attendance.index', 'attendance.view', 'clipboard-document-check'],
            ['Exams and marks', 'exams.index', 'marks.view', 'chart-bar'],
            ['Student Results', 'student-results.index', 'marks.view', 'academic-cap'],
        ],
        'Library' => [
            ['Books', 'books.index', 'books.view', 'book-open'],
            ['Book copies', 'book-copies.index', 'book_copies.view', 'books'],
            ['Borrowings', 'book-borrowings.index', 'borrowings.view', 'clipboard-document-check'],
            ['Fines', 'library-fines.index', 'fines.view', 'currency-dollar'],
        ],
        'Finance' => [
            ['Fee structures', 'fee-structures.index', 'fee_structures.view', 'currency-dollar'],
            ['Student fees', 'student-fees.index', 'student_fees.view', 'users'],
            ['Payments', 'payments.index', 'payments.view', 'currency-dollar'],
            ['Finance reports', 'reports.finance.collection-summary', 'reports.finance.view', 'chart-bar'],
        ],
        'Communication' => [
            ['Announcements', 'announcements.index', 'announcements.view', 'megaphone'],
        ],
        'Discipline' => [
            ['Cases', 'discipline.index', 'discipline.view', 'clipboard-document-check'],
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
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 font-semibold tracking-wide text-white">
            @if(config('school.logo'))
                <img src="{{ asset(config('school.logo')) }}" alt="{{ config('school.name', config('app.name', 'ISMS')) }}" class="size-9 rounded-lg object-cover">
            @else
                <x-icon name="academic-cap" size="lg" class="text-primary-300" />
            @endif
            <span>{{ config('school.name', config('app.name', 'ISMS')) }}</span>
        </a>
        <button type="button" class="rounded-lg p-2 text-slate-300 hover:bg-white/10 lg:hidden" @click="sidebarOpen = false" aria-label="Close navigation">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5" aria-label="Main navigation">
        <a href="{{ route('dashboard') }}" @class([
            'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
            'bg-primary-600 text-white' => request()->routeIs('dashboard'),
            'text-slate-300 hover:bg-white/10 hover:text-white' => !request()->routeIs('dashboard'),
        ])>
            <x-icon name="home" />
            <span>Dashboard</span>
        </a>

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
                        @foreach($visibleItems as [$label, $routeName, $permission, $icon])
                            <li>
                                <a href="{{ route($routeName) }}"
                                    @class([
                                        'flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition',
                                        'bg-white/10 text-white' => request()->routeIs($routeName),
                                        'text-slate-300 hover:bg-white/10 hover:text-white' => !request()->routeIs($routeName),
                                    ])>
                                    <x-icon :name="$icon" />
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
                <a href="{{ route('student-results.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-slate-300 hover:bg-white/10 hover:text-white">
                    <x-icon name="academic-cap" />
                    Student Results
                </a>
            </div>
        @endif
    </nav>
</aside>