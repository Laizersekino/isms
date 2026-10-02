@extends('layouts.crud')

@section('content')
<style>
    .status-badge {
        border-radius: 4px;
        display: inline-block;
        font-weight: 600;
        padding: 3px 8px;
    }

    .status-unpaid {
        background: #fff3cd;
        color: #664d03;
    }

    .status-paid {
        background: #d1e7dd;
        color: #0f5132;
    }

    .status-waived {
        background: #e2e3e5;
        color: #41464b;
    }
</style>

<h1>Library Fines</h1>

<p>
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <a href="{{ route('book-borrowings.index') }}">Borrowings</a>
</p>

<form method="GET" action="{{ route('library-fines.index') }}">
    <label for="status">Status</label>
    <select id="status" name="status">
        <option value="">All statuses</option>
        @foreach(['unpaid', 'paid', 'waived'] as $option)
            <option value="{{ $option }}" @selected(($filters['status'] ?? '') === $option)>{{ ucfirst($option) }}</option>
        @endforeach
    </select>
    <label for="student_id">Student ID</label>
    <input id="student_id" type="number" name="student_id" min="1" value="{{ $filters['student_id'] ?? '' }}">
    <label for="teacher_id">Teacher ID</label>
    <input id="teacher_id" type="number" name="teacher_id" min="1" value="{{ $filters['teacher_id'] ?? '' }}">
    <label for="date_from">Issued from</label>
    <input id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
    <label for="date_to">Issued to</label>
    <input id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
    <button type="submit">Filter</button>
    @if(count(array_filter($filters, fn ($value) => $value !== null && $value !== '' && $value !== false)) > 0)
        <a href="{{ route('library-fines.index') }}">Clear</a>
    @endif
</form>

@if($fines->isEmpty())
    <p>No library fines found.</p>
@else
    <table>
        <thead>
            <tr>
                <th>Borrower</th>
                <th>Book</th>
                <th>Issued</th>
                <th>Reason</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($fines as $fine)
                <tr>
                    <td>
                        @if($fine->borrowing->student)
                            {{ $fine->borrowing->student->first_name }} {{ $fine->borrowing->student->last_name }} (Student)
                        @elseif($fine->borrowing->teacher)
                            {{ $fine->borrowing->teacher->first_name }} {{ $fine->borrowing->teacher->last_name }} (Teacher)
                        @else
                            Unknown borrower
                        @endif
                    </td>
                    <td>{{ $fine->borrowing->bookCopy->book->title }}</td>
                    <td>{{ $fine->issued_date->format('Y-m-d') }}</td>
                    <td>{{ ucfirst($fine->reason) }}</td>
                    <td>{{ config('library.currency', 'TZS') }} {{ number_format((float) $fine->amount, 2) }}</td>
                    <td><span class="status-badge status-{{ $fine->status }}">{{ ucfirst($fine->status) }}</span></td>
                    <td><a href="{{ route('library-fines.show', $fine) }}">Details</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $fines->links() }}
@endif
@endsection
