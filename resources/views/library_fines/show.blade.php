@extends('layouts.crud')

@section('content')
<style>
    .status-badge {
        border-radius: 4px;
        display: inline-block;
        font-weight: 600;
        padding: 3px 8px;
    }

    .status-unpaid {
        background: #fff3cd;
        color: #664d03;
    }

    .status-paid {
        background: #d1e7dd;
        color: #0f5132;
    }

    .status-waived {
        background: #e2e3e5;
        color: #41464b;
    }
</style>

<h1>Library Fine #{{ $libraryFine->id }}</h1>

<p>Borrowing: <a href="{{ route('book-borrowings.show', $libraryFine->borrowing) }}">#{{ $libraryFine->borrowing->id }}</a></p>
<p>Book: <a href="{{ route('books.show', $libraryFine->borrowing->bookCopy->book) }}">{{ $libraryFine->borrowing->bookCopy->book->title }}</a></p>
<p>Copy: <a href="{{ route('book-copies.show', $libraryFine->borrowing->bookCopy) }}">{{ $libraryFine->borrowing->bookCopy->copy_number }}</a></p>
<p>
    Borrower:
    @if($libraryFine->borrowing->student)
        {{ $libraryFine->borrowing->student->first_name }} {{ $libraryFine->borrowing->student->last_name }} (Student)
    @elseif($libraryFine->borrowing->teacher)
        {{ $libraryFine->borrowing->teacher->first_name }} {{ $libraryFine->borrowing->teacher->last_name }} (Teacher)
    @else
        Unknown borrower
    @endif
</p>
<p>Reason: {{ ucfirst($libraryFine->reason) }}</p>
<p>Amount: {{ config('library.currency', 'TZS') }} {{ number_format((float) $libraryFine->amount, 2) }}</p>
<p>Status: <span class="status-badge status-{{ $libraryFine->status }}">{{ ucfirst($libraryFine->status) }}</span></p>
<p>Issued date: {{ $libraryFine->issued_date->format('Y-m-d') }}</p>
<p>Paid date: {{ $libraryFine->paid_date?->format('Y-m-d') ?? '-' }}</p>
<p>Recorded by: {{ $libraryFine->recordedBy?->name ?? '-' }}</p>
<p>Paid by: {{ $libraryFine->paidBy?->name ?? '-' }}</p>
<p>Waived date: {{ $libraryFine->waived_date?->format('Y-m-d') ?? '-' }}</p>
<p>Waived by: {{ $libraryFine->waivedBy?->name ?? '-' }}</p>
@if($libraryFine->remarks)
    <p>Remarks / waiver reason: {{ $libraryFine->remarks }}</p>
@endif

@if($libraryFine->status === 'unpaid')
    @if(auth()->user()->hasPermission('fines.pay'))
        <form action="{{ route('library-fines.pay', $libraryFine) }}" method="POST" style="display:inline">
            @csrf
            <button type="submit" class="primary" onclick="return confirm('Record full payment of this fine?')">Mark as Paid</button>
        </form>
    @endif

    @if(auth()->user()->hasPermission('fines.waive'))
        <form action="{{ route('library-fines.waive', $libraryFine) }}" method="POST">
            @csrf
            <p>
                <label for="reason">Waiver reason</label><br>
                <textarea id="reason" name="reason" minlength="10" maxlength="1000" required>{{ old('reason') }}</textarea>
            </p>
            <button type="submit" class="warning">Waive Fine</button>
        </form>
    @endif
@endif

<p><a href="{{ route('library-fines.index') }}">Back to fines</a></p>
@endsection
