@extends('layouts.crud')

@section('content')
<h1>Add Book Copy</h1>

<form method="POST" action="{{ route('book-copies.store') }}">
    @csrf

    <p>
        <label for="book_id">Book</label><br>
        <select id="book_id" name="book_id" required>
            <option value="">Select a book</option>
            @foreach($books as $book)
                <option value="{{ $book->id }}" @selected((string) old('book_id') === (string) $book->id)>
                    {{ $book->title }}{{ $book->status === 'archived' ? ' (Archived)' : '' }}
                </option>
            @endforeach
        </select>
    </p>
    <p><label for="copy_number">Copy Number</label><br><input id="copy_number" name="copy_number" maxlength="50" value="{{ old('copy_number') }}" required></p>
    <p><label for="barcode">Barcode</label><br><input id="barcode" name="barcode" maxlength="100" value="{{ old('barcode') }}"></p>
    <p>
        <label for="condition">Condition</label><br>
        <select id="condition" name="condition" required>
            @foreach(['new', 'good', 'fair', 'poor', 'damaged'] as $option)
                <option value="{{ $option }}" @selected(old('condition', 'good') === $option)>{{ ucfirst($option) }}</option>
            @endforeach
        </select>
    </p>
    <p>
        <label for="status">Status</label><br>
        <select id="status" name="status" required>
            @foreach(['available', 'borrowed', 'lost', 'damaged', 'retired'] as $option)
                <option value="{{ $option }}" @selected(old('status', 'available') === $option)>{{ ucfirst($option) }}</option>
            @endforeach
        </select>
    </p>
    <p><label for="acquisition_date">Acquisition Date</label><br><input id="acquisition_date" type="date" name="acquisition_date" value="{{ old('acquisition_date') }}"></p>
    <p><label for="purchase_price">Purchase Price</label><br><input id="purchase_price" type="number" name="purchase_price" min="0" step="0.01" value="{{ old('purchase_price') }}"></p>
    <p><label for="remarks">Remarks</label><br><textarea id="remarks" name="remarks" maxlength="1000">{{ old('remarks') }}</textarea></p>

    <button type="submit" class="primary">Save Copy</button>
    <a href="{{ route('book-copies.index') }}">Cancel</a>
</form>
@endsection
