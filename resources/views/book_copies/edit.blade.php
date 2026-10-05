@extends('layouts.app')

@section('title', 'Edit Book Copy')

@section('content')
    <div class="mb-6">
        <a href="{{ route('book-copies.show', $bookCopy) }}" class="mb-3 inline-flex items-center gap-2 text-sm font-medium text-primary-600 hover:text-primary-700">
            <x-icon name="books" size="sm" /> Back to copy
        </a>
        <h1 class="flex items-center gap-3 text-2xl font-bold tracking-tight text-slate-950">
            <x-icon name="pencil-square" class="text-warning-600" /> Edit Book Copy
        </h1>
        <p class="mt-1 text-sm text-slate-600">{{ $bookCopy->copy_number }}</p>
    </div>

    <x-card>
        <form method="POST" action="{{ route('book-copies.update', $bookCopy) }}" class="space-y-6">
            @csrf
            @method('PUT')
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <x-form.select name="book_id" label="Book" required>
                    @foreach($books as $book)
                        <option value="{{ $book->id }}" @selected((string) old('book_id', $bookCopy->book_id) === (string) $book->id)>
                            {{ $book->title }}{{ $book->status === 'archived' ? ' (Archived)' : '' }}
                        </option>
                    @endforeach
                </x-form.select>
                <x-form.input name="copy_number" label="Copy number" maxlength="50" :value="$bookCopy->copy_number" required />
                <x-form.input name="barcode" label="Barcode" maxlength="100" :value="$bookCopy->barcode" />
                <x-form.select name="condition" label="Condition" required>
                    @foreach(['new', 'good', 'fair', 'poor', 'damaged'] as $option)
                        <option value="{{ $option }}" @selected(old('condition', $bookCopy->condition) === $option)>{{ ucfirst($option) }}</option>
                    @endforeach
                </x-form.select>
                <x-form.select name="status" label="Status" required>
                    @foreach(['available', 'borrowed', 'lost', 'damaged', 'retired'] as $option)
                        <option value="{{ $option }}" @selected(old('status', $bookCopy->status) === $option)>{{ ucfirst($option) }}</option>
                    @endforeach
                </x-form.select>
                <x-form.input name="acquisition_date" label="Acquisition date" type="date" :value="$bookCopy->acquisition_date?->format('Y-m-d')" />
                <x-form.input name="purchase_price" label="Purchase price" type="number" min="0" step="0.01" :value="$bookCopy->purchase_price" />
                <div class="sm:col-span-2 lg:col-span-3">
                    <x-form.textarea name="remarks" label="Remarks" maxlength="1000" :value="$bookCopy->remarks" />
                </div>
            </div>
            <div class="flex flex-wrap gap-3 border-t border-slate-100 pt-5">
                <x-button type="submit" icon="arrow-down-tray">Update Copy</x-button>
                <a href="{{ route('book-copies.show', $bookCopy) }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
            </div>
        </form>
    </x-card>
@endsection
