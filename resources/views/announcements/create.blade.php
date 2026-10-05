@extends('layouts.app')

@section('title', 'Create Announcement')
@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <a href="{{ route('announcements.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-primary-700 hover:text-primary-800 hover:underline">
            <x-icon name="arrow-right" class="rotate-180" size="sm" />
            <span>Back to announcements</span>
        </a>
        <div class="mt-3 flex items-center gap-3">
            <span class="inline-flex rounded-xl bg-primary-50 p-2.5 text-primary-700">
                <x-icon name="plus" size="lg" />
            </span>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Create Announcement</h1>
                <p class="mt-0.5 text-sm text-slate-600">Draft a notice for the appropriate school audience.</p>
            </div>
        </div>
    </div>

    @include('announcements._form', [
        'action' => route('announcements.store'),
        'method' => 'POST',
        'submitLabel' => 'Save draft',
    ])
</div>
@endsection
