@extends('layouts.crud')

@section('content')
<h1>Announcements</h1>

<p>
    <a href="{{ route('dashboard') }}">Dashboard</a>
    @if(auth()->user()->hasPermission('announcements.create'))
        <a href="{{ route('announcements.create') }}" class="primary">Create announcement</a>
    @endif
</p>

<form method="GET" action="{{ route('announcements.index') }}">
    <label for="status">Status</label>
    <select id="status" name="status">
        <option value="">All visible statuses</option>
        @foreach(['draft', 'published', 'archived'] as $status)
            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>

    <label for="category">Category</label>
    <select id="category" name="category">
        <option value="">All categories</option>
        @foreach(['general', 'academic', 'event', 'urgent', 'holiday'] as $category)
            <option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ ucfirst($category) }}</option>
        @endforeach
    </select>

    @if($isAdministrator)
        <label for="audience_type">Audience</label>
        <select id="audience_type" name="audience_type">
            <option value="">All audiences</option>
            @foreach(['all', 'role', 'class', 'specific_users'] as $type)
                <option value="{{ $type }}" @selected(($filters['audience_type'] ?? '') === $type)>
                    {{ str($type)->replace('_', ' ')->title() }}
                </option>
            @endforeach
        </select>
    @endif

    <label for="date_from">From</label>
    <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}">
    <label for="date_to">To</label>
    <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}">

    <button type="submit">Filter</button>
    <a href="{{ route('announcements.index') }}">Clear</a>
</form>

@if($announcements->isEmpty())
    <p>No announcements found.</p>
@else
    <table>
        <thead>
            <tr>
                <th>Title</th><th>Category</th><th>Status</th><th>Audience</th><th>Published</th>
                @if($isAdministrator)<th>Reads</th>@endif
            </tr>
        </thead>
        <tbody>
            @foreach($announcements as $announcement)
                <tr>
                    <td>
                        @if($announcement->is_pinned)<strong>Pinned:</strong>@endif
                        <a href="{{ route('announcements.show', $announcement) }}">{{ $announcement->title }}</a>
                    </td>
                    <td>{{ ucfirst($announcement->category) }}</td>
                    <td>{{ ucfirst($announcement->status) }}</td>
                    <td>{{ str($announcement->audience_type)->replace('_', ' ')->title() }}</td>
                    <td>{{ $announcement->published_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    @if($isAdministrator)<td>{{ $announcement->reads_count }}</td>@endif
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $announcements->links() }}
@endif
@endsection
