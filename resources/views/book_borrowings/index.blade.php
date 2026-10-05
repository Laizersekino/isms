@extends('layouts.app')

@section('title', 'Book Borrowings')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1 class="flex items-center gap-3 text-2xl font-bold tracking-tight text-slate-950">
                <x-icon name="clipboard-document-check" size="lg" class="text-primary-600" /> Book Borrowings
            </h1>
            <p class="mt-1 text-sm text-slate-600">Track issued books, due dates, and returns.</p>
        </div>
        @if(auth()->user()->hasPermission('borrowings.issue'))
            <a href="{{ route('book-borrowings.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">
                <x-icon name="plus" size="sm" /> Issue Book
            </a>
        @endif
    </div>

    <x-card>
        <form method="GET" action="{{ route('book-borrowings.index') }}" class="mb-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-form.select id="status" name="status" label="Status">
                <option value="">All statuses</option>
                @foreach(['borrowed', 'returned'] as $option)
                    <option value="{{ $option }}" @selected(($filters['status'] ?? '') === $option)>{{ ucfirst($option) }}</option>
                @endforeach
            </x-form.select>
            <x-form.input id="student_id" name="student_id" label="Student ID" type="number" min="1" :value="$filters['student_id'] ?? ''" />
            <x-form.input id="teacher_id" name="teacher_id" label="Teacher ID" type="number" min="1" :value="$filters['teacher_id'] ?? ''" />
            <div class="flex flex-wrap items-end gap-3 sm:col-span-2 lg:col-span-3">
                <x-form.checkbox id="overdue" name="overdue" label="Overdue only" :checked="($filters['overdue'] ?? false) == 1" />
                <x-button type="submit" icon="clipboard-document-check">Filter</x-button>
                @if(count(array_filter($filters, fn ($value) => $value !== null && $value !== '' && $value !== false)) > 0)
                    <a href="{{ route('book-borrowings.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Clear</a>
                @endif
            </div>
        </form>

        @if($borrowings->isEmpty())
            <div class="mb-4 flex items-center gap-3 rounded-lg bg-primary-50 px-4 py-3 text-primary-700">
                <x-icon name="clipboard-document-check" />
                <div>
                    <p class="font-semibold">No book borrowings found</p>
                    <p class="text-sm">Try changing the filters or issue a book to get started.</p>
                </div>
            </div>
        @endif

        <x-table.index>
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                <tr>
                    <th scope="col" class="px-4 py-3">Book</th>
                    <th scope="col" class="px-4 py-3">Copy</th>
                    <th scope="col" class="px-4 py-3">Borrower</th>
                    <th scope="col" class="px-4 py-3">Borrowed</th>
                    <th scope="col" class="px-4 py-3">Due</th>
                    <th scope="col" class="px-4 py-3">Status</th>
                    <th scope="col" class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($borrowings as $borrowing)
                    @php($isOverdue = $borrowing->status === 'borrowed' && $borrowing->due_date->lt(today()))
                    @php($borrower = $borrowing->student ?? $borrowing->teacher)
                    <tr @class(['transition hover:bg-slate-50', 'bg-danger-50/60' => $isOverdue])>
                        <td class="px-4 py-3">
                            <a href="{{ route('books.show', $borrowing->bookCopy->book) }}" class="inline-flex items-center gap-2 font-medium text-primary-700 hover:text-primary-800">
                                <x-icon name="book-open" size="sm" /> {{ $borrowing->bookCopy->book->title }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ $borrowing->bookCopy->copy_number }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-2 text-slate-700">
                                <x-icon name="user-circle" size="sm" class="text-slate-400" />
                                @if($borrower)
                                    {{ $borrower->first_name }} {{ $borrower->last_name }} ({{ $borrowing->student ? 'Student' : 'Teacher' }})
                                @else
                                    Unknown borrower
                                @endif
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $borrowing->borrowed_date->format('Y-m-d') }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $borrowing->due_date->format('Y-m-d') }}</td>
                        <td class="px-4 py-3">
                            <x-badge :variant="$isOverdue ? 'danger' : ($borrowing->status === 'returned' ? 'success' : 'warning')">
                                {{ $isOverdue ? 'Overdue' : ucfirst($borrowing->status) }}
                            </x-badge>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('book-borrowings.show', $borrowing) }}" class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:text-primary-700">
                                <x-icon name="book-open" size="sm" /> Details
                            </a>
                        </td>
                    </tr>
                @empty
                    <x-table.empty :colspan="7" message="No book borrowings found." />
                @endforelse
            </tbody>
        </x-table.index>

        <x-table.pagination :paginator="$borrowings" />
    </x-card>
@endsection
