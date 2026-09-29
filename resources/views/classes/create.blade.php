@extends('layouts.crud')

@section('content')

<h1>Create Class</h1>

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

<form action="{{ route('classes.store') }}" method="POST">
    @csrf

    <div>
        <label for="name">Class Name</label><br>
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
        <select id="status" name="status" required>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>

    <br>

    <button type="submit" class="primary">
        Save Class
    </button>

    <a href="{{ route('classes.index') }}">
        Cancel
    </a>

</form>

@endsection