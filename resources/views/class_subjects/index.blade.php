@extends('layouts.crud')

@section('content')

<h1>Class Subject Assignments</h1>

<a href="{{ route('dashboard') }}">Dashboard</a>

<a href="{{ route('class-subjects.create') }}" class="primary">
    Assign Subject
</a>

<table>

    <tr>
        <th>Class</th>
        <th>Subjects</th>
        <th>Actions</th>
    </tr>

    @forelse($classes as $class)

        <tr>

            <td>{{ $class->name }}</td>

            <td>

                @forelse($class->subjects as $subject)

                    <div>

                        {{ $subject->code }} -
                        {{ $subject->name }}

                        <form
                            action="{{ route(
                                'class-subjects.destroy',
                                [$class, $subject]
                            ) }}"
                            method="POST"
                            style="display:inline"
                        >

                            @csrf
                            @method('DELETE')

                            <button
                                class="danger"
                                onclick="return confirm('Remove this subject from the class?')"
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
                    href="{{ route('class-subjects.create') }}"
                    class="primary"
                >
                    Assign
                </a>
            </td>

        </tr>

    @empty

        <tr>
            <td colspan="3">No classes found.</td>
        </tr>

    @endforelse

</table>

@endsection