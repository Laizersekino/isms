@extends('layouts.crud')

@section('content')
<h1>Issue Book</h1>

@if($availableCopies->isEmpty())
    <p>No available book copies can be issued.</p>
@else
    <form method="POST" action="{{ route('book-borrowings.store') }}">
        @csrf

        <p>
            <label for="book_copy_id">Book Copy</label><br>
            <select id="book_copy_id" name="book_copy_id" required>
                <option value="">Select a copy</option>
                @foreach($availableCopies as $copy)
                    <option value="{{ $copy->id }}" @selected((string) old('book_copy_id') === (string) $copy->id)>
                        {{ $copy->book->title }} — {{ $copy->copy_number }}
                    </option>
                @endforeach
            </select>
        </p>
        <p>
            <label for="borrower_type">Borrower type</label><br>
            <select id="borrower_type" name="borrower_type" required>
                <option value="student" @selected(old('borrower_type', 'student') === 'student')>Student</option>
                <option value="teacher" @selected(old('borrower_type') === 'teacher')>Teacher</option>
            </select>
        </p>
        <p id="student_borrower">
            <label for="student_borrower_id">Student</label><br>
            <select id="student_borrower_id" name="borrower_id" required>
                <option value="">Select a student</option>
                @foreach($students as $student)
                    <option value="{{ $student->id }}" @selected(old('borrower_type', 'student') === 'student' && (string) old('borrower_id') === (string) $student->id)>
                        {{ $student->admission_number }} — {{ $student->first_name }} {{ $student->last_name }}
                    </option>
                @endforeach
            </select>
        </p>
        <p id="teacher_borrower" hidden>
            <label for="teacher_borrower_id">Teacher</label><br>
            <select id="teacher_borrower_id" name="borrower_id" disabled required>
                <option value="">Select a teacher</option>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}" @selected(old('borrower_type') === 'teacher' && (string) old('borrower_id') === (string) $teacher->id)>
                        {{ $teacher->employee_number }} — {{ $teacher->first_name }} {{ $teacher->last_name }}
                    </option>
                @endforeach
            </select>
        </p>
        <p>
            <label for="borrowed_date">Borrowed date</label><br>
            <input id="borrowed_date" type="date" name="borrowed_date" value="{{ old('borrowed_date', today()->toDateString()) }}" required>
        </p>
        <p>
            <label for="due_date">Due date</label><br>
            <input id="due_date" type="date" name="due_date" value="{{ old('due_date', today()->addDays(config('library.loan_period_days', 14))->toDateString()) }}" required>
        </p>
        <p>
            <label for="remarks">Remarks</label><br>
            <textarea id="remarks" name="remarks" maxlength="1000">{{ old('remarks') }}</textarea>
        </p>

        <button type="submit" class="primary">Issue Book</button>
        <a href="{{ route('book-borrowings.index') }}">Cancel</a>
    </form>

    <script>
        const borrowerType = document.getElementById('borrower_type');
        const studentBorrower = document.getElementById('student_borrower');
        const teacherBorrower = document.getElementById('teacher_borrower');
        const studentId = document.getElementById('student_borrower_id');
        const teacherId = document.getElementById('teacher_borrower_id');

        function updateBorrowerOptions() {
            const isStudent = borrowerType.value === 'student';
            studentBorrower.hidden = !isStudent;
            teacherBorrower.hidden = isStudent;
            studentId.disabled = !isStudent;
            teacherId.disabled = isStudent;
            studentId.required = isStudent;
            teacherId.required = !isStudent;
        }

        borrowerType.addEventListener('change', updateBorrowerOptions);
        updateBorrowerOptions();
    </script>
@endif
@endsection
