@extends('layouts.crud')

@section('content')
<h1>Fee Structures</h1>

<p>
    <a href="{{ route('dashboard') }}">Dashboard</a>
    @if(auth()->user()->hasPermission('fee_structures.create'))
        <a href="{{ route('fee-structures.create') }}" class="primary">Add Fee Structure</a>
    @endif
</p>

<form method="GET" action="{{ route('fee-structures.index') }}">
    <label for="academic_year_id">Academic year</label>
    <select id="academic_year_id" name="academic_year_id">
        <option value="">All years</option>
        @foreach($academicYears as $academicYear)
            <option value="{{ $academicYear->id }}" @selected((string) ($filters['academic_year_id'] ?? '') === (string) $academicYear->id)>
                {{ $academicYear->name }}
            </option>
        @endforeach
    </select>
    <label for="term_id">Term</label>
    <select id="term_id" name="term_id">
        <option value="">All terms</option>
        @foreach($terms as $term)
            <option value="{{ $term->id }}" @selected((string) ($filters['term_id'] ?? '') === (string) $term->id)>
                {{ $term->academicYear->name }} — {{ $term->name }}
            </option>
        @endforeach
    </select>
    <label for="class_id">Class</label>
    <select id="class_id" name="class_id">
        <option value="">All classes</option>
        @foreach($classes as $class)
            <option value="{{ $class->id }}" @selected((string) ($filters['class_id'] ?? '') === (string) $class->id)>
                {{ $class->name }}
            </option>
        @endforeach
    </select>
    <label for="status">Status</label>
    <select id="status" name="status">
        <option value="">All statuses</option>
        @foreach(['draft', 'active', 'archived'] as $status)
            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
    <button type="submit">Filter</button>
    @if(count(array_filter($filters, fn ($value) => $value !== null && $value !== '' && $value !== false)) > 0)
        <a href="{{ route('fee-structures.index') }}">Clear</a>
    @endif
</form>

@if($feeStructures->isEmpty())
    <p>No fee structures found.</p>
@else
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Academic year</th>
                <th>Term</th>
                <th>Class</th>
                <th>Total (TZS)</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($feeStructures as $feeStructure)
                <tr>
                    <td><a href="{{ route('fee-structures.show', $feeStructure) }}">{{ $feeStructure->name }}</a></td>
                    <td>{{ $feeStructure->academicYear->name }}</td>
                    <td>{{ $feeStructure->term->name }}</td>
                    <td>{{ $feeStructure->classRoom->name }}</td>
                    <td>{{ number_format((float) $feeStructure->total_amount, 2) }}</td>
                    <td>{{ ucfirst($feeStructure->status) }}</td>
                    <td>
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
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $feeStructures->links() }}
@endif
@endsection
