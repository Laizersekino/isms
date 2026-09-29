@extends('layouts.crud')

@section('content')

<h1>Edit Student Enrollment</h1>

<a href="{{ route('enrollments.index') }}">Back</a>

<form
    action="{{ route('enrollments.update', $enrollment) }}"
    method="POST"
>

    @csrf
    @method('PUT')

    <p>
        <label>Student</label><br>

        <select name="student_id" required>

            @foreach($students as $student)

                <option
                    value="{{ $student->id }}"
                    {{ $enrollment->student_id == $student->id ? 'selected' : '' }}
                >

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

            @foreach($academicYears as $year)

                <option
                    value="{{ $year->id }}"
                    {{ $enrollment->academic_year_id == $year->id ? 'selected' : '' }}
                >
                    {{ $year->name }}
                </option>

            @endforeach

        </select>
    </p>

    <p>
        <label>Class</label><br>

        <select name="class_id" required>

            @foreach($classes as $class)

                <option
                    value="{{ $class->id }}"
                    {{ $enrollment->class_id == $class->id ? 'selected' : '' }}
                >
                    {{ $class->name }}
                </option>

            @endforeach

        </select>
    </p>

    <p>
        <label>Stream</label><br>

        <select name="stream_id" required>

            @foreach($streams as $stream)

                <option
                    value="{{ $stream->id }}"
                    {{ $enrollment->stream_id == $stream->id ? 'selected' : '' }}
                >

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

            <option
                value="active"
                {{ $enrollment->status === 'active' ? 'selected' : '' }}
            >
                Active
            </option>

            <option
                value="completed"
                {{ $enrollment->status === 'completed' ? 'selected' : '' }}
            >
                Completed
            </option>

            <option
                value="transferred"
                {{ $enrollment->status === 'transferred' ? 'selected' : '' }}
            >
                Transferred
            </option>

            <option
                value="withdrawn"
                {{ $enrollment->status === 'withdrawn' ? 'selected' : '' }}
            >
                Withdrawn
            </option>

        </select>
    </p>

    <p>
        <label>Enrollment Date</label><br>

        <input
            type="date"
            name="enrollment_date"
            value="{{ $enrollment->enrollment_date?->format('Y-m-d') }}"
            required
        >
    </p>

    <p>
        <label>Exit Date</label><br>

        <input
            type="date"
            name="exit_date"
            value="{{ $enrollment->exit_date?->format('Y-m-d') }}"
        >
    </p>

    <button type="submit">Update Enrollment</button>

</form>

@endsection