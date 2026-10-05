@extends('layouts.app')

@section('title', 'Edit Book')

@section('content')
    <div class="mb-6">
        <a href="{{ route('books.show', $book) }}" class="mb-3 inline-flex items-center gap-2 text-sm font-medium text-primary-600 hover:text-primary-700">
            <x-icon name="book-open" size="sm" /> Back to book
        </a>
        <h1 class="flex items-center gap-3 text-2xl font-bold tracking-tight text-slate-950">
            <x-icon name="pencil-square" class="text-warning-600" /> Edit Book
        </h1>
        <p class="mt-1 text-sm text-slate-600">{{ $book->title }}</p>
    </div>

    <x-card>
        <form method="POST" action="{{ route('books.update', $book) }}" class="space-y-6">
            @csrf
            @method('PUT')
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <x-form.input name="title" label="Title" maxlength="255" :value="$book->title" required />
                <x-form.input name="isbn" label="ISBN" maxlength="20" :value="$book->isbn" />
                <x-form.input name="author" label="Author" maxlength="255" :value="$book->author" required />
                <x-form.input name="publisher" label="Publisher" maxlength="255" :value="$book->publisher" />
                <x-form.input name="category" label="Category" maxlength="100" :value="$book->category" />
                <x-form.input name="edition" label="Edition" maxlength="50" :value="$book->edition" />
                <x-form.input name="publication_year" label="Publication year" type="number" min="1000" :max="now()->year" :value="$book->publication_year" />
                <x-form.select name="status" label="Status" required>
                    @foreach(['active' => 'Active', 'inactive' => 'Inactive', 'archived' => 'Archived'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $book->status) === $value)>{{ $label }}</option>
                    @endforeach
                </x-form.select>
                <div class="sm:col-span-2 lg:col-span-3">
                    <x-form.textarea name="description" label="Description" maxlength="2000" :value="$book->description" rows="4" />
                </div>
            </div>
            <div class="flex flex-wrap gap-3 border-t border-slate-100 pt-5">
                <x-button type="submit" icon="arrow-down-tray">Update Book</x-button>
                <a href="{{ route('books.show', $book) }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
            </div>
        </form>
    </x-card>
@endsection
