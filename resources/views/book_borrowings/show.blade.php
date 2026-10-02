@extends('layouts.crud')

@section('content')
<h1>Borrowing #{{ $bookBorrowing->id }}</h1>

<p>Book: <a href="{{ route('books.show', $bookBorrowing->bookCopy->book) }}">{{ $bookBorrowing->bookCopy->book->title }}</a></p>
<p>Copy: <a href="{{ route('book-copies.show', $bookBorrowing->bookCopy) }}">{{ $bookBorrowing->bookCopy->copy_number }}</a></p>
<p>
    Borrower:
    @if($bookBorrowing->student)
        {{ $bookBorrowing->student->first_name }} {{ $bookBorrowing->student->last_name }} (Student)
    @elseif($bookBorrowing->teacher)
        {{ $bookBorrowing->teacher->first_name }} {{ $bookBorrowing->teacher->last_name }} (Teacher)
    @else
        Unknown borrower
    @endif
</p>
<p>Issued by: {{ $bookBorrowing->issuedBy?->name ?? '-' }}</p>
<p>Borrowed: {{ $bookBorrowing->borrowed_date->format('Y-m-d') }}</p>
<p>Due: {{ $bookBorrowing->due_date->format('Y-m-d') }}</p>
<p>Returned: {{ $bookBorrowing->returned_date?->format('Y-m-d') ?? '-' }}</p>
<p>Status: {{ ucfirst($bookBorrowing->status) }}</p>
<p>Renewals: {{ $bookBorrowing->renewal_count }} / {{ config('library.max_renewals', 2) }}</p>
<p>Remarks: {{ $bookBorrowing->remarks ?? '-' }}</p>

@if($bookBorrowing->fines->isNotEmpty())
    <h2>Fines</h2>
    <table>
        <thead>
            <tr>
                <th>Issued</th>
                <th>Reason</th>
                <th>Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bookBorrowing->fines as $fine)
                <tr>
                    <td>{{ $fine->issued_date->format('Y-m-d') }}</td>
                    <td>{{ ucfirst($fine->reason) }}</td>
                    <td>{{ config('library.currency', 'TZS') }} {{ number_format((float) $fine->amount, 2) }}</td>
                    <td>{{ ucfirst($fine->status) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<p>
    @if($bookBorrowing->status === 'borrowed' && $bookBorrowing->returned_date === null && auth()->user()->hasPermission('borrowings.return'))
        <form action="{{ route('book-borrowings.return', $bookBorrowing) }}" method="POST" style="display:inline">
            @csrf
            <button type="submit" class="primary">Return Book</button>
        </form>
    @endif
    @if($bookBorrowing->status === 'borrowed' && $bookBorrowing->returned_date === null && auth()->user()->hasPermission('borrowings.renew'))
        <form action="{{ route('book-borrowings.renew', $bookBorrowing) }}" method="POST" style="display:inline">
            @csrf
            <button type="submit" class="warning">Renew</button>
        </form>
    @endif
    <a href="{{ route('book-borrowings.index') }}">Back to borrowings</a>
</p>
@endsection
