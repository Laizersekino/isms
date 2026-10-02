@extends('layouts.crud')

@section('content')
<h1>{{ $announcement->title }}</h1>

<p>
    <a href="{{ route('announcements.index') }}">Back to announcements</a>
    @if($canEdit)
        <a href="{{ route('announcements.edit', $announcement) }}">Edit</a>
    @endif
</p>

<p>
    <strong>Status:</strong> {{ ucfirst($announcement->status) }}
    <strong>Category:</strong> {{ ucfirst($announcement->category) }}
    @if($announcement->is_pinned)<strong>Pinned</strong>@endif
</p>
<p><strong>Audience:</strong> {{ str($announcement->audience_type)->replace('_', ' ')->title() }}</p>
<p><strong>Created by:</strong> {{ $announcement->createdBy->name }}</p>
<p><strong>Published:</strong> {{ $announcement->published_at?->format('Y-m-d H:i') ?? 'Not published' }}</p>
@if($announcement->expires_at)
    <p><strong>Expires:</strong> {{ $announcement->expires_at->format('Y-m-d H:i') }}</p>
@endif

<div style="white-space: pre-wrap;">{{ $announcement->content }}</div>

@if($isAdministrator)
    <p><strong>Read count:</strong> {{ $announcement->reads_count }}</p>
@endif

@if(auth()->user()->hasPermission('announcements.publish'))
    @if($announcement->status === 'draft')
        <form method="POST" action="{{ route('announcements.publish', $announcement) }}">
            @csrf
            <button type="submit">Publish</button>
        </form>
    @elseif($announcement->status === 'published')
        <form method="POST" action="{{ route('announcements.archive', $announcement) }}">
            @csrf
            <button type="submit">Archive</button>
        </form>
    @endif
@endif

@if(auth()->user()->hasPermission('announcements.delete'))
    <form method="POST" action="{{ route('announcements.destroy', $announcement) }}">
        @csrf
        @method('DELETE')
        <button class="danger" type="submit">Delete</button>
    </form>
@endif
@endsection
