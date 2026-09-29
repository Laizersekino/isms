@extends('layouts.crud')

@section('content')

<h1>Edit Stream</h1>

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

<form action="{{ route('streams.update', $stream) }}" method="POST">

    @csrf
    @method('PUT')

    <div>
        <label for="class_id">Class</label><br>

        <select name="class_id" id="class_id" required>
            <option value="">-- Select Class --</option>

            @foreach($classes as $class)
                <option
                    value="{{ $class->id }}"
                    {{ old('class_id', $stream->class_id) == $class->id ? 'selected' : '' }}
                >
                    {{ $class->name }}
                </option>
            @endforeach
        </select>
    </div>

    <br>

    <div>
        <label for="name">Stream Name</label><br>

        <input
            type="text"
            name="name"
            id="name"
            value="{{ old('name', $stream->name) }}"
            required
        >
    </div>

    <br>

    <div>
        <label for="description">Description</label><br>

        <textarea
            name="description"
            id="description"
            rows="4"
        >{{ old('description', $stream->description) }}</textarea>
    </div>

    <br>

    <div>
        <label for="status">Status</label><br>

        <select name="status" id="status" required>
            <option
                value="active"
                {{ old('status', $stream->status) == 'active' ? 'selected' : '' }}
            >
                Active
            </option>

            <option
                value="inactive"
                {{ old('status', $stream->status) == 'inactive' ? 'selected' : '' }}
            >
                Inactive
            </option>
        </select>
    </div>

    <br>

    <button type="submit" class="primary">
        Update Stream
    </button>

    <a href="{{ route('streams.index') }}">
        Cancel
    </a>

</form>

@endsection