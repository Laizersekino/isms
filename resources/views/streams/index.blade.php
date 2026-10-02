@extends('layouts.crud')

@section('content')

<h1>Streams</h1>

@if ($errors->has('delete'))
    <div class="error">{{ $errors->first('delete') }}</div>
@endif

<a href="{{ route('streams.create') }}" class="primary">
    Create Stream
</a>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Class</th>
            <th>Stream Name</th>
            <th>Description</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>

    <tbody>
        @forelse($streams as $stream)
            <tr>
                <td>{{ $stream->id }}</td>
                <td>{{ $stream->classRoom->name ?? 'N/A' }}</td>
                <td>{{ $stream->name }}</td>
                <td>{{ $stream->description }}</td>
                <td>{{ $stream->status }}</td>

                <td>
                    @if(auth()->user()->hasPermission('academic_structure.update'))
                        <a href="{{ route('streams.edit', $stream) }}">
                            Edit
                        </a>
                    @endif

                    <form
                        action="{{ route('streams.destroy', $stream) }}"
                        method="POST"
                        style="display:inline;"
                    >
                        @csrf
                        @method('DELETE')

                        <button
                            type="submit"
                            class="danger"
                            onclick="return confirm('Are you sure you want to delete this stream?')"
                        >
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6">No streams found.</td>
            </tr>
        @endforelse
    </tbody>
</table>

@endsection