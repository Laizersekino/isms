@extends('layouts.app')

@section('title', $announcement->title)
@section('content')
@php
    $categoryVariant = match ($announcement->category) {
        'academic' => 'info',
        'event' => 'primary',
        'urgent' => 'danger',
        'holiday' => 'success',
        default => 'gray',
    };
    $statusVariant = match ($announcement->status) {
        'draft' => 'warning',
        'published' => 'success',
        'archived' => 'gray',
        default => 'gray',
    };
@endphp

<div class="mx-auto max-w-5xl space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
        <div class="space-y-3">
            <a href="{{ route('announcements.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-primary-700 hover:text-primary-800 hover:underline">
                <x-icon name="arrow-right" class="rotate-180" size="sm" />
                <span>Back to announcements</span>
            </a>
            <div class="flex items-start gap-3">
                <span class="mt-1 inline-flex rounded-xl bg-primary-50 p-2.5 text-primary-700">
                    <x-icon name="megaphone" size="lg" />
                </span>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $announcement->title }}</h1>
                    @if($announcement->is_pinned)
                        <span class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-warning-50 px-2.5 py-0.5 text-xs font-medium text-warning-700 ring-1 ring-inset ring-warning-600/20">
                            <x-icon name="bell" size="sm" />
                            <span>Pinned announcement</span>
                        </span>
                    @endif
                </div>
            </div>
        </div>
        @if($canEdit)
            <div>
                <a href="{{ route('announcements.edit', $announcement) }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-slate-100">
                    <x-icon name="pencil-square" size="sm" />
                    <span>Edit</span>
                </a>
            </div>
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-2">
            <div class="mb-4 flex flex-wrap items-center gap-2 border-b border-slate-100 pb-4">
                <x-badge :variant="$categoryVariant">
                    {{ ucfirst($announcement->category) }}
                </x-badge>
                <x-badge :variant="$statusVariant">
                    {{ ucfirst($announcement->status) }}
                </x-badge>
                <span class="text-xs text-slate-400">&bull;</span>
                <span class="flex items-center gap-1 text-xs text-slate-500">
                    <x-icon name="calendar" size="sm" class="text-slate-400" />
                    {{ $announcement->created_at->format('M j, Y') }}
                </span>
            </div>
            <div class="whitespace-pre-wrap break-words text-sm leading-7 text-slate-700">
                {{ $announcement->content }}
            </div>
        </x-card>

        <x-card title="Announcement Details">
            <dl class="space-y-4">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</dt>
                    <dd class="mt-1">
                        <x-badge :variant="$statusVariant">
                            {{ ucfirst($announcement->status) }}
                        </x-badge>
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Category</dt>
                    <dd class="mt-1">
                        <x-badge :variant="$categoryVariant">
                            {{ ucfirst($announcement->category) }}
                        </x-badge>
                    </dd>
                </div>

                <div>
                    <dt class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <x-icon name="user-group" size="sm" class="text-slate-400" />
                        <span>Audience</span>
                    </dt>
                    <dd class="mt-1 text-sm font-medium text-slate-800">
                        {{ str($announcement->audience_type)->replace('_', ' ')->title() }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Created by</dt>
                    <dd class="mt-1 text-sm text-slate-800">
                        {{ $announcement->createdBy->name }}
                    </dd>
                </div>

                <div>
                    <dt class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <x-icon name="calendar" size="sm" class="text-slate-400" />
                        <span>Published</span>
                    </dt>
                    <dd class="mt-1 text-sm text-slate-800">
                        {{ $announcement->published_at?->format('Y-m-d H:i') ?? 'Not published' }}
                    </dd>
                </div>

                @if($announcement->expires_at)
                    <div>
                        <dt class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <x-icon name="calendar" size="sm" class="text-slate-400" />
                            <span>Expires</span>
                        </dt>
                        <dd class="mt-1 text-sm text-slate-800">
                            {{ $announcement->expires_at->format('Y-m-d H:i') }}
                        </dd>
                    </div>
                @endif

                @if($isAdministrator)
                    <div>
                        <dt class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <x-icon name="eye" size="sm" class="text-slate-400" />
                            <span>Engagement</span>
                        </dt>
                        <dd class="mt-1">
                            <p class="flex items-center gap-2 text-sm font-medium text-slate-800">
                                <x-icon name="eye" size="sm" class="text-slate-500" />
                                <strong>Read count:</strong> {{ $announcement->reads_count }}
                            </p>
                        </dd>
                    </div>
                @endif
            </dl>
        </x-card>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        @if(auth()->user()->hasPermission('announcements.publish'))
            @if($announcement->status === 'draft')
                <form method="POST" action="{{ route('announcements.publish', $announcement) }}">
                    @csrf
                    <x-button type="submit" icon="megaphone">Publish announcement</x-button>
                </form>
            @elseif($announcement->status === 'published')
                <form method="POST" action="{{ route('announcements.archive', $announcement) }}">
                    @csrf
                    <x-button type="submit" variant="secondary" icon="inbox">Archive announcement</x-button>
                </form>
            @endif
        @endif

        @if(auth()->user()->hasPermission('announcements.delete'))
            <form method="POST" action="{{ route('announcements.destroy', $announcement) }}" onsubmit="return confirm('Delete this announcement?')">
                @csrf
                @method('DELETE')
                <x-button variant="danger" type="submit" icon="trash">Delete</x-button>
            </form>
        @endif
    </div>
</div>
@endsection
