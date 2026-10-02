@extends('layouts.crud')

@section('content')
<style>
    .status-badge {
        border-radius: 4px;
        display: inline-block;
        font-weight: 600;
        padding: 3px 8px;
    }

    .status-borrowed {
        background: #fff3cd;
        color: #664d03;
    }

    .status-returned {
        background: #d1e7dd;
        color: #0f5132;
    }
</style>

<h1>Book Borrowings</h1>

<p>
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <a href="{{ route('books.index') }}">Books</a>
    @if(auth()->user()->hasPermission('borrowings.issue'))
        <a href="{{ route('book-borrowings.create') }}" class="primary">Issue Book</a>
    @endif
</p>

<form method="GET" action="{{ route('book-borrowings.index') }}">
    <label for="status">Status</label>
    <select id="status" name="status">
        <option value="">All statuses</option>
        @foreach(['borrowed', 'returned'] as $option)
            <option value="{{ $option }}" @selected(($filters['status'] ?? '') === $option)>{{ ucfirst($option) }}</option>
        @endforeach
    </select>
    <label for="student_id">Student ID</label>
    <input id="student_id" type="number" name="student_id" min="1" value="{{ $filters['student_id'] ?? '' }}">
    <label for="teacher_id">Teacher ID</label>
    <input id="teacher_id" type="number" name="teacher_id" min="1" value="{{ $filters['teacher_id'] ?? '' }}">
    <label for="overdue">
        <input id="overdue" type="checkbox" name="overdue" value="1" @checked(($filters['overdue'] ?? false) == 1)>
        Overdue only
    </label>
    <button type="submit">Filter</button>
    @if(count(array_filter($filters, fn ($value) => $value !== null && $value !== '' && $value !== false)) > 0)
        <a href="{{ route('book-borrowings.index') }}">Clear</a>
    @endif
</form>

@if($borrowings->isEmpty())
    <p>No book borrowings found.</p>
@else
    <table>
        <thead>
            <tr>
                <th>Book</th>
                <th>Copy</th>
                <th>Borrower</th>
                <th>Borrowed</th>
                <th>Due</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($borrowings as $borrowing)
                @php($isOverdue = $borrowing->status === 'borrowed' && $borrowing->due_date->lt(today()))
                <tr @if($isOverdue) style="background:#fff0f0" @endif>
                    <td>{{ $borrowing->bookCopy->book->title }}</td>
                    <td>{{ $borrowing->bookCopy->copy_number }}</td>
                    <td>
                        @if($borrowing->student)
                            {{ $borrowing->student->first_name }} {{ $borrowing->student->last_name }} (Student)
                        @elseif($borrowing->teacher)
                            {{ $borrowing->teacher->first_name }} {{ $borrowing->teacher->last_name }} (Teacher)
                        @else
                            Unknown borrower
                        @endif
                    </td>
                    <td>{{ $borrowing->borrowed_date->format('Y-m-d') }}</td>
                    <td>{{ $borrowing->due_date->format('Y-m-d') }}</td>
                    <td>
                        <span class="status-badge status-{{ $borrowing->status }}">
                            {{ $isOverdue ? 'Overdue' : ucfirst($borrowing->status) }}
                        </span>
                    </td>
                    <td><a href="{{ route('book-borrowings.show', $borrowing) }}">Details</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $borrowings->links() }}
@endif
@endsection
