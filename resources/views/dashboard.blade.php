@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-description', 'Your school workspace at a glance')

@section('content')
    @php
        $user = auth()->user();
        $widgets = [
            ['Students', 'Manage student records and admissions.', 'students.view', 'students.index'],
            ['Teachers', 'View teaching staff and assignments.', 'teachers.view', 'teachers.index'],
            ['Attendance', 'Review attendance records.', 'attendance.view', 'attendance.index'],
            ['Academic results', 'Review exams, marks, and published results.', 'marks.view', 'marks.index'],
            ['Academic reports', 'Open academic reporting tools.', 'reports.academic.view', 'student-results.index'],
            ['Fee structures', 'Manage fees assigned to classes and terms.', 'fee_structures.view', 'fee-structures.index'],
            ['Student fees', 'Review student fee accounts.', 'student_fees.view', 'student-fees.index'],
            ['Payments', 'Review recorded payments.', 'payments.view', 'payments.index'],
            ['Finance reports', 'Review collection and outstanding balances.', 'reports.finance.view', 'reports.finance.collection-summary'],
            ['Library', 'Browse books and borrowing activity.', 'books.view', 'books.index'],
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
        @foreach($widgets as [$title, $description, $permission, $routeName])
            @if($user->hasPermission($permission) && \Illuminate\Support\Facades\Route::has($routeName))
                <a href="{{ route($routeName) }}" class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-primary-300 hover:shadow-md">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="font-semibold text-slate-900 group-hover:text-primary-700">{{ $title }}</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $description }}</p>
                        </div>
                        <span class="text-lg text-primary-600" aria-hidden="true">&rarr;</span>
                    </div>
                </a>
            @endif
        @endforeach

        @if($portalCanSeeResults && \Illuminate\Support\Facades\Route::has('student-results.index'))
            <a href="{{ route('student-results.index') }}" class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-primary-300 hover:shadow-md">
                <h2 class="font-semibold text-slate-900 group-hover:text-primary-700">My results</h2>
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

    <section class="mt-8">
        <x-card title="Recent Announcements">
            @if(($recentAnnouncements ?? collect())->isEmpty())
                <p class="text-sm text-slate-500">No current announcements.</p>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach($recentAnnouncements as $announcement)
                        <li class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex flex-wrap items-center gap-2">
                                @if($announcement->is_pinned)
                                    <x-badge variant="warning">Pinned</x-badge>
                                @endif
                                <a href="{{ route('announcements.show', $announcement) }}" class="font-medium text-slate-800 hover:text-primary-700">{{ $announcement->title }}</a>
                                @if($announcement->is_read === 0)
                                    <x-badge variant="info">New</x-badge>
                                @endif
                            </div>
                            <span class="text-sm text-slate-500">{{ ucfirst($announcement->category) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </section>
@endsection
