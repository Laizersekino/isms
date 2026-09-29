@extends('layouts.crud')

@section('content')

<h1>Add Subject to Exam</h1>

<form action="{{ route('exam-subjects.store') }}" method="POST">

    @csrf

    <div>

        <label for="exam_id">
            Exam:
        </label>

        <br>

        <select
            name="exam_id"
            id="exam_id"
            required
        >

            <option value="">
                -- Select Exam --
            </option>

            @foreach($exams as $exam)

                <option
                    value="{{ $exam->id }}"
                    {{ old('exam_id') == $exam->id ? 'selected' : '' }}
                >
                    {{ $exam->name }}
                    -
                    {{ $exam->exam_type }}
                </option>

            @endforeach

        </select>

    </div>

    <br>

    <div>

        <label for="subject_id">
            Subject:
        </label>

        <br>

        <select
            name="subject_id"
            id="subject_id"
            required
        >

            <option value="">
                -- Select Subject --
            </option>

            @foreach($subjects as $subject)

                <option
                    value="{{ $subject->id }}"
                    {{ old('subject_id') == $subject->id ? 'selected' : '' }}
                >
                    {{ $subject->code }}
                    -
                    {{ $subject->name }}
                </option>

            @endforeach

        </select>

    </div>

    <br>

    <div>

        <label for="class_id">
            Class:
        </label>

        <br>

        <select
            name="class_id"
            id="class_id"
            required
        >

            <option value="">
                -- Select Class --
            </option>

            @foreach($classes as $class)

                <option
                    value="{{ $class->id }}"
                    {{ old('class_id') == $class->id ? 'selected' : '' }}
                >
                    {{ $class->name }}
                </option>

            @endforeach

        </select>

    </div>

    <br>

    <div>

        <label for="max_marks">
            Maximum Marks:
        </label>

        <br>

        <input
            type="number"
            name="max_marks"
            id="max_marks"
            value="{{ old('max_marks', 100) }}"
            min="1"
            step="0.01"
            required
        >

    </div>

    <br>

    <button type="submit" class="primary">
        Add Subject
    </button>

    <a href="{{ route('exam-subjects.index') }}">
        Cancel
    </a>

</form>

@endsection