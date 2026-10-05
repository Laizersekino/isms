@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-description', 'Your school workspace at a glance')

@section('content')
    @php
        $user = auth()->user();
        $widgets = [
            ['Students', 'Manage student records and admissions.', 'students.view', 'students.index', 'users'],
            ['Teachers', 'View teaching staff and assignments.', 'teachers.view', 'teachers.index', 'academic-cap'],
            ['Attendance', 'Review attendance records.', 'attendance.view', 'attendance.index', 'clipboard-document-check'],
            ['Academic results', 'Review exams, marks, and published results.', 'marks.view', 'marks.index', 'chart-bar'],
            ['Academic reports', 'Open academic reporting tools.', 'reports.academic.view', 'student-results.index', 'academic-cap'],
            ['Fee structures', 'Manage fees assigned to classes and terms.', 'fee_structures.view', 'fee-structures.index', 'currency-dollar'],
            ['Student fees', 'Review student fee accounts.', 'student_fees.view', 'student-fees.index', 'users'],
            ['Payments', 'Review recorded payments.', 'payments.view', 'payments.index', 'currency-dollar'],
            ['Finance reports', 'Review collection and outstanding balances.', 'reports.finance.view', 'reports.finance.collection-summary', 'chart-bar'],
            ['Library', 'Browse books and borrowing activity.', 'books.view', 'books.index', 'book-open'],
        ];
        $portalCanSeeResults = $user->hasRole('Student')
            || $user->hasRole('Parent')
            || $user->hasPermission('student.portal')
            || $user->hasPermission('parent.portal');
    @endphp

    <section class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wider text-primary-700">Dashboard</p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-950">Welcome, {{ $user->name }}</h1>
            <p class="mt-2 text-slate-600">Your school management workspace is ready.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach($user->roles as $role)
                <x-badge variant="info">{{ $role->name }}</x-badge>
            @endforeach
            @if($user->roles->isEmpty())
                <x-badge>No role assigned</x-badge>
            @endif
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach($widgets as [$title, $description, $permission, $routeName, $icon])
            @if($user->hasPermission($permission) && \Illuminate\Support\Facades\Route::has($routeName))
                <a href="{{ route($routeName) }}" class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-primary-300 hover:shadow-md">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="flex items-center gap-2 font-semibold text-slate-900 group-hover:text-primary-700">
                                <x-icon :name="$icon" class="text-primary-600" />
                                {{ $title }}
                            </h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $description }}</p>
                        </div>
                        <x-icon name="arrow-right" class="text-primary-600" />
                    </div>
                </a>
            @endif
        @endforeach

        @if($portalCanSeeResults && \Illuminate\Support\Facades\Route::has('student-results.index'))
            <a href="{{ route('student-results.index') }}" class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-primary-300 hover:shadow-md">
                <h2 class="flex items-center gap-2 font-semibold text-slate-900 group-hover:text-primary-700">
                    <x-icon name="academic-cap" class="text-primary-600" />
                    My results
                </h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">View published results available to your account.</p>
            </a>
        @endif

        @if($user->hasPermission('announcements.view') && \Illuminate\Support\Facades\Route::has('announcements.index'))
            <a href="{{ route('announcements.index') }}" class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-primary-300 hover:shadow-md">
                <h2 class="font-semibold text-slate-900 group-hover:text-primary-700">Announcements</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Read updates shared with your account.</p>
            </a>
        @endif
    </section>

    <section class="mt-8" x-data="{ speed: 30, isPaused: false }">
        <x-card>
            <div class="mb-4 flex flex-col justify-between gap-3 border-b border-slate-100 pb-3 sm:flex-row sm:items-center">
                <div class="flex items-center gap-3">
                    <span class="inline-flex rounded-lg bg-primary-50 p-2 text-primary-700">
                        <x-icon name="megaphone" size="md" />
                    </span>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Recent Announcements</h2>
                        <p class="text-xs text-slate-500">Live school notices and updates &bull; Hover to pause</p>
                    </div>
                </div>

                @if(!($recentAnnouncements ?? collect())->isEmpty())
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Speed controls -->
                        <div class="flex items-center gap-1 text-xs text-slate-600">
                            <span class="hidden text-slate-400 sm:inline">Speed:</span>
                            <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-0.5">
                                <button
                                    type="button"
                                    @click="speed = 45"
                                    :class="speed === 45 ? 'bg-white font-semibold text-primary-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                                    class="rounded px-2 py-0.5 text-xs transition"
                                    title="Slow (45s per cycle)"
                                >
                                    Slow
                                </button>
                                <button
                                    type="button"
                                    @click="speed = 30"
                                    :class="speed === 30 ? 'bg-white font-semibold text-primary-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                                    class="rounded px-2 py-0.5 text-xs transition"
                                    title="Normal (30s per cycle)"
                                >
                                    Normal
                                </button>
                                <button
                                    type="button"
                                    @click="speed = 15"
                                    :class="speed === 15 ? 'bg-white font-semibold text-primary-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                                    class="rounded px-2 py-0.5 text-xs transition"
                                    title="Fast (15s per cycle)"
                                >
                                    Fast
                                </button>
                            </div>
                        </div>

                        <!-- Pause / Resume button -->
                        <button
                            type="button"
                            @click="isPaused = !isPaused"
                            :class="isPaused ? 'bg-warning-50 text-warning-700 border-warning-300' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                            class="inline-flex items-center gap-1 rounded-lg border px-2 py-1 text-xs font-medium transition"
                            :title="isPaused ? 'Resume auto-scroll' : 'Pause auto-scroll'"
                        >
                            <template x-if="!isPaused">
                                <span class="inline-flex items-center gap-1">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/></svg>
                                    <span>Pause</span>
                                </span>
                            </template>
                            <template x-if="isPaused">
                                <span class="inline-flex items-center gap-1">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="currentColor"><polygon points="6 4 20 12 6 20 6 4"/></svg>
                                    <span>Resume</span>
                                </span>
                            </template>
                        </button>

                        @if(\Illuminate\Support\Facades\Route::has('announcements.index'))
                            <a href="{{ route('announcements.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-primary-700 hover:text-primary-800 hover:underline">
                                <span>View all</span>
                                <x-icon name="arrow-right" size="sm" />
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            @if(($recentAnnouncements ?? collect())->isEmpty())
                <p class="py-6 text-center text-sm text-slate-500">No current announcements.</p>
            @else
                <div class="announcement-scroll-container relative h-96 overflow-hidden rounded-xl border border-slate-100 bg-slate-50/60 p-4">
                    <!-- Top & bottom fade overlays -->
                    <div class="pointer-events-none absolute inset-x-0 top-0 z-10 h-8 bg-gradient-to-b from-slate-50 to-transparent"></div>
                    <div class="pointer-events-none absolute inset-x-0 bottom-0 z-10 h-8 bg-gradient-to-t from-slate-50 to-transparent"></div>

                    <div
                        class="announcement-scroll"
                        :style="{ '--scroll-duration': speed + 's', 'animation-play-state': isPaused ? 'paused' : null }"
                    >
                        <!-- First Set -->
                        <div class="space-y-3.5">
                            @foreach($recentAnnouncements as $announcement)
                                @php
                                    $categoryVariant = match ($announcement->category) {
                                        'academic' => 'info',
                                        'event' => 'primary',
                                        'urgent' => 'danger',
                                        'holiday' => 'success',
                                        default => 'gray',
                                    };
                                @endphp
                                <article class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-primary-300 hover:shadow-md">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <x-badge :variant="$categoryVariant">
                                                {{ ucfirst($announcement->category) }}
                                            </x-badge>
                                            @if($announcement->is_pinned)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-warning-50 px-2 py-0.5 text-xs font-semibold text-warning-700 ring-1 ring-inset ring-warning-600/20">
                                                    <x-icon name="bell" size="sm" />
                                                    <span>Pinned</span>
                                                </span>
                                            @endif
                                            @if(($announcement->is_read ?? 1) === 0)
                                                <span class="inline-flex items-center rounded-full bg-primary-50 px-2 py-0.5 text-xs font-semibold text-primary-700 ring-1 ring-inset ring-primary-600/20">
                                                    New
                                                </span>
                                            @endif
                                        </div>
                                        <span class="inline-flex items-center gap-1 text-xs text-slate-500">
                                            <x-icon name="calendar" size="sm" class="text-slate-400" />
                                            {{ $announcement->published_at?->format('M j, Y') ?? 'Recent' }}
                                        </span>
                                    </div>

                                    <h3 class="mt-2 text-base font-bold text-slate-900 group-hover:text-primary-700 transition">
                                        <a href="{{ route('announcements.show', $announcement) }}">
                                            {{ $announcement->title }}
                                        </a>
                                    </h3>

                                    <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                                        {{ \Illuminate\Support\Str::limit($announcement->content ?? '', 200) }}
                                    </p>

                                    <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-2">
                                        <a href="{{ route('announcements.show', $announcement) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-primary-700 hover:text-primary-800 hover:underline">
                                            <span>Read more</span>
                                            <x-icon name="arrow-right" size="sm" />
                                        </a>
                                        <span class="text-xs text-slate-400">{{ ucfirst($announcement->category) }} notice</span>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <!-- Duplicate Set for Seamless Loop -->
                        <div class="mt-3.5 space-y-3.5" aria-hidden="true">
                            @foreach($recentAnnouncements as $announcement)
                                @php
                                    $categoryVariant = match ($announcement->category) {
                                        'academic' => 'info',
                                        'event' => 'primary',
                                        'urgent' => 'danger',
                                        'holiday' => 'success',
                                        default => 'gray',
                                    };
                                @endphp
                                <article class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-primary-300 hover:shadow-md">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <x-badge :variant="$categoryVariant">
                                                {{ ucfirst($announcement->category) }}
                                            </x-badge>
                                            @if($announcement->is_pinned)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-warning-50 px-2 py-0.5 text-xs font-semibold text-warning-700 ring-1 ring-inset ring-warning-600/20">
                                                    <x-icon name="bell" size="sm" />
                                                    <span>Pinned</span>
                                                </span>
                                            @endif
                                            @if(($announcement->is_read ?? 1) === 0)
                                                <span class="inline-flex items-center rounded-full bg-primary-50 px-2 py-0.5 text-xs font-semibold text-primary-700 ring-1 ring-inset ring-primary-600/20">
                                                    New
                                                </span>
                                            @endif
                                        </div>
                                        <span class="inline-flex items-center gap-1 text-xs text-slate-500">
                                            <x-icon name="calendar" size="sm" class="text-slate-400" />
                                            {{ $announcement->published_at?->format('M j, Y') ?? 'Recent' }}
                                        </span>
                                    </div>

                                    <h3 class="mt-2 text-base font-bold text-slate-900 group-hover:text-primary-700 transition">
                                        <a href="{{ route('announcements.show', $announcement) }}" tabindex="-1">
                                            {{ $announcement->title }}
                                        </a>
                                    </h3>

                                    <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                                        {{ \Illuminate\Support\Str::limit($announcement->content ?? '', 200) }}
                                    </p>

                                    <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-2">
                                        <a href="{{ route('announcements.show', $announcement) }}" tabindex="-1" class="inline-flex items-center gap-1 text-xs font-semibold text-primary-700 hover:text-primary-800 hover:underline">
                                            <span>Read more</span>
                                            <x-icon name="arrow-right" size="sm" />
                                        </a>
                                        <span class="text-xs text-slate-400">{{ ucfirst($announcement->category) }} notice</span>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </x-card>
    </section>
@endsection
