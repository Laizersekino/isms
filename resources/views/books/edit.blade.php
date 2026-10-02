@extends('layouts.crud')

@section('content')
<h1>Edit Book</h1>

<form method="POST" action="{{ route('books.update', $book) }}">
    @csrf
    @method('PUT')

    <p><label for="title">Title</label><br><input id="title" name="title" maxlength="255" value="{{ old('title', $book->title) }}" required></p>
    <p><label for="isbn">ISBN</label><br><input id="isbn" name="isbn" maxlength="20" value="{{ old('isbn', $book->isbn) }}"></p>
    <p><label for="author">Author</label><br><input id="author" name="author" maxlength="255" value="{{ old('author', $book->author) }}" required></p>
    <p><label for="publisher">Publisher</label><br><input id="publisher" name="publisher" maxlength="255" value="{{ old('publisher', $book->publisher) }}"></p>
    <p><label for="category">Category</label><br><input id="category" name="category" maxlength="100" value="{{ old('category', $book->category) }}"></p>
    <p><label for="edition">Edition</label><br><input id="edition" name="edition" maxlength="50" value="{{ old('edition', $book->edition) }}"></p>
    <p><label for="publication_year">Publication year</label><br><input id="publication_year" type="number" name="publication_year" min="1000" max="{{ now()->year }}" value="{{ old('publication_year', $book->publication_year) }}"></p>
    <p><label for="description">Description</label><br><textarea id="description" name="description" maxlength="2000">{{ old('description', $book->description) }}</textarea></p>
    <p>
        <label for="status">Status</label><br>
        <select id="status" name="status" required>
            @foreach(['active' => 'Active', 'inactive' => 'Inactive', 'archived' => 'Archived'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $book->status) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </p>

    <button type="submit" class="primary">Update Book</button>
    <a href="{{ route('books.show', $book) }}">Cancel</a>
</form>
@endsection
