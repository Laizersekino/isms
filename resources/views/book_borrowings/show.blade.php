@extends('layouts.app')

@section('title', 'Borrowing #'.$bookBorrowing->id)

@section('content')
    @php
        $isOverdue = $bookBorrowing->status === 'borrowed'
            && $bookBorrowing->returned_date === null
            && $bookBorrowing->due_date->lt(today());
        $statusVariant = $isOverdue ? 'danger' : ($bookBorrowing->status === 'returned' ? 'success' : 'warning');
        $borrower = $bookBorrowing->student ?? $bookBorrowing->teacher;
    @endphp

    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
        <div>
            <a href="{{ route('book-borrowings.index') }}" class="mb-3 inline-flex items-center gap-2 text-sm font-medium text-primary-600 hover:text-primary-700">
                <x-icon name="clipboard-document-check" size="sm" /> Back to borrowings
            </a>
            <h1 class="flex items-center gap-3 text-2xl font-bold tracking-tight text-slate-950">
                <x-icon name="clipboard-document-check" class="text-primary-600" /> Borrowing #{{ $bookBorrowing->id }}
            </h1>
        </div>
        <x-badge :variant="$statusVariant">{{ $isOverdue ? 'Overdue' : ucfirst($bookBorrowing->status) }}</x-badge>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <x-card title="Loan details" class="lg:col-span-2">
            <dl class="grid gap-x-6 gap-y-5 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-slate-500">Book</dt>
                    <dd class="mt-1"><a href="{{ route('books.show', $bookBorrowing->bookCopy->book) }}" class="inline-flex items-center gap-2 font-medium text-primary-600 hover:text-primary-700"><x-icon name="book-open" size="sm" />{{ $bookBorrowing->bookCopy->book->title }}</a></dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Copy</dt>
                    <dd class="mt-1"><a href="{{ route('book-copies.show', $bookBorrowing->bookCopy) }}" class="font-medium text-primary-600 hover:text-primary-700">{{ $bookBorrowing->bookCopy->copy_number }}</a></dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Borrower</dt>
                    <dd class="mt-1 flex items-center gap-2 font-medium text-slate-900">
                        <x-icon name="user-circle" size="sm" class="text-primary-600" />
                        @if($borrower)
                            {{ $borrower->first_name }} {{ $borrower->last_name }} ({{ $bookBorrowing->student ? 'Student' : 'Teacher' }})
                        @else
                            Unknown borrower
                        @endif
                    </dd>
                </div>
                <div><dt class="text-sm text-slate-500">Issued by</dt><dd class="mt-1 font-medium text-slate-900">{{ $bookBorrowing->issuedBy?->name ?? '—' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Borrowed</dt><dd class="mt-1 font-medium text-slate-900">{{ $bookBorrowing->borrowed_date->format('Y-m-d') }}</dd></div>
                <div><dt class="text-sm text-slate-500">Due</dt><dd class="mt-1 font-medium {{ $isOverdue ? 'text-danger-600' : 'text-slate-900' }}">{{ $bookBorrowing->due_date->format('Y-m-d') }}</dd></div>
                <div><dt class="text-sm text-slate-500">Returned</dt><dd class="mt-1 font-medium text-slate-900">{{ $bookBorrowing->returned_date?->format('Y-m-d') ?? '—' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Renewals</dt><dd class="mt-1 font-medium text-slate-900">{{ $bookBorrowing->renewal_count }} / {{ config('library.max_renewals', 2) }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-sm text-slate-500">Remarks</dt><dd class="mt-1 whitespace-pre-line text-slate-900">{{ $bookBorrowing->remarks ?: '—' }}</dd></div>
            </dl>
        </x-card>

        <x-card title="Actions">
            @if($bookBorrowing->status === 'borrowed' && $bookBorrowing->returned_date === null)
                <div class="flex flex-col gap-3">
                    @if(auth()->user()->hasPermission('borrowings.return'))
                        <form action="{{ route('book-borrowings.return', $bookBorrowing) }}" method="POST" onsubmit="return confirm('Record this book as returned?')">
                            @csrf
                            <x-button type="submit" icon="check" class="w-full">Return Book</x-button>
                        </form>
                    @endif
                    @if(auth()->user()->hasPermission('borrowings.renew'))
                        <form action="{{ route('book-borrowings.renew', $bookBorrowing) }}" method="POST">
                            @csrf
                            <x-button type="submit" variant="secondary" icon="calendar-days" class="w-full">Renew</x-button>
                        </form>
                    @endif
                    @if(!auth()->user()->hasPermission('borrowings.return') && !auth()->user()->hasPermission('borrowings.renew'))
                        <p class="text-sm text-slate-500">No actions are available for your account.</p>
                    @endif
                </div>
            @else
                <p class="text-sm text-slate-500">This borrowing is complete.</p>
            @endif
        </x-card>

        <x-card title="Fines" class="lg:col-span-3">
            @if($bookBorrowing->fines->isEmpty())
                <div class="flex items-center gap-3 text-slate-600">
                    <x-icon name="currency-dollar" class="text-success-600" />
                    <p>No fines recorded for this borrowing.</p>
                </div>
            @else
                <x-table.index>
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th scope="col" class="px-4 py-3">Issued</th>
                            <th scope="col" class="px-4 py-3">Reason</th>
                            <th scope="col" class="px-4 py-3">Amount</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($bookBorrowing->fines as $fine)
                            <tr class="hover:bg-slate-50">
                                <td class="whitespace-nowrap px-4 py-3">{{ $fine->issued_date->format('Y-m-d') }}</td>
                                <td class="px-4 py-3">{{ ucfirst($fine->reason) }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ config('library.currency', 'TZS') }} {{ number_format((float) $fine->amount, 2) }}</td>
                                <td class="px-4 py-3">
                                    <x-badge :variant="match ($fine->status) { 'unpaid' => 'warning', 'paid' => 'success', default => 'default' }">{{ ucfirst($fine->status) }}</x-badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table.index>
            @endif
        </x-card>
    </div>
@endsection
