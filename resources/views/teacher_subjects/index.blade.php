@extends('layouts.crud')

@section('content')

<h1>Teacher Subject Assignments</h1>

<a href="{{ route('dashboard') }}">Dashboard</a>

<a href="{{ route('teacher-subjects.create') }}" class="primary">
    Assign Subject
</a>

<table>

    <tr>
        <th>Teacher</th>
        <th>Employee Number</th>
        <th>Subjects</th>
        <th>Actions</th>
    </tr>

    @forelse($teachers as $teacher)

        <tr>

            <td>
                {{ $teacher->first_name }}
                {{ $teacher->middle_name }}
                {{ $teacher->last_name }}
            </td>

            <td>{{ $teacher->employee_number }}</td>

            <td>

                @forelse($teacher->subjects as $subject)

                    <div>
                        {{ $subject->code }} - {{ $subject->name }}

                        <form
                            action="{{ route(
                                'teacher-subjects.destroy',
                                [$teacher, $subject]
                            ) }}"
                            method="POST"
                            style="display:inline"
                        >

                            @csrf
                            @method('DELETE')

                            <button
                                class="danger"
                                onclick="return confirm('Remove this subject?')"
                            >
                                Remove
                            </button>

                        </form>
                    </div>

                @empty

                    No subjects assigned.

                @endforelse

            </td>

            <td>
                <a
                    href="{{ route('teacher-subjects.create') }}"
                    class="primary"
                >
                    Assign
                </a>
            </td>

        </tr>

    @empty

        <tr>
            <td colspan="4">No teachers found.</td>
        </tr>

    @endforelse

</table>

@endsection