@extends('layouts.crud')

@section('content')

<h1>Assign Subject to Teacher</h1>

<a href="{{ route('teacher-subjects.index') }}">Back</a>

@if($errors->any())
    <div>
        <strong>Please correct these errors:</strong>

        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('teacher-subjects.store') }}" method="POST">

    @csrf

    <p>
        <label>Teacher</label><br>

        <select name="teacher_id" required>

            <option value="">Select Teacher</option>

            @foreach($teachers as $teacher)

                <option value="{{ $teacher->id }}"
                    {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>

                    {{ $teacher->employee_number }} -
                    {{ $teacher->first_name }}
                    {{ $teacher->last_name }}

                </option>

            @endforeach

        </select>
    </p>

    <p>
        <label>Subject</label><br>

        <select name="subject_id" required>

            <option value="">Select Subject</option>

            @foreach($subjects as $subject)

                <option value="{{ $subject->id }}"
                    {{ old('subject_id') == $subject->id ? 'selected' : '' }}>

                    {{ $subject->code }} - {{ $subject->name }}

                </option>

            @endforeach

        </select>
    </p>

    <p>
        <label>Class</label><br>

        <select name="class_id" required>

            <option value="">Select Class</option>

            @foreach($classes as $class)

                <option value="{{ $class->id }}"
                    {{ old('class_id') == $class->id ? 'selected' : '' }}>

                    {{ $class->name }}

                </option>

            @endforeach

        </select>

    </p>

    <button type="submit" class="primary">
        Assign Subject
    </button>

</form>

@endsection