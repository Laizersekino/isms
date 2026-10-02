@extends('layouts.crud')

@section('content')
<h1>Book Copies</h1>

<p>
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <a href="{{ route('books.index') }}">Books</a>
    @if(auth()->user()->hasPermission('book_copies.create'))
        <a href="{{ route('book-copies.create') }}" class="primary">Add Copy</a>
    @endif
</p>

<form method="GET" action="{{ route('book-copies.index') }}">
    <label for="search">Search copies</label>
    <input id="search" type="search" name="search" value="{{ $search }}" placeholder="Barcode, copy number, or book title">
    <label for="status">Status</label>
    <select id="status" name="status">
        <option value="">All statuses</option>
        @foreach(['available', 'borrowed', 'lost', 'damaged', 'retired'] as $option)
            <option value="{{ $option }}" @selected($status === $option)>{{ ucfirst($option) }}</option>
        @endforeach
    </select>
    <button type="submit">Filter</button>
    @if($search !== '' || $status !== '')
        <a href="{{ route('book-copies.index') }}">Clear</a>
    @endif
</form>

@if($bookCopies->isEmpty())
    <p>No book copies found.</p>
@else
    <table>
        <thead>
            <tr>
                <th>Book</th>
                <th>Copy Number</th>
                <th>Barcode</th>
                <th>Condition</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bookCopies as $bookCopy)
                <tr>
                    <td>{{ $bookCopy->book->title }}</td>
                    <td><a href="{{ route('book-copies.show', $bookCopy) }}">{{ $bookCopy->copy_number }}</a></td>
                    <td>{{ $bookCopy->barcode ?? '-' }}</td>
                    <td>{{ ucfirst($bookCopy->condition) }}</td>
                    <td>{{ ucfirst($bookCopy->status) }}</td>
                    <td>
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
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $bookCopies->links() }}
@endif
@endsection
