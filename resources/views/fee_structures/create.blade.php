@extends('layouts.crud')

@section('content')
<h1>Create Fee Structure</h1>

<form method="POST" action="{{ route('fee-structures.store') }}">
    @csrf

    @include('fee_structures.form', [
        'feeStructure' => null,
        'items' => old('items', [['name' => '', 'amount' => '', 'is_mandatory' => 1, 'description' => '']]),
    ])

    <button type="submit" class="primary">Save Fee Structure</button>
    <a href="{{ route('fee-structures.index') }}">Cancel</a>
</form>
@endsection
