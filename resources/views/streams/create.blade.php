@extends('layouts.crud')

@section('content')

<h1>Create Stream</h1>

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

<form action="{{ route('streams.store') }}" method="POST">
    @csrf

    <div>
        <label for="class_id">Class</label><br>

        <select name="class_id" id="class_id" required>
            <option value="">-- Select Class --</option>

            @foreach($classes as $class)
                <option
                    value="{{ $class->id }}"
                    {{ old('class_id') == $class->id ? 'selected' : '' }}
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
            id="name"
            name="name"
            value="{{ old('name') }}"
            required
        >
    </div>

    <br>

    <div>
        <label for="description">Description</label><br>

        <textarea
            id="description"
            name="description"
            rows="4"
        >{{ old('description') }}</textarea>
    </div>

    <br>

    <div>
        <label for="status">Status</label><br>

        <select name="status" id="status" required>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>

    <br>

    <button type="submit" class="primary">
        Save Stream
    </button>

    <a href="{{ route('streams.index') }}">
        Cancel
    </a>

</form>

@endsection