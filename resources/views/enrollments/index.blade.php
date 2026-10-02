@extends('layouts.crud')

@section('content')

<h1>Student Enrollments</h1>

@if ($errors->has('delete'))
    <div class="error">{{ $errors->first('delete') }}</div>
@endif

<a href="{{ route('dashboard') }}">Dashboard</a>

<a href="{{ route('enrollments.create') }}" class="primary">
    Enroll Student
</a>

<table>

    <tr>
        <th>Student</th>
        <th>Admission Number</th>
        <th>Academic Year</th>
        <th>Class</th>
        <th>Stream</th>
        <th>Status</th>
        <th>Enrollment Date</th>
        <th>Actions</th>
    </tr>

    @forelse($enrollments as $enrollment)

        <tr>

            <td>
                {{ $enrollment->student->first_name }}
                {{ $enrollment->student->last_name }}
            </td>

            <td>
                {{ $enrollment->student->admission_number }}
            </td>

            <td>
                {{ $enrollment->academicYear->name ?? '-' }}
            </td>

            <td>
                {{ $enrollment->classRoom->name ?? '-' }}
            </td>

            <td>
                {{ $enrollment->stream->name ?? '-' }}
            </td>

            <td>
                {{ $enrollment->status }}
            </td>

            <td>
                {{ $enrollment->enrollment_date?->format('Y-m-d') }}
            </td>

            <td>

                @if(auth()->user()->hasPermission('enrollments.update'))
                    <a
                        href="{{ route('enrollments.edit', $enrollment) }}"
                        class="warning"
                    >
                        Edit
                    </a>
                @endif

                <form
                    action="{{ route('enrollments.destroy', $enrollment) }}"
                    method="POST"
                    style="display:inline"
                >

                    @csrf
                    @method('DELETE')

                    <button
                        class="danger"
                        onclick="return confirm('Delete this enrollment?')"
                    >
                        Delete
                    </button>

                </form>

            </td>

        </tr>

    @empty

        <tr>
            <td colspan="8">No enrollments found.</td>
        </tr>

    @endforelse

</table>

@endsection