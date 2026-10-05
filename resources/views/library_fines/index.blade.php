@extends('layouts.app')

@section('title', 'Library Fines')

@section('content')
    <div class="mb-6">
        <h1 class="flex items-center gap-3 text-2xl font-bold tracking-tight text-slate-950">
            <x-icon name="currency-dollar" size="lg" class="text-primary-600" /> Library Fines
        </h1>
        <p class="mt-1 text-sm text-slate-600">Review fines, payments, and waivers for library loans.</p>
        <a href="{{ route('book-borrowings.index') }}" class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-primary-600 hover:text-primary-700">
            <x-icon name="clipboard-document-check" size="sm" /> View borrowings
        </a>
    </div>

    <x-card>
        <form method="GET" action="{{ route('library-fines.index') }}" class="mb-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-form.select id="status" name="status" label="Status">
                <option value="">All statuses</option>
                @foreach(['unpaid', 'paid', 'waived'] as $option)
                    <option value="{{ $option }}" @selected(($filters['status'] ?? '') === $option)>{{ ucfirst($option) }}</option>
                @endforeach
            </x-form.select>
            <x-form.input id="student_id" name="student_id" label="Student ID" type="number" min="1" :value="$filters['student_id'] ?? ''" />
            <x-form.input id="teacher_id" name="teacher_id" label="Teacher ID" type="number" min="1" :value="$filters['teacher_id'] ?? ''" />
            <x-form.input id="date_from" name="date_from" label="Issued from" type="date" :value="$filters['date_from'] ?? ''" />
            <x-form.input id="date_to" name="date_to" label="Issued to" type="date" :value="$filters['date_to'] ?? ''" />
            <div class="flex flex-wrap items-end gap-2">
                <x-button type="submit" icon="currency-dollar">Filter</x-button>
                @if(count(array_filter($filters, fn ($value) => $value !== null && $value !== '' && $value !== false)) > 0)
                    <a href="{{ route('library-fines.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Clear</a>
                @endif
            </div>
        </form>

        @if($fines->isEmpty())
            <div class="mb-4 flex items-center gap-3 rounded-lg bg-primary-50 px-4 py-3 text-primary-700">
                <x-icon name="currency-dollar" />
                <div>
                    <p class="font-semibold">No library fines found</p>
                    <p class="text-sm">Try changing the filters. New fines will appear here when recorded.</p>
                </div>
            </div>
        @endif

        <x-table.index>
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                <tr>
                    <th scope="col" class="px-4 py-3">Borrower</th>
                    <th scope="col" class="px-4 py-3">Book</th>
                    <th scope="col" class="px-4 py-3">Issued</th>
                    <th scope="col" class="px-4 py-3">Reason</th>
                    <th scope="col" class="px-4 py-3">Amount</th>
                    <th scope="col" class="px-4 py-3">Status</th>
                    <th scope="col" class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($fines as $fine)
                    @php($borrower = $fine->borrowing->student ?? $fine->borrowing->teacher)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-2 text-slate-700">
                                <x-icon name="user-circle" size="sm" class="text-slate-400" />
                                @if($borrower)
                                    {{ $borrower->first_name }} {{ $borrower->last_name }} ({{ $fine->borrowing->student ? 'Student' : 'Teacher' }})
                                @else
                                    Unknown borrower
                                @endif
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('books.show', $fine->borrowing->bookCopy->book) }}" class="inline-flex items-center gap-2 font-medium text-primary-700 hover:text-primary-800">
                                <x-icon name="book-open" size="sm" /> {{ $fine->borrowing->bookCopy->book->title }}
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $fine->issued_date->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ ucfirst($fine->reason) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-900">{{ config('library.currency', 'TZS') }} {{ number_format((float) $fine->amount, 2) }}</td>
                        <td class="px-4 py-3">
                            <x-badge :variant="match ($fine->status) { 'unpaid' => 'warning', 'paid' => 'success', default => 'default' }">{{ ucfirst($fine->status) }}</x-badge>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('library-fines.show', $fine) }}" class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:text-primary-700">
                                <x-icon name="book-open" size="sm" /> Details
                            </a>
                        </td>
                    </tr>
                @empty
                    <x-table.empty :colspan="7" message="No library fines found." />
                @endforelse
            </tbody>
        </x-table.index>

        <x-table.pagination :paginator="$fines" />
    </x-card>
@endsection
