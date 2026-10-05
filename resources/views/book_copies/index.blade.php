@extends('layouts.app')

@section('title', 'Book Copies')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1 class="flex items-center gap-3 text-2xl font-bold tracking-tight text-slate-950">
                <x-icon name="books" size="lg" class="text-primary-600" /> Book Copies
            </h1>
            <p class="mt-1 text-sm text-slate-600">Manage individual library inventory items.</p>
        </div>
        @if(auth()->user()->hasPermission('book_copies.create'))
            <a href="{{ route('book-copies.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">
                <x-icon name="plus" size="sm" /> Add Copy
            </a>
        @endif
    </div>

    <x-card>
        <form method="GET" action="{{ route('book-copies.index') }}" class="mb-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,2fr)_minmax(12rem,1fr)_auto] lg:items-end">
            <x-form.input id="search" name="search" label="Search copies" type="search" :value="$search" placeholder="Barcode, copy number, or book title" />
            <x-form.select id="status" name="status" label="Status">
                <option value="">All statuses</option>
                @foreach(['available', 'borrowed', 'lost', 'damaged', 'retired'] as $option)
                    <option value="{{ $option }}" @selected($status === $option)>{{ ucfirst($option) }}</option>
                @endforeach
            </x-form.select>
            <div class="flex flex-wrap gap-2">
                <x-button type="submit" icon="book-open">Filter</x-button>
                @if($search !== '' || $status !== '')
                    <a href="{{ route('book-copies.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Clear</a>
                @endif
            </div>
        </form>

        @if($bookCopies->isEmpty())
            <div class="mb-4 flex items-center gap-3 rounded-lg bg-primary-50 px-4 py-3 text-primary-700">
                <x-icon name="books" />
                <div>
                    <p class="font-semibold">No book copies found</p>
                    <p class="text-sm">Try adjusting your search or status filter.</p>
                </div>
            </div>
        @endif

        <x-table.index>
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                <tr>
                    <th scope="col" class="px-4 py-3">Book</th>
                    <th scope="col" class="px-4 py-3">Copy number</th>
                    <th scope="col" class="px-4 py-3">Barcode</th>
                    <th scope="col" class="px-4 py-3">Condition</th>
                    <th scope="col" class="px-4 py-3">Status</th>
                    <th scope="col" class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($bookCopies as $bookCopy)
                    @php
                        $statusVariant = match ($bookCopy->status) {
                            'available' => 'success',
                            'borrowed', 'damaged' => 'warning',
                            'lost' => 'danger',
                            default => 'default',
                        };
                    @endphp
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('books.show', $bookCopy->book) }}" class="inline-flex items-center gap-2 font-medium text-primary-700 hover:text-primary-800">
                                <x-icon name="book-open" size="sm" /> {{ $bookCopy->book->title }}
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('book-copies.show', $bookCopy) }}" class="font-medium text-primary-600 hover:text-primary-700">{{ $bookCopy->copy_number }}</a>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $bookCopy->barcode ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ ucfirst($bookCopy->condition) }}</td>
                        <td class="px-4 py-3"><x-badge :variant="$statusVariant">{{ ucfirst($bookCopy->status) }}</x-badge></td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('book-copies.show', $bookCopy) }}" class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:text-primary-700">
                                    <x-icon name="book-open" size="sm" /> View
                                </a>
                                @if(auth()->user()->hasPermission('book_copies.update'))
                                    <a href="{{ route('book-copies.edit', $bookCopy) }}" class="inline-flex items-center gap-1 text-sm font-medium text-warning-600 hover:text-warning-700">
                                        <x-icon name="pencil-square" size="sm" /> Edit
                                    </a>
                                @endif
                                @if(auth()->user()->hasPermission('book_copies.delete'))
                                    <form action="{{ route('book-copies.destroy', $bookCopy) }}" method="POST" onsubmit="return confirm('Delete this book copy?')">
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
                    <x-table.empty :colspan="6" message="No book copies found." />
                @endforelse
            </tbody>
        </x-table.index>

        <x-table.pagination :paginator="$bookCopies" />
    </x-card>
@endsection
