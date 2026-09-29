@extends('layouts.crud')

@section('content')

<h1>Enter Marks</h1>

<form action="{{ route('marks.create') }}" method="GET">

    <label for="exam_subject_id">
        Exam / Subject / Class:
    </label>

    <br>

    <select
        name="exam_subject_id"
        id="exam_subject_id"
        required
    >

        <option value="">
            -- Select Exam Subject --
        </option>

        @foreach($examSubjects as $examSubject)

            <option
                value="{{ $examSubject->id }}"
                {{ request('exam_subject_id') == $examSubject->id ? 'selected' : '' }}
            >

                {{ $examSubject->exam->name }}
                -
                {{ $examSubject->subject->name }}
                -
                {{ $examSubject->classRoom->name }}
                -
                Max: {{ $examSubject->max_marks }}

            </option>

        @endforeach

    </select>

    <br><br>

    <button type="submit" class="primary">
        Load Students
    </button>

</form>

@if($selectedExamSubject)

    <hr>

    <h2>
        {{ $selectedExamSubject->exam->name }}
        -
        {{ $selectedExamSubject->subject->name }}
        -
        {{ $selectedExamSubject->classRoom->name }}
    </h2>

    <p>
        Maximum Marks:
        <strong>{{ $selectedExamSubject->max_marks }}</strong>
    </p>

    @if($students->count() > 0)

        <form
            action="{{ route('marks.store') }}"
            method="POST"
        >

            @csrf

            <input
                type="hidden"
                name="exam_subject_id"
                value="{{ $selectedExamSubject->id }}"
            >

            <table>

                <thead>

                    <tr>
                        <th>#</th>
                        <th>Admission Number</th>
                        <th>Student Name</th>
                        <th>Marks</th>
                        <th>Remarks</th>
                    </tr>

                </thead>

                <tbody>

                    @foreach($students as $student)

                        <tr>

                            <td>
                                {{ $loop->iteration }}
                            </td>

                            <td>
                                {{ $student->admission_number }}
                            </td>

                            <td>
                                {{ $student->first_name }}
                                {{ $student->middle_name }}
                                {{ $student->last_name }}
                            </td>

                            <td>

                                <input
                                    type="number"
                                    name="students[{{ $student->id }}][marks_obtained]"
                                    min="0"
                                    max="{{ $selectedExamSubject->max_marks }}"
                                    step="0.01"
                                    required
                                >

                            </td>

                            <td>

                                <input
                                    type="text"
                                    name="students[{{ $student->id }}][remarks]"
                                    placeholder="Optional"
                                >

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

            <br>

            <button type="submit" class="primary">
                Save All Marks
            </button>

        </form>

    @else

        <p>
            No active students found for this class.
        </p>

    @endif

@endif

@endsection