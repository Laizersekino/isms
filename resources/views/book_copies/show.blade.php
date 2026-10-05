@extends('layouts.app')

@section('title', 'Book Copy '.$bookCopy->copy_number)

@section('content')
    @php
        $statusVariant = match ($bookCopy->status) {
            'available' => 'success',
            'borrowed', 'damaged' => 'warning',
            'lost' => 'danger',
            default => 'default',
        };
    @endphp

    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
        <div>
            <a href="{{ route('book-copies.index') }}" class="mb-3 inline-flex items-center gap-2 text-sm font-medium text-primary-600 hover:text-primary-700">
                <x-icon name="books" size="sm" /> Back to copies
            </a>
            <h1 class="flex items-center gap-3 text-2xl font-bold tracking-tight text-slate-950">
                <x-icon name="books" class="text-primary-600" /> Copy {{ $bookCopy->copy_number }}
            </h1>
            <a href="{{ route('books.show', $bookCopy->book) }}" class="mt-1 inline-flex items-center gap-2 text-sm text-primary-600 hover:text-primary-700">
                <x-icon name="book-open" size="sm" /> {{ $bookCopy->book->title }}
            </a>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(auth()->user()->hasPermission('book_copies.update'))
                <a href="{{ route('book-copies.edit', $bookCopy) }}" class="inline-flex items-center gap-2 rounded-lg border border-warning-200 bg-warning-50 px-4 py-2.5 text-sm font-semibold text-warning-700 hover:bg-warning-100">
                    <x-icon name="pencil-square" size="sm" /> Edit
                </a>
            @endif
            @if(auth()->user()->hasPermission('book_copies.delete'))
                <form action="{{ route('book-copies.destroy', $bookCopy) }}" method="POST" onsubmit="return confirm('Delete this book copy?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-danger-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-danger-700">
                        <x-icon name="trash" size="sm" /> Delete
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <x-card title="Copy details" class="lg:col-span-2">
            <dl class="grid gap-x-6 gap-y-5 sm:grid-cols-2">
                <div><dt class="text-sm text-slate-500">Copy number</dt><dd class="mt-1 font-medium text-slate-900">{{ $bookCopy->copy_number }}</dd></div>
                <div><dt class="text-sm text-slate-500">Barcode</dt><dd class="mt-1 font-medium text-slate-900">{{ $bookCopy->barcode ?? '—' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Condition</dt><dd class="mt-1 font-medium text-slate-900">{{ ucfirst($bookCopy->condition) }}</dd></div>
                <div><dt class="text-sm text-slate-500">Acquisition date</dt><dd class="mt-1 font-medium text-slate-900">{{ $bookCopy->acquisition_date?->format('Y-m-d') ?? '—' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Purchase price</dt><dd class="mt-1 font-medium text-slate-900">{{ $bookCopy->purchase_price ?? '—' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-sm text-slate-500">Remarks</dt><dd class="mt-1 whitespace-pre-line text-slate-900">{{ $bookCopy->remarks ?: '—' }}</dd></div>
            </dl>
            <div class="mt-6 border-t border-slate-100 pt-5">
                <p class="mb-2 text-sm text-slate-500">Status</p>
                <x-badge :variant="$statusVariant">{{ ucfirst($bookCopy->status) }}</x-badge>
            </div>
        </x-card>

        <x-card title="Current borrowing">
            @if($bookCopy->currentBorrowing)
                @php($currentBorrower = $bookCopy->currentBorrowing->student ?? $bookCopy->currentBorrowing->teacher)
                <div class="flex flex-col gap-4">
                    @if($currentBorrower)
                        <div>
                            <p class="text-sm text-slate-500">Borrower</p>
                            <p class="mt-1 flex items-center gap-2 font-medium text-slate-900">
                                {{ $currentBorrower->first_name }} {{ $currentBorrower->last_name }} ({{ $bookCopy->currentBorrowing->student ? 'Student' : 'Teacher' }})
                                <x-icon name="user-circle" size="sm" class="text-primary-600" />
                            </p>
                        </div>
                    @endif
                    <div><p class="text-sm text-slate-500">Borrowed</p><p class="mt-1 font-medium text-slate-900">{{ $bookCopy->currentBorrowing->borrowed_date?->format('Y-m-d') ?? '—' }}</p></div>
                    <div><p class="text-sm text-slate-500">Due</p><p class="mt-1 font-medium text-slate-900">{{ $bookCopy->currentBorrowing->due_date?->format('Y-m-d') ?? '—' }}</p></div>
                    <a href="{{ route('book-borrowings.show', $bookCopy->currentBorrowing) }}" class="inline-flex items-center gap-2 font-medium text-primary-600 hover:text-primary-700">
                        <x-icon name="book-open" size="sm" /> View borrowing
                    </a>
                </div>
            @else
                <div class="flex items-center gap-3 text-slate-600">
                    <x-icon name="clipboard-document-check" class="text-success-600" />
                    <p>No current borrowing.</p>
                </div>
            @endif
        </x-card>
    </div>
@endsection
