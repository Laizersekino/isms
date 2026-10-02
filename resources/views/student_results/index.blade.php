@extends('layouts.crud')

@section('content')

<h1>Student Results</h1>

<form method="GET" action="{{ route('student-results.index') }}">
    <div>
        <label for="admission_number">Admission Number</label><br>
        <input type="text" name="admission_number" id="admission_number" value="{{ old('admission_number', request('admission_number')) }}" placeholder="e.g. ST-001">
    </div>

    <div style="margin-top: 12px;">
        <label for="student_name">Student Name</label><br>
        <input type="text" name="student_name" id="student_name" value="{{ old('student_name', request('student_name')) }}" placeholder="Search by name">
    </div>

    <div style="margin-top: 12px;">
        <button type="submit" class="primary">Search</button>
        <a href="{{ route('student-results.index') }}">Reset</a>
    </div>
</form>

@if($students->isNotEmpty())
    <hr style="margin: 24px 0;">

    <h2>Matches</h2>

    <table>
        <thead>
            <tr>
                <th>Student</th>
                <th>Admission</th>
                <th>Class</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $student)
                <tr>
                    <td>{{ $student->first_name }} {{ $student->last_name }}</td>
                    <td>{{ $student->admission_number }}</td>
                    <td>
                        @php
                            $currentClass = $student->enrollments->firstWhere('status', 'active')?->classRoom;
                        @endphp
                        {{ $currentClass?->name ?? 'N/A' }}
                    </td>
                    <td>
                        <a href="{{ route('student-results.index', ['admission_number' => $student->admission_number]) }}">View results</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

@if($selectedStudent)
    <hr style="margin: 24px 0;">

    <h2>{{ $selectedStudent->first_name }} {{ $selectedStudent->last_name }} - {{ $selectedStudent->admission_number }}</h2>

    @if($publishedExams->isNotEmpty())
        <form method="GET" action="{{ route('student-results.index') }}" style="margin: 12px 0;">
            <input type="hidden" name="admission_number" value="{{ $selectedStudent->admission_number }}">
            <label for="exam_id">Select Exam</label>
            <select name="exam_id" id="exam_id" onchange="this.form.submit()">
                @foreach($publishedExams as $examPublication)
                    <option value="{{ $examPublication->exam_id }}" {{ ($selectedExam && $selectedExam->exam_id == $examPublication->exam_id) ? 'selected' : '' }}>
                        {{ $examPublication->exam->name ?? 'Exam' }}
                        @if($examPublication->exam && $examPublication->exam->term)
                            - {{ $examPublication->exam->term->name }}
                        @endif
                    </option>
                @endforeach
            </select>
        </form>

        <p>
            <a href="{{ route('student-results.print', ['student' => $selectedStudent->id, 'exam_id' => $selectedExam?->exam_id]) }}" target="_blank" class="primary">
                Print Result
            </a>
        </p>

        @if($resultRows->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Marks</th>
                        <th>Grade</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resultRows as $mark)
                        <tr>
                            <td>{{ $mark->examSubject->subject->name ?? 'Unknown subject' }}</td>
                            <td>{{ $mark->marks_obtained }} / {{ $mark->examSubject->max_marks }}</td>
                            <td>{{ $mark->grade }}</td>
                            <td>{{ $mark->status }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>No published result rows are available for this exam.</p>
        @endif
    @else
        <p>No published results are available for this student.</p>
    @endif
@endif

@if(!$students->isNotEmpty() && ! $selectedStudent)
    <p>No student matches your search.</p>
@endif

@endsection
