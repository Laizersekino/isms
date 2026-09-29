@extends('layouts.crud')

@section('content')

<h1>Classes</h1>

<a href="{{ route('classes.create') }}" class="primary">
    Create Class
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
            <th>Name</th>
            <th>Description</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>

    <tbody>
        @forelse($classes as $class)
            <tr>
                <td>{{ $class->id }}</td>
                <td>{{ $class->name }}</td>
                <td>{{ $class->description }}</td>
                <td>{{ $class->status }}</td>

                <td>
                    <a href="{{ route('classes.edit', $class) }}">
                        Edit
                    </a>

                    <form
                        action="{{ route('classes.destroy', $class) }}"
                        method="POST"
                        style="display:inline;"
                    >
                        @csrf
                        @method('DELETE')

                        <button
                            type="submit"
                            class="danger"
                            onclick="return confirm('Are you sure you want to delete this class?')"
                        >
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5">
                    No classes found.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

@endsection