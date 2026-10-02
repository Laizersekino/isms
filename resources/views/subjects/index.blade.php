@extends('layouts.crud')

@section('content')

<h1>Subjects</h1>

@if ($errors->has('delete'))
    <div class="error">{{ $errors->first('delete') }}</div>
@endif

<a href="{{ route('subjects.create') }}" class="primary">
    Create Subject
</a>

@if(session('success'))
    <div class="success">
        {{ session('success') }}
    </div>
@endif
@if ($errors->has('delete'))
    <div class="error">{{ $errors->first('delete') }}</div>
@endif

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Code</th>
            <th>Name</th>
            <th>Description</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>

    <tbody>
        @forelse($subjects as $subject)
            <tr>
                <td>{{ $subject->id }}</td>
                <td>{{ $subject->code }}</td>
                <td>{{ $subject->name }}</td>
                <td>{{ $subject->description }}</td>
                <td>{{ $subject->status }}</td>

                <td>
                    @if(auth()->user()->hasPermission('academic_structure.update'))
                        <a href="{{ route('subjects.edit', $subject) }}">
                            Edit
                        </a>
                    @endif

                    <form action="{{ route('subjects.destroy', $subject) }}"
                          method="POST"
                          style="display:inline;">

                        @csrf
                        @method('DELETE')

                        <button type="submit"
                                class="danger"
                                onclick="return confirm('Are you sure you want to delete this subject?')">
                            Delete
                        </button>
                    </form>
                </td>
            </tr>

        @empty

            <tr>
                <td colspan="6">
                    No subjects found.
                </td>
            </tr>

        @endforelse
    </tbody>
</table>

@endsection