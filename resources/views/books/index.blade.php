@extends('layouts.crud')

@section('content')
<h1>Library Books</h1>

<p>
    <a href="{{ route('dashboard') }}">Dashboard</a>
    @if(auth()->user()->hasPermission('books.create'))
        <a href="{{ route('books.create') }}" class="primary">Add Book</a>
    @endif
</p>

<form method="GET" action="{{ route('books.index') }}">
    <label for="search">Search books</label>
    <input id="search" type="search" name="search" value="{{ $search }}" placeholder="Title, ISBN, author, publisher, or category">
    <button type="submit">Search</button>
    @if($search !== '')
        <a href="{{ route('books.index') }}">Clear</a>
    @endif
</form>

@if($books->isEmpty())
    <p>No books found.</p>
@else
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>ISBN</th>
                <th>Author</th>
                <th>Category</th>
                <th>Copies</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($books as $book)
                <tr>
                    <td><a href="{{ route('books.show', $book) }}">{{ $book->title }}</a></td>
                    <td>{{ $book->isbn ?? '-' }}</td>
                    <td>{{ $book->author }}</td>
                    <td>{{ $book->category ?? '-' }}</td>
                    <td>{{ $book->copies_count }}</td>
                    <td>{{ ucfirst($book->status) }}</td>
                    <td>
                        @if(auth()->user()->hasPermission('books.update'))
                            <a href="{{ route('books.edit', $book) }}" class="warning">Edit</a>
                        @endif

                        @if(auth()->user()->hasPermission('books.delete'))
                            <form action="{{ route('books.destroy', $book) }}" method="POST" style="display:inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="danger" onclick="return confirm('Delete this book?')">Delete</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $books->links() }}
@endif
@endsection
