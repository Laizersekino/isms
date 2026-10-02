@extends('layouts.crud')

@section('content')
<h1>{{ $book->title }}</h1>

<p>ISBN: {{ $book->isbn ?? '-' }}</p>
<p>Author: {{ $book->author }}</p>
<p>Publisher: {{ $book->publisher ?? '-' }}</p>
<p>Category: {{ $book->category ?? '-' }}</p>
<p>Edition: {{ $book->edition ?? '-' }}</p>
<p>Publication year: {{ $book->publication_year ?? '-' }}</p>
<p>Description: {{ $book->description ?? '-' }}</p>
<p>Status: {{ ucfirst($book->status) }}</p>
<p>Copies: {{ $book->copies_count }}</p>

<a href="{{ route('books.index') }}">Back to books</a>
@if(auth()->user()->hasPermission('books.update'))
    <a href="{{ route('books.edit', $book) }}" class="warning">Edit</a>
@endif
@endsection
