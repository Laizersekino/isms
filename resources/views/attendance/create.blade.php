@extends('layouts.crud')

@section('content')

<h1>Record Attendance</h1>

<form action="{{ route('attendance.create') }}" method="GET">

<div>
    <label for="assignment_id">Teacher Assignment:</label><br>

    <select name="assignment_id" id="assignment_id" required>
        <option value="">-- Select Assignment --</option>

        @foreach($assignments as $assignment)

            <option value="{{ $assignment->id }}"
                {{ request('assignment_id') == $assignment->id ? 'selected' : '' }}>

                {{ $assignment->classRoom->name }}
                -
                {{ $assignment->stream->name }}
                -
                {{ $assignment->subject->name }}
                -
                {{ $assignment->academicYear->name }}

            </option>

        @endforeach

    </select>
</div>

<br>

<button type="submit" class="primary">
    Load Students
</button>

</form>

@if($selectedAssignment)

<hr>

<h2>
    {{ $selectedAssignment->classRoom->name }}
    -
    {{ $selectedAssignment->stream->name }}
    -
    {{ $selectedAssignment->subject->name }}
</h2>

<form action="{{ route('attendance.store') }}" method="POST">

    @csrf

    <input type="hidden"
           name="assignment_id"
           value="{{ $selectedAssignment->id }}">

    <input type="hidden"
           name="academic_year_id"
           value="{{ $selectedAssignment->academic_year_id }}">

    <input type="hidden"
           name="class_id"
           value="{{ $selectedAssignment->class_id }}">

    <input type="hidden"
           name="stream_id"
           value="{{ $selectedAssignment->stream_id }}">

    <div>
        <label for="attendance_date">Attendance Date:</label><br>

        <input type="date"
               name="attendance_date"
               id="attendance_date"
               value="{{ old('attendance_date', date('Y-m-d')) }}"
               required>
    </div>

    <br>

    @if($students->count() > 0)

        <table>

            <thead>
                <tr>
                    <th>#</th>
                    <th>Admission Number</th>
                    <th>Student Name</th>
                    <th>Attendance Status</th>
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

                            <select name="students[{{ $student->id }}][status]" required>

                                <option value="Present">
                                    Present
                                </option>

                                <option value="Absent">
                                    Absent
                                </option>

                                <option value="Late">
                                    Late
                                </option>

                                <option value="Excused">
                                    Excused
                                </option>

                            </select>

                        </td>

                        <td>

                            <input type="text"
                                   name="students[{{ $student->id }}][remarks]"
                                   placeholder="Optional">

                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

        <br>

        <button type="submit" class="primary">
            Save All Attendance
        </button>

    @else

        <p>
            No active students are enrolled in this class and stream
            for the selected academic year.
        </p>

    @endif

</form>

@endif

@endsection
