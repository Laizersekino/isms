@extends('layouts.crud')

@section('content')
<h1>Edit Fee Structure</h1>

<form method="POST" action="{{ route('fee-structures.update', $feeStructure) }}">
    @csrf
    @method('PUT')

    @include('fee_structures.form', [
        'feeStructure' => $feeStructure,
        'items' => old('items', $feeStructure->items->map(fn ($item) => [
            'name' => $item->name,
            'amount' => $item->amount,
            'is_mandatory' => $item->is_mandatory,
            'description' => $item->description,
        ])->all()),
    ])

    <button type="submit" class="primary">Update Fee Structure</button>
    <a href="{{ route('fee-structures.show', $feeStructure) }}">Cancel</a>
</form>
@endsection
