@extends('layouts.crud')

@section('content')
<h1>Book Copy {{ $bookCopy->copy_number }}</h1>

<p>Book: <a href="{{ route('books.show', $bookCopy->book) }}">{{ $bookCopy->book->title }}</a></p>
<p>Copy Number: {{ $bookCopy->copy_number }}</p>
<p>Barcode: {{ $bookCopy->barcode ?? '-' }}</p>
<p>Condition: {{ ucfirst($bookCopy->condition) }}</p>
<p>Status: {{ ucfirst($bookCopy->status) }}</p>
<p>Acquisition Date: {{ $bookCopy->acquisition_date?->format('Y-m-d') ?? '-' }}</p>
<p>Purchase Price: {{ $bookCopy->purchase_price ?? '-' }}</p>
<p>Remarks: {{ $bookCopy->remarks ?? '-' }}</p>

@if($bookCopy->currentBorrowing)
    <h2>Current Borrowing</h2>
    @if($bookCopy->currentBorrowing->student)
        <p>Borrower: {{ $bookCopy->currentBorrowing->student->first_name }} {{ $bookCopy->currentBorrowing->student->last_name }} (Student)</p>
    @elseif($bookCopy->currentBorrowing->teacher)
        <p>Borrower: {{ $bookCopy->currentBorrowing->teacher->first_name }} {{ $bookCopy->currentBorrowing->teacher->last_name }} (Teacher)</p>
    @endif
    <p>Borrowed: {{ $bookCopy->currentBorrowing->borrowed_date?->format('Y-m-d') ?? '-' }}</p>
    <p>Due: {{ $bookCopy->currentBorrowing->due_date?->format('Y-m-d') ?? '-' }}</p>
@else
    <p>No current borrowing.</p>
@endif

<a href="{{ route('book-copies.index') }}">Back to copies</a>
@if(auth()->user()->hasPermission('book_copies.update'))
    <a href="{{ route('book-copies.edit', $bookCopy) }}" class="warning">Edit</a>
@endif
@if(auth()->user()->hasPermission('book_copies.delete'))
    <form action="{{ route('book-copies.destroy', $bookCopy) }}" method="POST" style="display:inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="danger" onclick="return confirm('Delete this book copy?')">Delete</button>
    </form>
@endif
@endsection
