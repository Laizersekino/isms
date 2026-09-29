@extends('layouts.crud')

@section('content')

<h1>Teacher Assignments</h1>

@if(auth()->user()->hasPermission('teachers.create')) <p> <a href="{{ route('teacher-assignments.create') }}" class="primary">
+ Create Assignment </a> </p>
@endif

@if($assignments->count() > 0)

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Teacher</th>
            <th>Class</th>
            <th>Stream</th>
            <th>Subject</th>
            <th>Academic Year</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>

```
<tbody>
    @foreach($assignments as $assignment)
        <tr>
            <td>{{ $assignment->id }}</td>

            <td>
                {{ $assignment->teacher->first_name }}
                {{ $assignment->teacher->middle_name }}
                {{ $assignment->teacher->last_name }}
            </td>

            <td>
                {{ $assignment->classRoom->name }}
            </td>

            <td>
                {{ $assignment->stream->name }}
            </td>

            <td>
                {{ $assignment->subject->name }}
            </td>

            <td>
                {{ $assignment->academicYear->name }}
            </td>

            <td>
                {{ ucfirst($assignment->status) }}
            </td>

            <td>

                @if(auth()->user()->hasPermission('teachers.update'))
                    <a href="{{ route('teacher-assignments.edit', $assignment) }}">
                        Edit
                    </a>
                @endif

                @if(auth()->user()->hasPermission('teachers.delete'))
                    <form action="{{ route('teacher-assignments.destroy', $assignment) }}"
                          method="POST"
                          style="display:inline;"
                          onsubmit="return confirm('Are you sure you want to delete this assignment?');">

                        @csrf
                        @method('DELETE')

                        <button type="submit" class="danger">
                            Delete
                        </button>

                    </form>
                @endif

            </td>
        </tr>
    @endforeach
</tbody>
```

</table>

@else

```
<p>No teacher assignments have been created yet.</p>
```

@endif

@endsection
