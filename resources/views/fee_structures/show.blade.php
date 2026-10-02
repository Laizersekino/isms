@extends('layouts.crud')

@section('content')
<h1>{{ $feeStructure->name }}</h1>

<p>Academic year: {{ $feeStructure->academicYear->name }}</p>
<p>Term: {{ $feeStructure->term->name }}</p>
<p>Class: {{ $feeStructure->classRoom->name }}</p>
<p>Status: {{ ucfirst($feeStructure->status) }}</p>
<p>Description: {{ $feeStructure->description ?? '-' }}</p>
<p>Created by: {{ $feeStructure->createdBy->name }}</p>

<h2>Fee Items</h2>
<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Amount (TZS)</th>
            <th>Mandatory</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        @foreach($feeStructure->items as $item)
            <tr>
                <td>{{ $item->name }}</td>
                <td>{{ number_format((float) $item->amount, 2) }}</td>
                <td>{{ $item->is_mandatory ? 'Yes' : 'No' }}</td>
                <td>{{ $item->description ?? '-' }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th colspan="1">Total</th>
            <th>{{ number_format((float) $feeStructure->total_amount, 2) }} TZS</th>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>

<p>
    <a href="{{ route('fee-structures.index') }}">Back to fee structures</a>
    @if(auth()->user()->hasPermission('fee_structures.update'))
        <a href="{{ route('fee-structures.edit', $feeStructure) }}" class="warning">Edit</a>
    @endif
    @if(auth()->user()->hasPermission('fee_structures.delete'))
        <form action="{{ route('fee-structures.destroy', $feeStructure) }}" method="POST" style="display:inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="danger" onclick="return confirm('Archive this fee structure?')">Archive</button>
        </form>
    @endif
</p>
@endsection
