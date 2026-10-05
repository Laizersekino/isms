@extends('layouts.app')

@section('title', 'Edit Announcement')
@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <a href="{{ route('announcements.show', $announcement) }}" class="inline-flex items-center gap-2 text-sm font-medium text-primary-700 hover:text-primary-800 hover:underline">
            <x-icon name="arrow-right" class="rotate-180" size="sm" />
            <span>Back to announcement</span>
        </a>
        <div class="mt-3 flex items-center gap-3">
            <span class="inline-flex rounded-xl bg-primary-50 p-2.5 text-primary-700">
                <x-icon name="pencil-square" size="lg" />
            </span>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Edit Announcement</h1>
                <p class="mt-0.5 text-sm text-slate-600 truncate max-w-xl">{{ $announcement->title }}</p>
            </div>
        </div>
    </div>

    @include('announcements._form', [
        'action' => route('announcements.update', $announcement),
        'method' => 'PUT',
        'submitLabel' => 'Save changes',
    ])
</div>
@endsection
