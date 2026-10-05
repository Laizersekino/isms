@extends('layouts.app')

@section('title', 'Add Book Copy')

@section('content')
    <div class="mb-6">
        <a href="{{ route('book-copies.index') }}" class="mb-3 inline-flex items-center gap-2 text-sm font-medium text-primary-600 hover:text-primary-700">
            <x-icon name="books" size="sm" /> Back to copies
        </a>
        <h1 class="flex items-center gap-3 text-2xl font-bold tracking-tight text-slate-950">
            <x-icon name="plus" class="text-primary-600" /> Add Book Copy
        </h1>
        <p class="mt-1 text-sm text-slate-600">Register an individual copy in library inventory.</p>
    </div>

    <x-card>
        <form method="POST" action="{{ route('book-copies.store') }}" class="space-y-6">
            @csrf
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <x-form.select name="book_id" label="Book" required>
                    <option value="">Select a book</option>
                    @foreach($books as $book)
                        <option value="{{ $book->id }}" @selected((string) old('book_id') === (string) $book->id)>
                            {{ $book->title }}{{ $book->status === 'archived' ? ' (Archived)' : '' }}
                        </option>
                    @endforeach
                </x-form.select>
                <x-form.input name="copy_number" label="Copy number" maxlength="50" :value="old('copy_number')" required />
                <x-form.input name="barcode" label="Barcode" maxlength="100" :value="old('barcode')" />
                <x-form.select name="condition" label="Condition" required>
                    @foreach(['new', 'good', 'fair', 'poor', 'damaged'] as $option)
                        <option value="{{ $option }}" @selected(old('condition', 'good') === $option)>{{ ucfirst($option) }}</option>
                    @endforeach
                </x-form.select>
                <x-form.select name="status" label="Status" required>
                    @foreach(['available', 'borrowed', 'lost', 'damaged', 'retired'] as $option)
                        <option value="{{ $option }}" @selected(old('status', 'available') === $option)>{{ ucfirst($option) }}</option>
                    @endforeach
                </x-form.select>
                <x-form.input name="acquisition_date" label="Acquisition date" type="date" :value="old('acquisition_date')" />
                <x-form.input name="purchase_price" label="Purchase price" type="number" min="0" step="0.01" :value="old('purchase_price')" />
                <div class="sm:col-span-2 lg:col-span-3">
                    <x-form.textarea name="remarks" label="Remarks" maxlength="1000" :value="old('remarks')" />
                </div>
            </div>
            <div class="flex flex-wrap gap-3 border-t border-slate-100 pt-5">
                <x-button type="submit" icon="arrow-down-tray">Save Copy</x-button>
                <a href="{{ route('book-copies.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
            </div>
        </form>
    </x-card>
@endsection
