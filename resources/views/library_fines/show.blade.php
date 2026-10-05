@extends('layouts.app')

@section('title', 'Library Fine #'.$libraryFine->id)

@section('content')
    @php
        $fineBorrowing = $libraryFine->borrowing;
        $fineCopy = $fineBorrowing->bookCopy;
        $fineBook = $fineCopy->book;
        $borrower = $fineBorrowing->student ?? $fineBorrowing->teacher;
        $statusVariant = match ($libraryFine->status) {
            'unpaid' => 'warning',
            'paid' => 'success',
            default => 'default',
        };
    @endphp

    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
        <div>
            <a href="{{ route('library-fines.index') }}" class="mb-3 inline-flex items-center gap-2 text-sm font-medium text-primary-600 hover:text-primary-700">
                <x-icon name="currency-dollar" size="sm" /> Back to fines
            </a>
            <h1 class="flex items-center gap-3 text-2xl font-bold tracking-tight text-slate-950">
                <x-icon name="currency-dollar" class="text-primary-600" /> Library Fine #{{ $libraryFine->id }}
            </h1>
        </div>
        <x-badge :variant="$statusVariant">{{ ucfirst($libraryFine->status) }}</x-badge>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <x-card title="Fine details" class="lg:col-span-2">
            <dl class="grid gap-x-6 gap-y-5 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-slate-500">Borrowing</dt>
                    <dd class="mt-1"><a href="{{ route('book-borrowings.show', $fineBorrowing) }}" class="font-medium text-primary-600 hover:text-primary-700">#{{ $fineBorrowing->id }}</a></dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Book</dt>
                    <dd class="mt-1"><a href="{{ route('books.show', $fineBook) }}" class="inline-flex items-center gap-2 font-medium text-primary-600 hover:text-primary-700"><x-icon name="book-open" size="sm" />{{ $fineBook->title }}</a></dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Copy</dt>
                    <dd class="mt-1"><a href="{{ route('book-copies.show', $fineCopy) }}" class="font-medium text-primary-600 hover:text-primary-700">{{ $fineCopy->copy_number }}</a></dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Borrower</dt>
                    <dd class="mt-1 flex items-center gap-2 font-medium text-slate-900">
                        <x-icon name="user-circle" size="sm" class="text-primary-600" />
                        @if($borrower)
                            {{ $borrower->first_name }} {{ $borrower->last_name }} ({{ $fineBorrowing->student ? 'Student' : 'Teacher' }})
                        @else
                            Unknown borrower
                        @endif
                    </dd>
                </div>
                <div><dt class="text-sm text-slate-500">Reason</dt><dd class="mt-1 font-medium text-slate-900">{{ ucfirst($libraryFine->reason) }}</dd></div>
                <div><dt class="text-sm text-slate-500">Amount</dt><dd class="mt-1 font-semibold text-slate-900">{{ config('library.currency', 'TZS') }} {{ number_format((float) $libraryFine->amount, 2) }}</dd></div>
                <div><dt class="text-sm text-slate-500">Issued date</dt><dd class="mt-1 font-medium text-slate-900">{{ $libraryFine->issued_date->format('Y-m-d') }}</dd></div>
                <div><dt class="text-sm text-slate-500">Paid date</dt><dd class="mt-1 font-medium text-slate-900">{{ $libraryFine->paid_date?->format('Y-m-d') ?? '—' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Recorded by</dt><dd class="mt-1 font-medium text-slate-900">{{ $libraryFine->recordedBy?->name ?? '—' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Paid by</dt><dd class="mt-1 font-medium text-slate-900">{{ $libraryFine->paidBy?->name ?? '—' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Waived date</dt><dd class="mt-1 font-medium text-slate-900">{{ $libraryFine->waived_date?->format('Y-m-d') ?? '—' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Waived by</dt><dd class="mt-1 font-medium text-slate-900">{{ $libraryFine->waivedBy?->name ?? '—' }}</dd></div>
                @if($libraryFine->remarks)
                    <div class="sm:col-span-2"><dt class="text-sm text-slate-500">Remarks / waiver reason</dt><dd class="mt-1 whitespace-pre-line text-slate-900">{{ $libraryFine->remarks }}</dd></div>
                @endif
            </dl>
        </x-card>

        <x-card title="Fine actions">
            @if($libraryFine->status === 'unpaid')
                <div class="flex flex-col gap-5">
                    @if(auth()->user()->hasPermission('fines.pay'))
                        <form action="{{ route('library-fines.pay', $libraryFine) }}" method="POST" onsubmit="return confirm('Record full payment of this fine?')">
                            @csrf
                            <x-button type="submit" icon="check" class="w-full">Mark as Paid</x-button>
                        </form>
                    @endif

                    @if(auth()->user()->hasPermission('fines.waive'))
                        <form action="{{ route('library-fines.waive', $libraryFine) }}" method="POST" class="space-y-4 border-t border-slate-100 pt-5">
                            @csrf
                            <x-form.textarea id="reason" name="reason" label="Waiver reason" minlength="10" maxlength="1000" required :value="old('reason')" />
                            <x-button type="submit" variant="secondary" icon="check">Waive Fine</x-button>
                        </form>
                    @endif

                    @if(!auth()->user()->hasPermission('fines.pay') && !auth()->user()->hasPermission('fines.waive'))
                        <p class="text-sm text-slate-500">No actions are available for your account.</p>
                    @endif
                </div>
            @else
                <div class="flex items-center gap-3 text-slate-600">
                    <x-icon name="check" class="text-success-600" />
                    <p>This fine has been {{ $libraryFine->status }}.</p>
                </div>
            @endif
        </x-card>
    </div>
@endsection
