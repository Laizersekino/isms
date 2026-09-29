@extends('layouts.crud')

@section('content')

<h1>Edit Attendance</h1>

<a href="{{ route('attendance.index') }}">
    Back to Attendance
</a>

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

<form action="{{ route('attendance.update', $attendance) }}" method="POST">

    @csrf
    @method('PUT')

    <p>
        <label for="student_id">Student</label><br>

        <select name="student_id" id="student_id" required>

            <option value="">-- Select Student --</option>

            @foreach($students as $student)

                <option
                    value="{{ $student->id }}"
                    {{ old('student_id', $attendance->student_id) == $student->id ? 'selected' : '' }}
                >
                    {{ $student->admission_number }} -
                    {{ $student->first_name }}
                    {{ $student->last_name }}
                </option>

            @endforeach

        </select>
    </p>

    <p>
        <label for="academic_year_id">Academic Year</label><br>

        <select name="academic_year_id" id="academic_year_id" required>

            <option value="">-- Select Academic Year --</option>

            @foreach($academicYears as $academicYear)

                <option
                    value="{{ $academicYear->id }}"
                    {{ old('academic_year_id', $attendance->academic_year_id) == $academicYear->id ? 'selected' : '' }}
                >
                    {{ $academicYear->name }}
                </option>

            @endforeach

        </select>
    </p>

    <p>
        <label for="term_id">Term</label><br>

        <select name="term_id" id="term_id" required>

            <option value="">-- Select Term --</option>

            @foreach($terms as $term)

                <option
                    value="{{ $term->id }}"
                    {{ old('term_id', $attendance->term_id) == $term->id ? 'selected' : '' }}
                >
                    {{ $term->name }}
                </option>

            @endforeach

        </select>
    </p>

    <p>
        <label for="class_id">Class</label><br>

        <select name="class_id" id="class_id" required>

            <option value="">-- Select Class --</option>

            @foreach($classes as $class)

                <option
                    value="{{ $class->id }}"
                    {{ old('class_id', $attendance->class_id) == $class->id ? 'selected' : '' }}
                >
                    {{ $class->name }}
                </option>

            @endforeach

        </select>
    </p>

    <p>
        <label for="stream_id">Stream</label><br>

        <select name="stream_id" id="stream_id" required>

            <option value="">-- Select Stream --</option>

            @foreach($streams as $stream)

                <option
                    value="{{ $stream->id }}"
                    {{ old('stream_id', $attendance->stream_id) == $stream->id ? 'selected' : '' }}
                >
                    {{ $stream->classRoom->name ?? '' }} -
                    {{ $stream->name }}
                </option>

            @endforeach

        </select>

    </p>

    <p>
        <label for="attendance_date">Attendance Date</label><br>

        <input
            type="date"
            name="attendance_date"
            id="attendance_date"
            value="{{ old('attendance_date', $attendance->attendance_date) }}"
            required
        >
    </p>

    <p>
        <label for="status">Status</label><br>

        <select name="status" id="status" required>

            <option value="Present"
                {{ old('status', $attendance->status) == 'Present' ? 'selected' : '' }}>
                Present
            </option>

            <option value="Absent"
                {{ old('status', $attendance->status) == 'Absent' ? 'selected' : '' }}>
                Absent
            </option>

            <option value="Late"
                {{ old('status', $attendance->status) == 'Late' ? 'selected' : '' }}>
                Late
            </option>

            <option value="Excused"
                {{ old('status', $attendance->status) == 'Excused' ? 'selected' : '' }}>
                Excused
            </option>

        </select>

    </p>

    <p>
        <label for="remarks">Remarks</label><br>

        <textarea
            name="remarks"
            id="remarks"
            rows="4"
        >{{ old('remarks', $attendance->remarks) }}</textarea>
    </p>

    <button type="submit" class="primary">
        Update Attendance
    </button>

    <a href="{{ route('attendance.index') }}">
        Cancel
    </a>

</form>

@endsection