@extends('layouts.crud')

@section('content')

<h1>Attendance</h1>

<a href="{{ route('attendance.create') }}" class="primary">
    Record Attendance
</a>

@if(session('success'))
    <div class="success">
        {{ session('success') }}
    </div>
@endif

<table>

    <thead>

        <tr>
            <th>ID</th>
            <th>Student</th>
            <th>Academic Year</th>
            <th>Term</th>
            <th>Class</th>
            <th>Stream</th>
            <th>Date</th>
            <th>Status</th>
            <th>Recorded By</th>
            <th>Recorded Time</th>
            <th>Remarks</th>
            <th>Actions</th>
        </tr>

    </thead>

    <tbody>

        @forelse($attendance as $record)

            <tr>

                <td>{{ $record->id }}</td>

                <td>
                    {{ $record->student->first_name ?? '' }}
                    {{ $record->student->last_name ?? '' }}
                </td>

                <td>
                    {{ $record->academicYear->name ?? '' }}
                </td>

                <td>
                    {{ $record->term->name ?? '' }}
                </td>

                <td>
                    {{ $record->classRoom->name ?? '' }}
                </td>

                <td>
                    {{ $record->stream->name ?? '' }}
                </td>

                <td>
                    {{ $record->attendance_date }}
                </td>

                <td>
                    {{ $record->status }}
                </td>

                <td>
                    {{ $record->recordedBy->name ?? '' }}
                </td>

                <td>
                    {{ $record->recorded_time }}
                </td>

                <td>
                    {{ $record->remarks }}
                </td>

                <td>

                    <a href="{{ route('attendance.edit', $record) }}">
                        Edit
                    </a>

                    <form
                        action="{{ route('attendance.destroy', $record) }}"
                        method="POST"
                        style="display:inline;"
                    >

                        @csrf

                        @method('DELETE')

                        <button
                            type="submit"
                            class="danger"
                            onclick="return confirm('Are you sure you want to delete this attendance record?')"
                        >
                            Delete
                        </button>

                    </form>

                </td>

            </tr>

        @empty

            <tr>
                <td colspan="12">
                    No attendance records found.
                </td>
            </tr>

        @endforelse

    </tbody>

</table>

@endsection