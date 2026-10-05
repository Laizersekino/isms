@extends('layouts.app')

@section('title', 'Books')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <div class="flex items-center gap-3">
                <x-icon name="book-open" size="lg" class="text-primary-600" />
                <h1 class="text-2xl font-bold tracking-tight text-slate-950">Library Books</h1>
            </div>
            <p class="mt-1 text-sm text-slate-600">Browse and manage the school library catalogue.</p>
        </div>
        @if(auth()->user()->hasPermission('books.create'))
            <a href="{{ route('books.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">
                <x-icon name="plus" size="sm" />
                Add Book
            </a>
        @endif
    </div>

    <x-card>
        <form method="GET" action="{{ route('books.index') }}" class="mb-5 flex flex-col gap-3 sm:flex-row">
            <x-form.input id="search" name="search" label="Search books" type="search" :value="$search" placeholder="Title, ISBN, author, publisher, or category" class="min-w-0 flex-1" />
            <div class="flex items-end gap-2">
                <x-button type="submit" icon="book-open">Search</x-button>
                @if($search !== '')
                    <a href="{{ route('books.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Clear</a>
                @endif
            </div>
        </form>

        @if($books->isEmpty())
            <div class="mb-4 flex items-center gap-3 rounded-lg bg-primary-50 px-4 py-3 text-primary-700">
                <x-icon name="book-open" />
                <div>
                    <p class="font-semibold">No books found</p>
                    <p class="text-sm">Try another search or add a book to the catalogue.</p>
                </div>
            </div>
        @endif

        <x-table.index>
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                <tr>
                    <th scope="col" class="px-4 py-3">Title</th>
                    <th scope="col" class="px-4 py-3">ISBN</th>
                    <th scope="col" class="px-4 py-3">Author</th>
                    <th scope="col" class="px-4 py-3">Category</th>
                    <th scope="col" class="px-4 py-3">Copies</th>
                    <th scope="col" class="px-4 py-3">Status</th>
                    <th scope="col" class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($books as $book)
                    @php
                        $statusVariant = match ($book->status) {
                            'active' => 'success',
                            'inactive' => 'warning',
                            default => 'default',
                        };
                    @endphp
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('books.show', $book) }}" class="flex items-center gap-2 font-medium text-primary-700 hover:text-primary-800">
                                <x-icon name="book-open" size="sm" />
                                {{ $book->title }}
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $book->isbn ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $book->author }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $book->category ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $book->copies_count }}</td>
                        <td class="px-4 py-3"><x-badge :variant="$statusVariant">{{ ucfirst($book->status) }}</x-badge></td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('books.show', $book) }}" class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:text-primary-700">
                                    <x-icon name="book-open" size="sm" /> View
                                </a>
                                @if(auth()->user()->hasPermission('books.update'))
                                    <a href="{{ route('books.edit', $book) }}" class="inline-flex items-center gap-1 text-sm font-medium text-warning-600 hover:text-warning-700">
                                        <x-icon name="pencil-square" size="sm" /> Edit
                                    </a>
                                @endif
                                @if(auth()->user()->hasPermission('books.delete'))
                                    <form action="{{ route('books.destroy', $book) }}" method="POST" onsubmit="return confirm('Delete this book?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1 text-sm font-medium text-danger-600 hover:text-danger-700">
                                            <x-icon name="trash" size="sm" /> Delete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-table.empty :colspan="7" message="No books found." />
                @endforelse
            </tbody>
        </x-table.index>

        <x-table.pagination :paginator="$books" />
    </x-card>
@endsection
