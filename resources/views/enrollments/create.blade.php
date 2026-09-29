@extends('layouts.crud')

@section('content')

<h1>Enroll Student</h1>

<a href="{{ route('enrollments.index') }}">Back</a>

<form action="{{ route('enrollments.store') }}" method="POST">

    @csrf

    <p>
        <label>Student</label><br>

        <select name="student_id" required>

            <option value="">Select Student</option>

            @foreach($students as $student)

                <option value="{{ $student->id }}">

                    {{ $student->admission_number }} -
                    {{ $student->first_name }}
                    {{ $student->last_name }}

                </option>

            @endforeach

        </select>
    </p>

    <p>
        <label>Academic Year</label><br>

        <select name="academic_year_id" required>

            <option value="">Select Academic Year</option>

            @foreach($academicYears as $year)

                <option value="{{ $year->id }}">
                    {{ $year->name }}
                </option>

            @endforeach

        </select>
    </p>

    <p>
        <label>Class</label><br>

        <select name="class_id" required>

            <option value="">Select Class</option>

            @foreach($classes as $class)

                <option value="{{ $class->id }}">
                    {{ $class->name }}
                </option>

            @endforeach

        </select>
    </p>

    <p>
        <label>Stream</label><br>

        <select name="stream_id" required>

            <option value="">Select Stream</option>

            @foreach($streams as $stream)

                <option value="{{ $stream->id }}">

                    {{ $stream->classRoom->name ?? '' }}
                    -
                    {{ $stream->name }}

                </option>

            @endforeach

        </select>
    </p>

    <p>
        <label>Status</label><br>

        <select name="status">

            <option value="active">Active</option>
            <option value="completed">Completed</option>
            <option value="transferred">Transferred</option>
            <option value="withdrawn">Withdrawn</option>

        </select>
    </p>

    <p>
        <label>Enrollment Date</label><br>

        <input
            type="date"
            name="enrollment_date"
            value="{{ date('Y-m-d') }}"
            required
        >
    </p>

    <p>
        <label>Exit Date</label><br>

        <input type="date" name="exit_date">
    </p>

    <button type="submit">Enroll Student</button>

</form>

@endsection