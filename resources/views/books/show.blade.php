@extends('layouts.app')

@section('title', $book->title)

@section('content')
    @php
        $statusVariant = match ($book->status) {
            'active' => 'success',
            'inactive' => 'warning',
            default => 'default',
        };
    @endphp

    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
        <div>
            <a href="{{ route('books.index') }}" class="mb-3 inline-flex items-center gap-2 text-sm font-medium text-primary-600 hover:text-primary-700">
                <x-icon name="book-open" size="sm" /> Back to books
            </a>
            <h1 class="flex items-center gap-3 text-2xl font-bold tracking-tight text-slate-950">
                <x-icon name="book-open" class="text-primary-600" /> {{ $book->title }}
            </h1>
            <p class="mt-1 text-sm text-slate-600">Book record and catalogue details</p>
        </div>
        @if(auth()->user()->hasPermission('books.update'))
            <a href="{{ route('books.edit', $book) }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-warning-200 bg-warning-50 px-4 py-2.5 text-sm font-semibold text-warning-700 hover:bg-warning-100">
                <x-icon name="pencil-square" size="sm" /> Edit Book
            </a>
        @endif
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <x-card title="Book information" class="lg:col-span-2">
            <dl class="grid gap-x-6 gap-y-5 sm:grid-cols-2">
                <div><dt class="text-sm text-slate-500">Author</dt><dd class="mt-1 font-medium text-slate-900">{{ $book->author }}</dd></div>
                <div><dt class="text-sm text-slate-500">ISBN</dt><dd class="mt-1 font-medium text-slate-900">{{ $book->isbn ?? '—' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Publisher</dt><dd class="mt-1 font-medium text-slate-900">{{ $book->publisher ?? '—' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Category</dt><dd class="mt-1 font-medium text-slate-900">{{ $book->category ?? '—' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Edition</dt><dd class="mt-1 font-medium text-slate-900">{{ $book->edition ?? '—' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Publication year</dt><dd class="mt-1 font-medium text-slate-900">{{ $book->publication_year ?? '—' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-sm text-slate-500">Description</dt><dd class="mt-1 whitespace-pre-line text-slate-900">{{ $book->description ?: '—' }}</dd></div>
            </dl>
        </x-card>

        <x-card title="Catalogue status">
            <div class="flex flex-col gap-5">
                <div>
                    <p class="mb-2 text-sm text-slate-500">Status</p>
                    <x-badge :variant="$statusVariant">{{ ucfirst($book->status) }}</x-badge>
                </div>
                <div class="rounded-lg bg-primary-50 p-4">
                    <div class="flex items-center gap-2 text-primary-700">
                        <x-icon name="books" />
                        <p class="text-sm font-medium">Copies: {{ $book->copies_count }}</p>
                    </div>
                </div>
            </div>
        </x-card>
    </div>
@endsection
