@extends('layouts.app')

@section('title', 'Announcements')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <div class="flex items-center gap-3">
                <span class="inline-flex rounded-xl bg-primary-50 p-2.5 text-primary-700">
                    <x-icon name="megaphone" size="lg" />
                </span>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Announcements</h1>
                    <p class="mt-1 text-sm text-slate-600">View updates, notices, and important alerts for your school community.</p>
                </div>
            </div>
        </div>
        @if(auth()->user()->hasPermission('announcements.create'))
            <div>
                <a href="{{ route('announcements.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-primary-100">
                    <x-icon name="plus" size="sm" />
                    <span>Create Announcement</span>
                </a>
            </div>
        @endif
    </div>

    <x-card>
        <form method="GET" action="{{ route('announcements.index') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            <x-form.select name="status" label="Status">
                <option value="">All visible statuses</option>
                @foreach(['draft', 'published', 'archived'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </x-form.select>

            <x-form.select name="category" label="Category">
                <option value="">All categories</option>
                @foreach(['general', 'academic', 'event', 'urgent', 'holiday'] as $category)
                    <option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ ucfirst($category) }}</option>
                @endforeach
            </x-form.select>

            @if($isAdministrator)
                <x-form.select name="audience_type" label="Audience">
                    <option value="">All audiences</option>
                    @foreach(['all', 'role', 'class', 'specific_users'] as $type)
                        <option value="{{ $type }}" @selected(($filters['audience_type'] ?? '') === $type)>{{ str($type)->replace('_', ' ')->title() }}</option>
                    @endforeach
                </x-form.select>
            @endif

            <x-form.input name="date_from" label="From" type="date" :value="$filters['date_from'] ?? ''" />

            <div class="flex items-end gap-2">
                <div class="min-w-0 flex-1">
                    <x-form.input name="date_to" label="To" type="date" :value="$filters['date_to'] ?? ''" />
                </div>
                <x-button type="submit" icon="bell">Filter</x-button>
            </div>

            @if(count(array_filter($filters, fn ($value) => $value !== null && $value !== '')) > 0)
                <div class="sm:col-span-2 lg:col-span-3 xl:col-span-5">
                    <a href="{{ route('announcements.index') }}" class="inline-flex items-center text-sm font-medium text-primary-700 hover:text-primary-800 hover:underline">
                        Clear filters
                    </a>
                </div>
            @endif
        </form>
    </x-card>

    <x-card>
        <x-table.index>
            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                <tr>
                    <th scope="col" class="px-4 py-3">Announcement</th>
                    <th scope="col" class="px-4 py-3">Category</th>
                    <th scope="col" class="px-4 py-3">Status</th>
                    <th scope="col" class="px-4 py-3">Audience</th>
                    <th scope="col" class="px-4 py-3">Published</th>
                    @if($isAdministrator)
                        <th scope="col" class="px-4 py-3 text-center">Reads</th>
                    @endif
                    <th scope="col" class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($announcements as $announcement)
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
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <div class="flex items-start gap-2.5">
                                @if($announcement->is_pinned)
                                    <span class="mt-0.5 text-warning-600" title="Pinned announcement">
                                        <x-icon name="bell" size="sm" />
                                    </span>
                                @endif
                                <a href="{{ route('announcements.show', $announcement) }}" class="font-medium text-slate-900 hover:text-primary-700 hover:underline">
                                    {{ $announcement->title }}
                                </a>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <x-badge :variant="$categoryVariant">
                                {{ ucfirst($announcement->category) }}
                            </x-badge>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <x-badge :variant="$statusVariant">
                                {{ ucfirst($announcement->status) }}
                            </x-badge>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-600">
                            <div class="flex items-center gap-1.5 text-sm">
                                <x-icon name="user-group" size="sm" class="text-slate-400" />
                                <span>{{ str($announcement->audience_type)->replace('_', ' ')->title() }}</span>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">
                            <div class="flex items-center gap-1.5">
                                <x-icon name="calendar" size="sm" class="text-slate-400" />
                                <span>{{ $announcement->published_at?->format('Y-m-d H:i') ?? '—' }}</span>
                            </div>
                        </td>
                        @if($isAdministrator)
                            <td class="whitespace-nowrap px-4 py-3 text-center text-sm font-medium text-slate-700">
                                <span class="inline-flex items-center gap-1">
                                    <x-icon name="eye" size="sm" class="text-slate-400" />
                                    {{ $announcement->reads_count }}
                                </span>
                            </td>
                        @endif
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <a href="{{ route('announcements.show', $announcement) }}" class="inline-flex items-center gap-1 text-sm font-medium text-primary-700 hover:text-primary-800 hover:underline" aria-label="View {{ $announcement->title }}">
                                <x-icon name="eye" size="sm" />
                                <span>View</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <x-table.empty icon="megaphone" message="No announcements found." :colspan="$isAdministrator ? 7 : 6" />
                @endforelse
            </tbody>
        </x-table.index>
        <x-table.pagination :paginator="$announcements" />
    </x-card>
</div>
@endsection

